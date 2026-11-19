<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AccesRefuseJournal;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AccesRefuseJournalTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/acces_' . uniqid(more_entropy: true);
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        $fichier = $this->dir . '/' . AccesRefuseJournal::FILE_NAME;
        if (is_file($fichier)) {
            unlink($fichier);
        }
        rmdir($this->dir);
    }

    public function testPurgeSupprimeLesLignesAnciennesSeulement(): void
    {
        $fichier = $this->dir . '/' . AccesRefuseJournal::FILE_NAME;
        $ancienne = (new DateTimeImmutable('-40 days'))->format(\DATE_ATOM);
        $recente = (new DateTimeImmutable('-2 days'))->format(\DATE_ATOM);
        file_put_contents($fichier, json_encode(['date' => $ancienne, 'url' => '/a']) . "\n" . json_encode(['date' => $recente, 'url' => '/b']) . "\nnon json\n");
        $journal = new AccesRefuseJournal($this->dir);
        $limite = new DateTimeImmutable('-31 days');

        static::assertSame(1, $journal->compter($limite));
        static::assertStringContainsString('/a', (string) file_get_contents($fichier));

        static::assertSame(1, $journal->purger($limite));
        $contenu = (string) file_get_contents($fichier);
        static::assertStringNotContainsString('/a', $contenu);
        static::assertStringContainsString('/b', $contenu);
        static::assertStringContainsString('non json', $contenu);
    }

    public function testEcritureAjouteUneLigneJson(): void
    {
        $journal = new AccesRefuseJournal($this->dir);
        $journal->ecrire(['url' => '/x', 'user_id' => 3]);

        $ligne = json_decode(trim((string) file_get_contents($this->dir . '/' . AccesRefuseJournal::FILE_NAME)), associative: true);
        static::assertSame('/x', $ligne['url']);
        static::assertArrayHasKey('date', $ligne);
    }
}
