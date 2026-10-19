<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Personnage;
use App\Enum\OrigineBonusRepriseMode;
use App\Enum\OrigineBonusRepriseStatut;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Reprise des personnages existants pour le bonus d'origine figé.
 * Transitoire : à retirer avec l'ancien calcul une fois la reprise appliquée en production.
 */
class OrigineBonusReprise
{
    private const int LOT = 200;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrigineBonusService $service,
    ) {
    }

    /**
     * Analyse (et, selon le mode, enregistre) tous les personnages ayant au moins une participation.
     *
     * @return array{counts: array<string, int>, douteux: list<array{int, string, string, string, string, string}>, appliques: int}
     */
    public function executer(OrigineBonusRepriseMode $mode): array
    {
        $result = ['counts' => array_fill_keys(OrigineBonusRepriseStatut::getValues(), value: 0), 'douteux' => [], 'appliques' => 0];

        foreach (array_chunk($this->ids(), self::LOT) as $lot) {
            foreach ($this->entityManager->getRepository(Personnage::class)->findBy(['id' => $lot]) as $personnage) {
                $this->traiter($personnage, $mode, $result);
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        return $result;
    }

    public function statut(Personnage $personnage): OrigineBonusRepriseStatut
    {
        if ($this->service->hasDecision($personnage)) {
            return OrigineBonusRepriseStatut::DEJA;
        }

        $origine = $personnage->getOrigine();
        if (null === $origine || [] === $this->service->getNativeBonus($origine)) {
            return OrigineBonusRepriseStatut::SANS_BONUS;
        }

        return match ($this->service->isEligible($personnage)) {
            null => OrigineBonusRepriseStatut::DIFFERE,
            true => OrigineBonusRepriseStatut::ACQUIS,
            false => OrigineBonusRepriseStatut::REFUSE,
        };
    }

    /**
     * La nouvelle règle (fiefs possédés) donne un autre résultat que l'ancien calcul (territoire de référence du groupe).
     */
    public function isDouteux(Personnage $personnage): bool
    {
        return $this->service->isEligible($personnage) !== $personnage->isOrigineBonusActiveLegacy();
    }

    /**
     * @param array{counts: array<string, int>, douteux: list<array{int, string, string, string, string, string}>, appliques: int} $result
     */
    private function traiter(Personnage $personnage, OrigineBonusRepriseMode $mode, array &$result): void
    {
        $statut = $this->statut($personnage);
        ++$result['counts'][$statut->value];
        if (!$statut->isDecidable()) {
            return;
        }

        $douteux = $this->isDouteux($personnage);
        if ($douteux) {
            $result['douteux'][] = $this->ligne($personnage, $statut);
        }

        if ($mode->isApply() && !($douteux && OrigineBonusRepriseMode::SANS_DOUTEUX === $mode)) {
            $this->service->grant($personnage);
            ++$result['appliques'];
        }
    }

    /**
     * @return list<int>
     */
    private function ids(): array
    {
        return $this->entityManager
            ->createQuery('SELECT DISTINCT p.id FROM ' . Personnage::class . ' p INNER JOIN p.participants pa ORDER BY p.id')
            ->getSingleColumnResult();
    }

    /**
     * @return array{int, string, string, string, string, string}
     */
    private function ligne(Personnage $personnage, OrigineBonusRepriseStatut $statut): array
    {
        return [
            $personnage->getId(),
            $personnage->getNom(),
            $personnage->getOrigine()?->getNom() ?? '',
            $personnage->getFirstParticipantGnGroupe()?->getNom() ?? '',
            $personnage->isOrigineBonusActiveLegacy() ? 'actif' : 'inactif',
            OrigineBonusRepriseStatut::ACQUIS === $statut ? 'acquis' : 'refusé',
        ];
    }
}
