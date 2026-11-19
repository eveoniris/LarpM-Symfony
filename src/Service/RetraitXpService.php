<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ExperienceGain;
use App\Enum\LogActionType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Retire un gain d'XP de l'historique d'un personnage et corrige son solde.
 *
 * Mode soft : la ligne reste (barrée dans l'historique admin) mais n'est plus comptée.
 * Mode hard : la ligne est supprimée. Dans les deux cas le retrait est tracé dans log_action
 * (DeletionLogListener) avec le motif et le mode.
 */
final readonly class RetraitXpService
{
    public const string MODE_SOFT = 'soft';
    public const string MODE_HARD = 'hard';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DeletionLogContext $deletionLogContext,
    ) {
    }

    /** @return bool false si le gain était déjà retiré ou si le mode est inconnu */
    public function retirer(ExperienceGain $gain, string $mode, string $motif): bool
    {
        if ($gain->isDeleted() || !\in_array($mode, [self::MODE_SOFT, self::MODE_HARD], strict: true)) {
            return false;
        }

        $personnage = $gain->getPersonnage();
        $personnage->setXp($personnage->getXp() - $gain->getXpGain());

        $this->deletionLogContext->annotate($gain, LogActionType::XP_RETRAIT, [
            'mode' => $mode,
            'motif' => $motif,
            'xp_retire' => $gain->getXpGain(),
            'personnage_id' => $personnage->getId(),
        ]);

        self::MODE_HARD === $mode ? $this->entityManager->remove($gain) : $gain->setDeletedAt(new DateTime());
        $this->entityManager->flush();

        return true;
    }
}
