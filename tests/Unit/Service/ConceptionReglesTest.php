<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Enum\ConceptionIssue;
use App\Service\ConceptionRegles;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

// @mago-expect lint:too-many-methods
#[Group('unit')]
class ConceptionReglesTest extends TestCase
{
    private ConceptionRegles $regles;

    protected function setUp(): void
    {
        $this->regles = new ConceptionRegles();
    }

    /**
     * @return iterable<string, array{0: int, 1: ConceptionIssue}>
     */
    public static function tirages(): iterable
    {
        yield '1 : conséquence pour les parents' => [1, ConceptionIssue::CONSEQUENCE_PARENTS];
        yield '2 : échec' => [2, ConceptionIssue::ECHEC];
        yield '4 : échec' => [4, ConceptionIssue::ECHEC];
        yield '5 : fille' => [5, ConceptionIssue::FILLE];
        yield '9 : fille' => [9, ConceptionIssue::FILLE];
        yield '10 : fille ou garçon' => [10, ConceptionIssue::FILLE_OU_GARCON];
        yield '11 : garçon' => [11, ConceptionIssue::GARCON];
        yield '15 : garçon' => [15, ConceptionIssue::GARCON];
        yield '16 : genre au choix' => [16, ConceptionIssue::GENRE_AU_CHOIX];
        yield '19 : genre au choix' => [19, ConceptionIssue::GENRE_AU_CHOIX];
        yield '20 : jumeaux' => [20, ConceptionIssue::JUMEAUX];
    }

    #[DataProvider('tirages')]
    public function testIssueDuTirage(int $tirage, ConceptionIssue $attendue): void
    {
        static::assertSame($attendue, $this->regles->issue($tirage));
    }

    public function testChaqueTirageDe1A20DonneUneIssue(): void
    {
        $issues = [];
        foreach (range(ConceptionRegles::TIRAGE_MIN, ConceptionRegles::TIRAGE_MAX) as $tirage) {
            $issues[$this->regles->issue($tirage)->value] = true;
        }

        static::assertCount(\count(ConceptionIssue::cases()), $issues);
    }

    /**
     * @return iterable<string, array{0: int}>
     */
    public static function tiragesInvalides(): iterable
    {
        yield 'zéro' => [0];
        yield '21' => [21];
        yield 'négatif' => [-3];
    }

    #[DataProvider('tiragesInvalides')]
    public function testUnTirageHorsDeLaTableEstRefuse(int $tirage): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->regles->issue($tirage);
    }

    public function testLeTirageUtiliseLaSourceInjecteeEtSesBornes(): void
    {
        $bornes = [];
        $tirage = $this->regles->tirer(static function (int $min, int $max) use (&$bornes): int {
            $bornes = [$min, $max];

            return 13;
        });

        static::assertSame(13, $tirage);
        static::assertSame([1, 20], $bornes);
    }

    public function testLeTirageAleatoireResteDansLaTable(): void
    {
        for ($i = 0; $i < 200; ++$i) {
            $tirage = $this->regles->tirer();
            static::assertGreaterThanOrEqual(1, $tirage);
            static::assertLessThanOrEqual(20, $tirage);
        }
    }

    public function testUneSourceQuiSortDeLaTableEstRefusee(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->regles->tirer(static fn (int $min, int $max): int => 0);
    }

    public function testNombreDHeritiersSelonLIssue(): void
    {
        static::assertSame(0, ConceptionIssue::CONSEQUENCE_PARENTS->nombreHeritiers());
        static::assertSame(0, ConceptionIssue::ECHEC->nombreHeritiers());
        static::assertSame(1, ConceptionIssue::GARCON->nombreHeritiers());
        static::assertSame(1, ConceptionIssue::FILLE_OU_GARCON->nombreHeritiers());
        static::assertSame(1, ConceptionIssue::GENRE_AU_CHOIX->nombreHeritiers());
        static::assertSame(2, ConceptionIssue::JUMEAUX->nombreHeritiers());
        static::assertTrue(ConceptionIssue::JUMEAUX->estReussite());
        static::assertFalse(ConceptionIssue::ECHEC->estReussite());
    }

    public function testOnNePeutPlusConcevoirDes45AnsAgeMur(): void
    {
        static::assertTrue($this->regles->ageAutorise(15));
        static::assertTrue($this->regles->ageAutorise(44));
        static::assertFalse($this->regles->ageAutorise(45));
        static::assertFalse($this->regles->ageAutorise(70));
        static::assertTrue($this->regles->ageAutorise(null));
    }

    public function testLeMessageDAgeNommeLeParentEtLaLimite(): void
    {
        static::assertNull($this->regles->anomalieAge('Conan', 30));
        static::assertSame('Conan a 47 ans : on ne peut plus concevoir à partir de 45 ans (âge mûr).', $this->regles->anomalieAge('Conan', 47));
    }

    public function testUnPereEtUneMereSontNecessaires(): void
    {
        static::assertNull($this->regles->anomalieGenres('Conan', 1, 'Belit', 2));
        static::assertNull($this->regles->anomalieGenres('Belit', 2, 'Conan', 1));
        static::assertStringContainsString('même genre', (string) $this->regles->anomalieGenres('Conan', 1, 'Bêlit', 1));
        static::assertStringContainsString('même genre', (string) $this->regles->anomalieGenres('A', 2, 'B', 2));
    }

    public function testUnGenreAmbiguOuInconnuDoitEtreRenseigne(): void
    {
        static::assertStringContainsString('Conan', (string) $this->regles->anomalieGenres('Conan', 3, 'Belit', 2));
        static::assertStringContainsString('à vérifier et à renseigner', (string) $this->regles->anomalieGenres('Conan', 1, 'Belit', 3));
        static::assertNotNull($this->regles->anomalieGenres('Conan', null, 'Belit', 2));
    }

    public function testSeulsLesHumainsEtLesOmbrelinsPeuventConcevoir(): void
    {
        static::assertNull($this->regles->anomalieEspece('Conan', []));
        // La fiche attribue d'office l'espèce « Humain » à un personnage sans espèce : elle ne doit jamais bloquer.
        static::assertNull($this->regles->anomalieEspece('Conan', ['Humain']));
        static::assertNull($this->regles->anomalieEspece('Conan', [' humain ']));
        static::assertNull($this->regles->anomalieEspece('Conan', ['Humain', 'Ombrelin']));
        static::assertStringContainsString('Nain', (string) $this->regles->anomalieEspece('Conan', ['Humain', 'Nain']));
        static::assertNull($this->regles->anomalieEspece('Conan', ['Ombrelin']));
        static::assertNull($this->regles->anomalieEspece('Conan', [' OMBRELIN ']));
        static::assertStringContainsString('Nain', (string) $this->regles->anomalieEspece('Conan', ['Nain']));
        static::assertStringContainsString('Nain', (string) $this->regles->anomalieEspece('Conan', ['Ombrelin', 'Nain']));
    }
}
