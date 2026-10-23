<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Exception\ValidationInvalideException;
use App\Security\Voter\DepouillementVoter;
use App\Service\DepouillementValidationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Validation d'un dépouillement par un scénariste, un wargame ou un admin, ou renvoi au dépouilleur pour correction.
 * Un dépouillement validé ne peut pas être rouvert.
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
final class DepouillementValidationController extends AbstractDepouillementController
{
    #[Route('/gn/{gn}/depouillement/{depouillement}/valider', name: 'depouillement.valider', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function validerAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementValidationService $validationService): Response
    {
        $this->verifier($gn, $depouillement);
        $this->denyAccessUnlessGranted(DepouillementVoter::VALIDER, $depouillement);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::RECAPITULATIF), 303);

        if (!$this->isCsrfTokenValid('depouillement_valider_' . $depouillement->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $retour;
        }

        try {
            $validationService->valider($depouillement, $this->utilisateur());
        } catch (ValidationInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash('success', 'Dépouillement validé. Il ne peut plus être rouvert.');

        return $retour;
    }

    #[Route('/gn/{gn}/depouillement/{depouillement}/renvoyer', name: 'depouillement.renvoyer', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function renvoyerAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementValidationService $validationService): Response
    {
        $this->verifier($gn, $depouillement);
        $this->denyAccessUnlessGranted(DepouillementVoter::VALIDER, $depouillement);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::RECAPITULATIF), 303);

        if (!$this->isCsrfTokenValid('depouillement_renvoyer_' . $depouillement->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $retour;
        }

        try {
            $validationService->renvoyer($depouillement, $this->utilisateur(), $request->request->getString('motif'));
        } catch (ValidationInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash('success', 'Dépouillement renvoyé au dépouilleur pour correction.');

        return $retour;
    }
}
