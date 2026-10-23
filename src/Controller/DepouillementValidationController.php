<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Exception\ValidationInvalideException;
use App\Security\Voter\DepouillementVoter;
use App\Service\DepouillementImportService;
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

        if (!$this->jetonValide($request, 'depouillement_valider_' . $depouillement->getId())) {
            return $retour;
        }

        try {
            $apprentissages = $validationService->valider($depouillement, $this->utilisateur());
        } catch (ValidationInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash('success', 'Dépouillement validé. Il ne peut plus être rouvert.');
        foreach ($apprentissages->parties() as $partie) {
            $this->addFlash([] === $partie->anomalies ? 'info' : 'warning', $partie->resume());
        }

        return $retour;
    }

    #[Route('/gn/{gn}/depouillement/{depouillement}/renvoyer', name: 'depouillement.renvoyer', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function renvoyerAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementValidationService $validationService): Response
    {
        $this->verifier($gn, $depouillement);
        $this->denyAccessUnlessGranted(DepouillementVoter::VALIDER, $depouillement);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::RECAPITULATIF), 303);

        if (!$this->jetonValide($request, 'depouillement_renvoyer_' . $depouillement->getId())) {
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

    /**
     * Retente le report vers l'enveloppe du GN suivant (ivoire et étiquettes) d'un dépouillement validé ou en attente d'import.
     */
    #[Route('/gn/{gn}/depouillement/{depouillement}/importer', name: 'depouillement.importer', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function importerAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementImportService $importService): Response
    {
        $this->verifier($gn, $depouillement);
        $this->denyAccessUnlessGranted(DepouillementVoter::VALIDER, $depouillement);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::RECAPITULATIF), 303);

        if (!$this->jetonValide($request, 'depouillement_importer_' . $depouillement->getId())) {
            return $retour;
        }

        try {
            $resultat = $importService->importer($depouillement, $this->utilisateur());
        } catch (ValidationInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash([] === $resultat->anomalies ? 'success' : 'warning', $resultat->resume());

        return $retour;
    }
}
