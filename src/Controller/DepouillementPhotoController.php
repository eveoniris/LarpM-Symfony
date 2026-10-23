<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\DepouillementPhoto;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Exception\PhotoInvalideException;
use App\Security\Voter\DepouillementPhotoVoter;
use App\Service\DepouillementPhotoService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Photos du contenu d'une enveloppe : envoi, affichage (jamais depuis le dossier public), suppression, partage.
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
final class DepouillementPhotoController extends AbstractDepouillementController
{
    /**
     * Envoi d'une ou plusieurs photos (appareil photo ou galerie du téléphone).
     */
    #[Route('/gn/{gn}/depouillement/{depouillement}/photos', name: 'depouillement.photo.add', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function addAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementPhotoService $photoService): Response
    {
        $this->verifier($gn, $depouillement);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::PHOTOS), 303);

        // Au-delà de post_max_size, PHP vide tout l'envoi (fichiers et jeton) : on l'explique plutôt qu'un "jeton invalide".
        if (0 === $request->request->count() && $request->files->count() === 0 && (int) $request->server->get('CONTENT_LENGTH', 0) > 0) {
            $this->addFlash('error', 'Envoi trop volumineux : réessayez avec moins de photos à la fois.');

            return $retour;
        }

        if (!$this->jetonValide($request, 'depouillement_photos_' . $depouillement->getId())) {
            return $retour;
        }

        $fichiers = array_filter((array) $request->files->get('photos'), static fn (mixed $fichier): bool => $fichier instanceof UploadedFile);
        if ([] === $fichiers) {
            $this->addFlash('error', 'Aucune photo sélectionnée.');

            return $retour;
        }

        $ajoutees = 0;
        foreach ($fichiers as $fichier) {
            try {
                $photoService->ajouter($depouillement, $fichier, $this->utilisateur());
                ++$ajoutees;
            } catch (PhotoInvalideException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        if ($ajoutees > 0) {
            $this->addFlash('success', \sprintf('%d photo(s) ajoutée(s).', $ajoutees));
        }

        return $retour;
    }

    /**
     * Affiche une photo. Les droits sont contrôlés à chaque requête : le fichier n'est jamais public.
     */
    #[Route('/gn/{gn}/depouillement/{depouillement}/photos/{photo}', name: 'depouillement.photo.show', requirements: ['gn' => '\d+', 'depouillement' => '\d+', 'photo' => '\d+'], methods: ['GET'])]
    public function showAction(Gn $gn, Depouillement $depouillement, DepouillementPhoto $photo, DepouillementPhotoService $photoService): Response
    {
        $this->verifierPhoto($gn, $depouillement, $photo);
        $this->denyAccessUnlessGranted(DepouillementPhotoVoter::VIEW, $photo);

        $chemin = $photoService->cheminComplet($photo);
        if (!is_file($chemin)) {
            throw $this->createNotFoundException();
        }

        $reponse = new BinaryFileResponse($chemin);
        $reponse->headers->set('Content-Type', $photo->getMimeType());
        $reponse->headers->set('X-Content-Type-Options', 'nosniff');
        $reponse->headers->set('Cache-Control', 'private, max-age=3600');
        $reponse->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'photo-' . $photo->getId() . '.' . pathinfo($photo->getChemin(), PATHINFO_EXTENSION));

        return $reponse;
    }

    #[Route(
        '/gn/{gn}/depouillement/{depouillement}/photos/{photo}/supprimer',
        name: 'depouillement.photo.delete',
        requirements: ['gn' => '\d+', 'depouillement' => '\d+', 'photo' => '\d+'],
        methods: ['POST'],
    )]
    public function deleteAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementPhoto $photo, DepouillementPhotoService $photoService): Response
    {
        $this->verifierPhoto($gn, $depouillement, $photo);
        $this->denyAccessUnlessGranted(DepouillementPhotoVoter::MANAGE, $photo);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::PHOTOS), 303);

        if (!$this->jetonValide($request, 'depouillement_photo_' . $photo->getId())) {
            return $retour;
        }

        if (!$depouillement->getStatut()->isEditable()) {
            $this->addFlash('error', Depouillement::MESSAGE_NON_MODIFIABLE);

            return $retour;
        }

        $photoService->supprimer($photo, $this->utilisateur());
        $this->addFlash('success', 'La photo a été supprimée.');

        return $retour;
    }

    /**
     * Partage la photo avec le propriétaire de l'enveloppe (joueur ou chef de groupe), ou retire ce partage.
     */
    #[Route(
        '/gn/{gn}/depouillement/{depouillement}/photos/{photo}/partager',
        name: 'depouillement.photo.share',
        requirements: ['gn' => '\d+', 'depouillement' => '\d+', 'photo' => '\d+'],
        methods: ['POST'],
    )]
    public function shareAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementPhoto $photo, DepouillementPhotoService $photoService): Response
    {
        $this->verifierPhoto($gn, $depouillement, $photo);
        $this->denyAccessUnlessGranted(DepouillementPhotoVoter::MANAGE, $photo);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::PHOTOS), 303);

        if (!$this->jetonValide($request, 'depouillement_photo_' . $photo->getId())) {
            return $retour;
        }

        $this->addFlash('success', $photoService->basculerPartage($photo, $this->utilisateur()) ? 'Photo partagée avec le propriétaire de l\'enveloppe.' : 'Partage retiré.');

        return $retour;
    }

    private function verifierPhoto(Gn $gn, Depouillement $depouillement, DepouillementPhoto $photo): void
    {
        $this->verifier($gn, $depouillement);

        if ($photo->getDepouillement()->getId() !== $depouillement->getId()) {
            throw $this->createNotFoundException();
        }
    }
}
