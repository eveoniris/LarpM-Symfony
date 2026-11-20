<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\GnChronologieService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GnChronologieServiceTest extends TestCase
{
    /** @return iterable<string, array{int, string}> */
    public static function niveaux(): iterable
    {
        yield 'loin' => [60, 'info'];
        yield 'veille du seuil jaune' => [15, 'info'];
        yield 'seuil jaune' => [14, 'warn'];
        yield 'avant le rouge' => [4, 'warn'];
        yield 'seuil rouge' => [3, 'err'];
        yield 'jour J' => [0, 'err'];
    }

    #[DataProvider('niveaux')]
    public function testNiveauSelonLesJoursRestants(int $jours, string $attendu): void
    {
        $this->assertSame($attendu, GnChronologieService::niveau($jours));
    }
}
