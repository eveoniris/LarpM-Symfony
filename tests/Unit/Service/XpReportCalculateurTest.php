<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\ExperienceGain;
use App\Entity\Personnage;
use App\Service\XpReportCalculateur;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('rulebook')]
#[Group('unit')]
final class XpReportCalculateurTest extends TestCase
{
    private function personnage(array $gains): Personnage
    {
        $personnage = new Personnage();
        foreach ($gains as [$libelle, $xp]) {
            $personnage->addExperienceGain(
                (new ExperienceGain())
                    ->setExplanation($libelle)
                    ->setXpGain($xp)
                    ->setPersonnage($personnage),
            );
        }

        return $personnage;
    }

    public function testExempleDuLivreCree17Mort25DonneTrois(): void
    {
        $calculateur = new XpReportCalculateur();
        $defunt = $this->personnage([
            [XpReportCalculateur::LIBELLE_CREATION, 17],
            ['Participation au GN 1',               8],
        ]);

        static::assertSame(8, $calculateur->base($defunt));
        static::assertSame(3, $calculateur->montant(8, XpReportCalculateur::MODE_TIERS));
        static::assertSame(4, $calculateur->montant(8, XpReportCalculateur::MODE_MOITIE));
    }

    public function testLaBaseExclutCreationAgeNaissanceSuppressionEtReports(): void
    {
        $calculateur = new XpReportCalculateur();
        $defunt = $this->personnage([
            [XpReportCalculateur::LIBELLE_CREATION, 17],
            [XpReportCalculateur::LIBELLE_BONUS_AGE, 2],
            [XpReportCalculateur::LIBELLE_NAISSANCE_HERITIER, 10],
            [XpReportCalculateur::PREFIXE_REPORT . 'Conan', 5],
            ['Suppression de la compétence Combat', -3],
            ['Participation', 2],
            ['XP post mortem', 4],
            ['Inter-jeu', 3],
        ]);

        static::assertSame(9, $calculateur->base($defunt));
        static::assertSame(['base' => 9, 'tiers' => 3, 'moitie' => 5], $calculateur->montants($defunt));
    }

    public function testBaseNegativeRamenee(): void
    {
        $calculateur = new XpReportCalculateur();
        $defunt = $this->personnage([['Sanction', -4]]);

        static::assertSame(0, $calculateur->base($defunt));
        static::assertSame(0, $calculateur->montant(0, XpReportCalculateur::MODE_TIERS));
    }

    /** @return iterable<string, array{int, int, int}> */
    public static function arrondis(): iterable
    {
        yield 'zéro' => [0, 0, 0];
        yield 'un' => [1, 1, 1];
        yield 'deux' => [2, 1, 1];
        yield 'trois' => [3, 1, 2];
        yield 'quatre' => [4, 2, 2];
        yield 'six' => [6, 2, 3];
        yield 'sept' => [7, 3, 4];
        yield 'huit' => [8, 3, 4];
    }

    #[DataProvider('arrondis')]
    public function testArrondiAuSuperieur(int $base, int $tiers, int $moitie): void
    {
        $calculateur = new XpReportCalculateur();

        static::assertSame($tiers, $calculateur->montant($base, XpReportCalculateur::MODE_TIERS));
        static::assertSame($moitie, $calculateur->montant($base, XpReportCalculateur::MODE_MOITIE));
    }

    public function testModeInconnuRefuse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new XpReportCalculateur())->montant(5, 'quart');
    }
}
