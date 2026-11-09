<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Exception\ContenuInvalideException;
use App\Service\DepouillementDecisionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Décision d'un responsable sur une ligne déclarée par le joueur : confirmer, refuser, ou remettre en attente un élément refusé.
 * Compléter se fait en saisissant l'élément dans l'étape Déclaration.
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
final class DepouillementDecisionController extends AbstractDepouillementController
{
    #[Route('/gn/{gn}/depouillement/{depouillement}/decision', name: 'depouillement.decision', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function decisionAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementDecisionService $decisionService): Response
    {
        $this->verifierContenu($gn, $depouillement);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::DECLARATION), 303);

        if (!$this->jetonValide($request, 'depouillement_decision_' . $depouillement->getId())) {
            return $retour;
        }

        try {
            $decisionService->decider($depouillement, $request->request->getString('champ'), $request->request->getString('element'), $request->request->getString('decision'), $this->utilisateur());
        } catch (ContenuInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash('success', 'Décision enregistrée.');

        return $retour;
    }
}
