<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Genre;
use App\Entity\Personnage;
use App\Entity\Territoire;
use App\Entity\User;
use App\Entity\XpReport;
use App\Enum\Role;
use App\Repository\XpReportRepository;
use App\Exception\ConceptionInvalideException;
use App\Service\HeritierSaisie;
use App\Service\HeritierService;
use App\Service\XpReportCalculateur;
use App\Service\XpReportEligibilite;
use App\Service\XpReportException;
use App\Service\XpReportHeritiers;
use App\Service\XpReportService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Bonus d'XP à la mort d'un personnage, côté scénariste : seul un scénariste indique un héritage (moitié) ou force
 * un report vers un personnage qui a déjà participé.
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
#[IsGranted(Role::SCENARISTE->value)]
final class XpReportScenaristeController extends AbstractController
{
    #[Route('/personnage/{personnage}/xp-report/scenariste', name: 'personnage.xp.report.admin', requirements: ['personnage' => '\d+'], methods: ['GET'])]
    public function pageAction(
        XpReportRepository $repository,
        XpReportCalculateur $calculateur,
        XpReportEligibilite $eligibilite,
        XpReportHeritiers $heritiers,
        #[MapEntity]
        Personnage $personnage,
    ): Response {
        $heritiersLignes = [];
        foreach ($heritiers->heritiersDe($personnage) as $ligne) {
            $ligne['servi'] = $repository->findOneByBeneficiaire($ligne['personnage']);
            $heritiersLignes[] = $ligne;
        }
        $idsHeritiers = array_map(static fn (array $ligne): ?int => $ligne['personnage']->getId(), $heritiersLignes);

        $candidats = [];
        if (null !== $personnage->getUser()) {
            foreach ($eligibilite->candidatsDuJoueur($personnage->getUser()) as $ligne) {
                if ($ligne['personnage']->getId() !== $personnage->getId() && !\in_array($ligne['personnage']->getId(), $idsHeritiers, strict: true)) {
                    $candidats[] = $ligne;
                }
            }
        }

        return $this->render('personnage/xp_report_admin.twig', [
            'personnage' => $personnage,
            'report' => $repository->findOneByMort($personnage),
            'montants' => $calculateur->montants($personnage),
            'heritiers' => $heritiersLignes,
            'candidats' => $candidats,
            'gnMinimum' => XpReportHeritiers::GN_MINIMUM,
            'genres' => $this->entityManager->getRepository(Genre::class)->findAll(),
            'territoires' => $this->entityManager->getRepository(Territoire::class)->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/personnage/{personnage}/xp-report/scenariste/reporter', name: 'personnage.xp.report.admin.reporter', requirements: ['personnage' => '\d+'], methods: ['POST'])]
    public function reporterAction(
        Request $request,
        XpReportService $service,
        XpReportEligibilite $eligibilite,
        XpReportHeritiers $heritiers,
        #[MapEntity]
        Personnage $personnage,
    ): Response {
        $this->verifierJeton($request, $personnage);
        $retour = $this->redirectToRoute('personnage.xp.report.admin', ['personnage' => $personnage->getId()], 303);

        $cible = $this->entityManager->find(Personnage::class, $request->request->getInt('cible'));
        if (null === $cible) {
            $this->addFlash('danger', 'Personnage destinataire introuvable.');

            return $retour;
        }

        // Le mode découle du lien : un héritier reçoit la moitié, un autre personnage du joueur le tiers.
        $mode = null;
        $avertissement = null;
        foreach ($heritiers->heritiersDe($personnage) as $ligne) {
            if ($ligne['personnage']->getId() === $cible->getId()) {
                $mode = XpReportCalculateur::MODE_MOITIE;
                $avertissement = $ligne['ageOk']
                    ? null
                    : 'L\'âge de cet héritier n\'est pas confirmé (moins de ' . XpReportHeritiers::GN_MINIMUM . ' GN écoulés depuis la conception, ou conception inconnue).';
                break;
            }
        }
        if (null === $mode && null !== $personnage->getUser()) {
            foreach ($eligibilite->candidatsDuJoueur($personnage->getUser()) as $ligne) {
                if ($ligne['personnage']->getId() === $cible->getId()) {
                    $mode = XpReportCalculateur::MODE_TIERS;
                    break;
                }
            }
        }
        if (null === $mode) {
            $this->addFlash('danger', 'Ce personnage n\'est ni un héritier ni un personnage du même joueur sans bonus.');

            return $retour;
        }

        $forcer = $request->request->getBoolean('forcer');
        $motif = trim($request->request->getString('motif'));
        if (null !== $avertissement && !$forcer) {
            $this->addFlash('warning', $avertissement . ' Cochez « Forcer » et indiquez un motif pour continuer.');

            return $retour;
        }
        if ($forcer && '' === $motif) {
            $this->addFlash('danger', 'Un motif est obligatoire pour forcer le report.');

            return $retour;
        }

        try {
            $report = $service->reporter($personnage, $cible, $mode, $this->getUser() instanceof User ? $this->getUser() : null, $forcer, $motif);
            $this->addFlash('success', \sprintf('%d XP de %s ont été transmis à %s.', $report->getXpReporte(), $personnage->getIdentity(withId: false), $cible->getIdentity(withId: false)));
        } catch (XpReportException $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $retour;
    }

    #[Route('/personnage/{personnage}/xp-report/scenariste/heritier', name: 'personnage.xp.report.admin.heritier', requirements: ['personnage' => '\d+'], methods: ['POST'])]
    public function creerHeritierAction(
        Request $request,
        HeritierService $heritierService,
        XpReportService $service,
        XpReportRepository $repository,
        #[MapEntity]
        Personnage $personnage,
    ): Response {
        $this->verifierJeton($request, $personnage);
        $retour = $this->redirectToRoute('personnage.xp.report.admin', ['personnage' => $personnage->getId()], 303);

        if ($personnage->getVivant() || $repository->findOneByMort($personnage) instanceof XpReport) {
            $this->addFlash('danger', 'Création impossible : le personnage est vivant ou son bonus est déjà transmis.');

            return $retour;
        }

        $saisie = new HeritierSaisie();
        $saisie->nom = $request->request->getString('nom');
        $saisie->genre = $this->entityManager->find(Genre::class, $request->request->getInt('genre'));
        $saisie->territoire = $this->entityManager->find(Territoire::class, $request->request->getInt('territoire'));

        try {
            $heritier = $heritierService->creerDepuisParent($personnage, null, $saisie);
            $report = $service->reporter($personnage, $heritier, XpReportCalculateur::MODE_MOITIE, $this->getUser() instanceof User ? $this->getUser() : null);
            $this->addFlash('success', \sprintf(
                'Héritier %s créé, avec %d XP transmis par %s.',
                $heritier->getIdentity(withId: false),
                $report->getXpReporte(),
                $personnage->getIdentity(withId: false),
            ));
        } catch (ConceptionInvalideException|XpReportException $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        return $retour;
    }

    #[Route('/personnage/{personnage}/xp-report/scenariste/annuler', name: 'personnage.xp.report.admin.annuler', requirements: ['personnage' => '\d+'], methods: ['POST'])]
    public function annulerAction(
        Request $request,
        XpReportService $service,
        XpReportRepository $repository,
        #[MapEntity]
        Personnage $personnage,
    ): Response {
        $this->verifierJeton($request, $personnage);
        $report = $repository->findOneByMort($personnage);
        if (!$report instanceof XpReport) {
            $this->addFlash('warning', 'Aucun report à annuler.');
        } else {
            try {
                $service->annuler($report, $this->getUser() instanceof User ? $this->getUser() : null, $request->request->getString('motif'));
                $this->addFlash('success', 'Report annulé : le bonus a été retiré au destinataire.');
            } catch (XpReportException $exception) {
                $this->addFlash('danger', $exception->getMessage());
            }
        }

        return $this->redirectToRoute('personnage.xp.report.admin', ['personnage' => $personnage->getId()], 303);
    }

    private function verifierJeton(Request $request, Personnage $personnage): void
    {
        if (!$this->isCsrfTokenValid('xp_report_admin_' . $personnage->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
