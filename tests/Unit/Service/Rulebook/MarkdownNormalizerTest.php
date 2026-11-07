<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Rulebook;

use App\Service\Rulebook\MarkdownNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

// @mago-expect lint:too-many-methods
#[Group('unit')]
class MarkdownNormalizerTest extends TestCase
{
    private MarkdownNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new MarkdownNormalizer();
    }

    public function testTexteProprePasModifie(): void
    {
        $resultat = $this->normalizer->normaliser("# Titre\n\nUn paragraphe avec une liste :\n\n- un\n- deux");

        $this->assertSame("# Titre\n\nUn paragraphe avec une liste :\n\n- un\n- deux", $resultat->texte);
        $this->assertSame(0, $resultat->nettoyes);
        $this->assertFalse($resultat->aEteModifie());
    }

    /** @return array<string, array{string, string, int}> */
    public static function caracteresIndesirables(): array
    {
        return [
            'espace largeur nulle' => ["a\u{200B}b", 'ab', 1],
            'marques de direction' => ["a\u{200E}b\u{200F}c", 'abc', 2],
            'BOM' => ["\u{FEFF}Titre", 'Titre', 1],
            'trait d\'union conditionnel' => ["con\u{00AD}tre", 'contre', 1],
            'espace insécable' => ["Coût\u{00A0}: 3", 'Coût : 3', 1],
            'espace fine insécable' => ["10\u{202F}%", '10 %', 1],
            'espace cadratin' => ["a\u{2003}b", 'a b', 1],
            'caractère de contrôle' => ["a\x01b\x7Fc", 'abc', 2],
            'contrôle C1' => ["a\u{0090}b", 'ab', 1],
            'separateur de paragraphe' => ["a\u{2029}b", "a\nb", 1],
        ];
    }

    #[DataProvider('caracteresIndesirables')]
    public function testNettoieLesCaracteresIndesirables(string $entree, string $attendu, int $compte): void
    {
        $resultat = $this->normalizer->normaliser($entree);

        $this->assertSame($attendu, $resultat->texte);
        $this->assertSame($compte, $resultat->nettoyes);
        $this->assertTrue($resultat->aEteModifie());
    }

    public function testGuillemetsEtApostrophesTypographiquesConserves(): void
    {
        $texte = '« Le roi » dit l’archiviste, “très bien”.';

        $this->assertSame($texte, $this->normalizer->normaliser($texte)->texte);
        $this->assertSame(0, $this->normalizer->normaliser($texte)->nettoyes);
    }

    public function testTabulationEtSautsDeLigneConserves(): void
    {
        $this->assertSame("a\tb\nc", $this->normalizer->normaliser("a\tb\nc")->texte);
    }

    public function testFinsDeLigneUniformisees(): void
    {
        $resultat = $this->normalizer->normaliser("a\r\nb\rc");

        $this->assertSame("a\nb\nc", $resultat->texte);
        $this->assertSame(0, $resultat->nettoyes);
    }

    public function testEspacesEnFinDeLigneEtLignesVidesAuxExtremites(): void
    {
        $this->assertSame("a\nb", $this->normalizer->normaliser("\n\na  \nb \t\n\n")->texte);
    }

    public function testUnicodeNormaliseEnNfc(): void
    {
        // "é" décomposé (e + accent combinant) devient un seul caractère.
        $this->assertSame("\u{00E9}", $this->normalizer->normaliser("e\u{0301}")->texte);
    }

    public function testOctetsUtf8InvalidesRetires(): void
    {
        $resultat = $this->normalizer->normaliser("a\xC3\x28b");

        $this->assertSame('a(b', $resultat->texte);
        $this->assertGreaterThan(0, $resultat->nettoyes);
    }

    public function testNormalisationIdempotente(): void
    {
        $sale = "\u{FEFF}Coût\u{00A0}: 3\u{200B}  \r\n\r\n- a\u{2003}b";
        $premier = $this->normalizer->normaliser($sale);
        $second = $this->normalizer->normaliser($premier->texte);

        $this->assertSame($premier->texte, $second->texte);
        $this->assertSame(0, $second->nettoyes);
    }
}
