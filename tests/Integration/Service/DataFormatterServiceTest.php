<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Service\DataFormatterService;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Le formateur alimente le journal admin, affiché en |raw : les données saisies doivent y être échappées.
 */
#[Group('integration')]
class DataFormatterServiceTest extends KernelTestCase
{
    public function testLesValeursSaisiesSontEchappees(): void
    {
        self::bootKernel();
        $formateur = static::getContainer()->get(DataFormatterService::class);

        $html = $formateur->format([
            'nom' => '<script>alert(1)</script>',
            '<b>cle</b>' => 'x',
            'details' => ['description' => '<img src=x onerror=alert(1)>'],
        ]);

        static::assertStringNotContainsString('<script>', $html);
        static::assertStringNotContainsString('<img', $html);
        static::assertStringNotContainsString('<b>', $html);
        static::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }
}
