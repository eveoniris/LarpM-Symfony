<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Conception;
use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Exception\ConceptionInvalideException;
use App\Service\ConceptionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Conceptions d'héritier signalées dans une enveloppe de joueur : ajout et retrait depuis l'étape Contenu.
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
final class DepouillementConceptionController extends AbstractDepouillementController
{
    #[Route('/gn/{gn}/depouillement/{depouillement}/conceptions', name: 'depouillement.conception.add', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function addAction(Request $request, Gn $gn, Depouillement $depouillement, ConceptionService $conceptionService): Response
    {
        $this->verifier($gn, $depouillement);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::CONTENU), 303);

        if (!$this->jetonValide($request, 'depouillement_conception_add_' . $depouillement->getId())) {
            return $retour;
        }

        try {
            $conception = $conceptionService->enregistrer($depouillement, $request->request->getString('partenaire'), $this->utilisateur());
        } catch (ConceptionInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash(
            'success',
            null === $conception->getTirage()
                ? 'Conception enregistrée : pas de tirage possible (voir l\'anomalie), le scénariste décidera.'
                : 'Conception enregistrée, le tirage est fait : le scénariste décidera de la suite.',
        );

        return $retour;
    }

    #[Route(
        '/gn/{gn}/depouillement/{depouillement}/conceptions/{conception}/supprimer',
        name: 'depouillement.conception.delete',
        requirements: ['gn' => '\d+', 'depouillement' => '\d+', 'conception' => '\d+'],
        methods: ['POST'],
    )]
    public function deleteAction(Request $request, Gn $gn, Depouillement $depouillement, Conception $conception, ConceptionService $conceptionService): Response
    {
        $this->verifier($gn, $depouillement);
        if ($conception->getDepouillement()?->getId() !== $depouillement->getId()) {
            throw $this->createNotFoundException();
        }

        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::CONTENU), 303);

        if (!$this->jetonValide($request, 'depouillement_conception_' . $conception->getId())) {
            return $retour;
        }

        try {
            $conceptionService->supprimer($conception, $this->utilisateur());
        } catch (ConceptionInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash('success', 'La conception a été retirée.');

        return $retour;
    }
}
