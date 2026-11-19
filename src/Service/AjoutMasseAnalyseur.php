<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\AjoutMasseType;
use App\Repository\PersonnageRepository;

final readonly class AjoutMasseAnalyseur
{
    public function __construct(
        private PersonnageRepository $personnageRepository,
    ) {
    }

    /** @param list<int> $ids */
    public function analyser(AjoutMasseType $type, array $ids): AjoutMasseAnalyse
    {
        $trouves = [];
        foreach ($this->personnageRepository->findByIds($ids) as $personnage) {
            $trouves[$personnage->getId()] = $personnage;
        }

        $applicables = [];
        $morts = [];
        $introuvables = [];
        foreach ($ids as $id) {
            $personnage = $trouves[$id] ?? null;
            if (null === $personnage) {
                $introuvables[] = $id;
                continue;
            }
            if ($type->distingueLesMorts() && !$personnage->getVivant()) {
                $morts[] = $personnage;
                continue;
            }
            $applicables[] = $personnage;
        }

        return new AjoutMasseAnalyse($applicables, $morts, $introuvables);
    }
}
