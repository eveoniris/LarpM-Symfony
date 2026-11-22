<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Personnage;
use App\Entity\User;
use App\Service\XpReportCalculateur;
use App\Service\XpReportException;
use App\Service\XpReportService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Récupération par le joueur du bonus d'XP (tiers) d'un de ses personnages morts sur un de ses personnages
 * qui n'a pas encore participé. L'héritage (moitié) ne passe pas ici : seul un scénariste peut l'indiquer.
 */
#[IsGranted('ROLE_USER')]
final class XpReportJoueurController extends AbstractController
{
    #[Route('/personnage/{personnage}/xp-report', name: 'personnage.xp.report', requirements: ['personnage' => '\d+'], methods: ['POST'])]
    public function recupererAction(
        Request $request,
        XpReportService $service,
        #[MapEntity]
        Personnage $personnage,
    ): Response {
        $joueur = $this->getUser();
        if (!$joueur instanceof User || $personnage->getUser()?->getId() !== $joueur->getId()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('xp_report_' . $personnage->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $mort = $this->entityManager->find(Personnage::class, $request->request->getInt('mort'));
        if (null === $mort || $mort->getUser()?->getId() !== $joueur->getId()) {
            $this->addFlash('danger', 'Personnage mort introuvable.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        try {
            $report = $service->reporter($mort, $personnage, XpReportCalculateur::MODE_TIERS, $joueur);
            $this->addFlash('success', \sprintf('%d XP de %s ont été ajoutés à %s.', $report->getXpReporte(), $mort->getIdentity(withId: false), $personnage->getIdentity(withId: false)));
        } catch (XpReportException $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
    }
}
