<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\DepouillementEtiquette;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Exception\EtiquetteInvalideException;
use App\Form\Depouillement\DepouillementEtiquetteType;
use App\Service\DepouillementEtiquetteService;
use App\Service\EtiquetteSaisie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Étiquettes d'objets et d'armes trouvées dans l'enveloppe : ajout et suppression depuis l'étape Contenu.
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
final class DepouillementEtiquetteController extends AbstractDepouillementController
{
    #[Route('/gn/{gn}/depouillement/{depouillement}/etiquettes', name: 'depouillement.etiquette.add', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function addAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementEtiquetteService $etiquetteService): Response
    {
        $this->verifier($gn, $depouillement);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::CONTENU), 303);

        $form = $this->createForm(DepouillementEtiquetteType::class, new EtiquetteSaisie());
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->flashErreurs($form);

            return $retour;
        }

        try {
            $etiquette = $etiquetteService->ajouter($depouillement, $form->getData(), $this->utilisateur());
        } catch (EtiquetteInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash('success', 'Étiquette ajoutée : ' . $etiquette->getDestination()->label() . '.');

        return $retour;
    }

    #[Route(
        '/gn/{gn}/depouillement/{depouillement}/etiquettes/{etiquette}/supprimer',
        name: 'depouillement.etiquette.delete',
        requirements: ['gn' => '\d+', 'depouillement' => '\d+', 'etiquette' => '\d+'],
        methods: ['POST'],
    )]
    public function deleteAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementEtiquette $etiquette, DepouillementEtiquetteService $etiquetteService): Response
    {
        $this->verifier($gn, $depouillement);
        if ($etiquette->getDepouillement()->getId() !== $depouillement->getId()) {
            throw $this->createNotFoundException();
        }

        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::CONTENU), 303);

        if (!$this->jetonValide($request, 'depouillement_etiquette_' . $etiquette->getId())) {
            return $retour;
        }

        if (!$depouillement->getStatut()->isEditable()) {
            $this->addFlash('error', Depouillement::MESSAGE_NON_MODIFIABLE);

            return $retour;
        }

        $etiquetteService->supprimer($etiquette, $this->utilisateur());
        $this->addFlash('success', 'L\'étiquette a été supprimée.');

        return $retour;
    }
}
