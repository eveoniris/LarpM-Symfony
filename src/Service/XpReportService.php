<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ExperienceGain;
use App\Entity\LogAction;
use App\Entity\Personnage;
use App\Entity\User;
use App\Entity\XpReport;
use App\Enum\LogActionType;
use App\Repository\XpReportRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Report du bonus d'XP d'un personnage mort vers un autre personnage (règles XP-05 à XP-07).
 *
 * Les droits (qui a le droit de désigner un héritier, de forcer) sont décidés par l'appelant :
 * ce service contrôle les règles métier et refuse avec une XpReportException.
 */
// @mago-expect lint:cyclomatic-complexity
final readonly class XpReportService
{
    // @mago-expect lint:excessive-parameter-list
    public function __construct(
        private EntityManagerInterface $entityManager,
        private XpReportCalculateur $calculateur,
        private XpReportRepository $repository,
        private PersonnageParticipation $participation,
        private RetraitXpService $retraitXpService,
        private DeletionLogContext $deletionLogContext,
    ) {
    }

    /**
     * @param bool $forcer vrai pour passer outre un bénéficiaire qui a déjà participé (motif obligatoire)
     *
     * @throws XpReportException
     */
    // @mago-expect lint:excessive-parameter-list
    public function reporter(
        Personnage $mort,
        Personnage $beneficiaire,
        string $mode,
        ?User $auteur,
        bool $forcer = false,
        ?string $motif = null,
    ): XpReport {
        $motif = null === $motif ? null : trim($motif);
        $this->controler($mort, $beneficiaire, $mode, $forcer, $motif);

        $montants = $this->calculateur->montants($mort);
        $xp = $montants[$mode];
        if ($xp <= 0) {
            throw new XpReportException(\sprintf('%s n\'a aucun XP à transmettre.', $mort->getIdentity()));
        }

        $gain = (new ExperienceGain())
            ->setExplanation(XpReportCalculateur::PREFIXE_REPORT . $mort->getIdentity(withId: false))
            ->setOperationDate(new DateTime())
            ->setXpGain($xp)
            ->setPersonnage($beneficiaire);
        $beneficiaire->addExperienceGain($gain);
        $beneficiaire->setXp($beneficiaire->getXp() + $xp);

        $report = (new XpReport())
            ->setPersonnageMort($mort)
            ->setPersonnageBeneficiaire($beneficiaire)
            ->setMode($mode)
            ->setXpBase($montants['base'])
            ->setXpReporte($xp)
            ->setExperienceGain($gain)
            ->setForce($forcer)
            ->setMotif('' === $motif ? null : $motif)
            ->setAuteur($auteur);

        $this->entityManager->persist($gain);
        $this->entityManager->persist($report);
        $this->journaliser(LogActionType::XP_REPORT, $auteur, [
            'mort_id' => $mort->getId(),
            'beneficiaire_id' => $beneficiaire->getId(),
            'mode' => $mode,
            'xp_base' => $montants['base'],
            'xp_reporte' => $xp,
            'force' => $forcer,
            'motif' => $motif,
        ]);
        $this->entityManager->flush();

        return $report;
    }

    /**
     * Annule un report : le gain est retiré (suppression logique), le solde corrigé, la ligne xp_report supprimée.
     * Le mort peut alors transmettre à nouveau et le bénéficiaire en recevoir un autre.
     *
     * @throws XpReportException
     */
    public function annuler(XpReport $report, ?User $auteur, string $motif): void
    {
        $motif = trim($motif);
        if ('' === $motif) {
            throw new XpReportException('Le motif de l\'annulation est obligatoire.');
        }

        $gain = $report->getExperienceGain();
        if (null !== $gain && !$gain->isDeleted()) {
            $this->retraitXpService->retirer($gain, RetraitXpService::MODE_SOFT, 'Annulation du report d\'XP : ' . $motif);
        }

        // L'entrée de log est écrite par DeletionLogListener à la suppression, enrichie ici.
        $this->deletionLogContext->annotate($report, LogActionType::XP_REPORT_ANNULE, [
            'mort_id' => $report->getPersonnageMort()->getId(),
            'beneficiaire_id' => $report->getPersonnageBeneficiaire()->getId(),
            'xp_retire' => $report->getXpReporte(),
            'motif' => $motif,
        ]);
        $this->entityManager->remove($report);
        $this->entityManager->flush();
    }

    /** @throws XpReportException */
    // @mago-expect lint:no-boolean-flag-parameter
    private function controler(Personnage $mort, Personnage $beneficiaire, string $mode, bool $forcer, ?string $motif): void
    {
        if (!\in_array($mode, [XpReportCalculateur::MODE_TIERS, XpReportCalculateur::MODE_MOITIE], strict: true)) {
            throw new XpReportException('Mode de report inconnu.');
        }
        if ($mort === $beneficiaire) {
            throw new XpReportException('Un personnage ne peut pas se transmettre son propre bonus.');
        }
        if ($mort->getVivant()) {
            throw new XpReportException(\sprintf('%s est vivant : son bonus ne peut être transmis qu\'après sa mort.', $mort->getIdentity()));
        }
        if (null !== $this->repository->findOneByMort($mort)) {
            throw new XpReportException(\sprintf('%s a déjà transmis son bonus d\'XP.', $mort->getIdentity()));
        }
        if (null !== $this->repository->findOneByBeneficiaire($beneficiaire)) {
            throw new XpReportException(\sprintf('%s a déjà reçu un bonus d\'XP.', $beneficiaire->getIdentity()));
        }
        if (!$beneficiaire->getVivant()) {
            throw new XpReportException(\sprintf('%s est mort : il ne peut pas recevoir de bonus.', $beneficiaire->getIdentity()));
        }

        // Le tiers reste dans le compte du joueur ; seul l'héritage (moitié) peut passer à un autre compte.
        if (XpReportCalculateur::MODE_TIERS === $mode) {
            $joueur = $mort->getUser();
            if (null === $joueur || $joueur !== $beneficiaire->getUser()) {
                throw new XpReportException('Le bonus du tiers ne peut aller qu\'à un personnage du même joueur.');
            }
        }

        if ($this->participation->aDejaParticipe($beneficiaire)) {
            if (!$forcer) {
                throw new XpReportException(\sprintf('%s a déjà participé à un GN : le bonus ne peut plus lui être ajouté sans forcer.', $beneficiaire->getIdentity()));
            }
            if (null === $motif || '' === $motif) {
                throw new XpReportException('Un motif est obligatoire pour forcer le report.');
            }
        }
    }

    /** @param array<string, mixed> $data */
    private function journaliser(LogActionType $type, ?User $auteur, array $data): void
    {
        $log = new LogAction();
        $log->setDate(new DateTime());
        $log->setUser($auteur);
        $log->setType($type);
        $log->setData(['data' => $data]);
        $this->entityManager->persist($log);
    }
}
