<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ExperienceGain;
use App\Service\RetraitXpService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Retrait d'un gain d'XP depuis l'historique admin (suppression logique ou définitive, au choix).
 */
#[IsGranted('ROLE_ADMIN')]
final class RetraitXpController extends AbstractController
{
    #[Route('/admin/xp/gain/{gain}/retirer', name: 'xp.gain.retirer', requirements: ['gain' => '\d+'], methods: ['POST'])]
    public function retirerAction(Request $request, RetraitXpService $service, #[MapEntity] ExperienceGain $gain): Response
    {
        if (!$this->isCsrfTokenValid('xp_retrait_' . $gain->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $motif = trim($request->request->getString('motif'));
        if ('' === $motif) {
            $this->addFlash('danger', 'Le motif du retrait est obligatoire.');

            return $this->retour($request);
        }

        $retire = $service->retirer($gain, $request->request->getString('mode'), $motif);
        $this->addFlash($retire ? 'success' : 'warning', $retire ? 'Gain d\'XP retiré, solde du personnage corrigé.' : 'Retrait impossible (déjà retiré ou mode inconnu).');

        return $this->retour($request);
    }

    /** Retour à la page d'origine (chemin local uniquement, pas d'URL externe), sinon à l'historique admin. */
    private function retour(Request $request): Response
    {
        $retour = $request->request->getString('retour');
        if ($this->isCheminLocal($retour)) {
            return $this->redirect($retour);
        }

        return $this->redirectToRoute('xp.gain');
    }
}
