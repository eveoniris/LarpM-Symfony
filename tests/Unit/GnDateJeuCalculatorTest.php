<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Gn;
use App\Service\GnDateJeuCalculator;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class GnDateJeuCalculatorTest extends TestCase
{
    private function gn(string $debut, string $fin, int $anneeJeu): Gn
    {
        return (new Gn())
            ->setDateDebut(new DateTime($debut))
            ->setDateFin(new DateTime($fin))
            ->setDateJeu($anneeJeu);
    }

    public function testInterpolationEntreDeuxGn(): void
    {
        $a = $this->gn('2024-08-01', '2024-08-04', 1300);
        $b = $this->gn('2026-08-01', '2026-08-04', 1305);

        // Milieu de l'inter-jeu : environ 2 ans et demi de jeu écoulés, donc environ 1302-1303.
        $r = (new GnDateJeuCalculator())->calculer([$a, $b], $a, $b, null, new DateTimeImmutable('2025-08-02'));

        $this->assertFalse($r['estimation']);
        $this->assertEqualsWithDelta(1302.5, $r['annee'], 0.05);
    }

    public function testTempsDeJeuLimiteAUnePeriode(): void
    {
        $a = $this->gn('2024-08-01', '2024-08-04', 1300);
        $b = $this->gn('2026-08-01', '2026-08-04', 1305);
        $b->setDateTempsJeuDebut(new DateTime('2025-01-01'))->setDateTempsJeuFin(new DateTime('2025-12-31'));

        $avant = (new GnDateJeuCalculator())->calculer([$a, $b], $a, $b, null, new DateTimeImmutable('2024-12-01'));
        $apres = (new GnDateJeuCalculator())->calculer([$a, $b], $a, $b, null, new DateTimeImmutable('2026-03-01'));

        $this->assertEqualsWithDelta(1300.0, $avant['annee'], 0.001);
        $this->assertEqualsWithDelta(1305.0, $apres['annee'], 0.001);
    }

    public function testSansGnSuivantOnEstime(): void
    {
        $a = $this->gn('2024-08-01', '2024-08-04', 1300);

        $r = (new GnDateJeuCalculator())->calculer([$a], $a, null, null, new DateTimeImmutable('2026-06-01'));

        $this->assertTrue($r['estimation']);
        $this->assertStringStartsWith('environ année ', $r['libelle']);
    }

    public function testPendantUnGnOnPrendSonAnnee(): void
    {
        $a = $this->gn('2026-08-01', '2026-08-04', 1305);

        $r = (new GnDateJeuCalculator())->calculer([$a], null, null, $a, new DateTimeImmutable('2026-08-02'));

        $this->assertSame(1305.0, $r['annee']);
    }
}
