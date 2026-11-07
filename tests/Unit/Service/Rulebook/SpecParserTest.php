<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Rulebook;

use App\Enum\RulebookStatut;
use App\Service\Rulebook\SpecParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class SpecParserTest extends TestCase
{
    private const string SPEC = <<<'MD'
        ---
        name: spec
        ---

        # Titre du fichier

        > Légende ignorée.

        ---

        ## Territoire — Richesse (`Territoire::getRichesse()`)

        ### Règle
        La richesse dépend du statut.

        ### Statut
        ⚠️ TODO — pour STABLE, les revenus sont ignorés.

        ### Tests
        → `TerritoireWealthTest::testRichesse*`

        ---

        ## Territoire — Défense (`Territoire::getDefense()`)

        ### Statut
        ✅ VALIDE

        ---

        ## Conditions — Évaluation

        ### Bug OR
        🐛 **BUGUÉ** — sans statut global.

        ---

        ## Capacité d'un groupe (`GroupeGn`)

        ### Statut
        🔒 HARDCODÉ

        ---

        ## Score d'initiative du domaine (règle reçue 2026-10-22) - À CONFIRMER, règles attendues

        ### Règle
        Texte.

        ### Statut
        ✅ VALIDE selon le fichier, mais le titre prime.

        ---

        ## Bonus d'origine figé (2026-10-19) - CODÉ sur `feat/x`, pas en prod

        Tests : `tests/Integration/OrigineTest.php`, `tests/Unit/AutreTest.php`.
        MD;

    public function testUneRegleParTitreDeNiveauDeux(): void
    {
        $regles = (new SpecParser())->parser(self::SPEC);

        $this->assertCount(6, $regles);
        $this->assertSame(
            ['Richesse', 'Défense', 'Évaluation', 'Capacité d\'un groupe', 'Score d\'initiative du domaine (règle reçue 2026-10-22)', 'Bonus d\'origine figé (2026-10-19)'],
            array_map(static fn ($r) => $r->titre, $regles),
        );
    }

    public function testDomaineVientDuTitreOuDeLaTableDesTitresSansTiret(): void
    {
        $regles = (new SpecParser())->parser(self::SPEC);

        $this->assertSame(['territoire', 'territoire', 'conditions', 'groupe', 'initiative', 'origine'], array_map(static fn ($r) => $r->domaineCode, $regles));
        $this->assertSame('Territoire', $regles[0]->domaineTitre);
    }

    /** @return array<string, array{int, RulebookStatut, bool}> */
    public static function statuts(): array
    {
        return [
            'todo dans la section statut' => [0, RulebookStatut::TODO, true],
            'valide' => [1, RulebookStatut::VALIDE, true],
            'sans section statut' => [2, RulebookStatut::A_CONFIRMER, false],
            'hardcode' => [3, RulebookStatut::HARDCODE, true],
            'a confirmer dans le titre prime' => [4, RulebookStatut::A_CONFIRMER, true],
            'sans section statut ni marqueur' => [5, RulebookStatut::A_CONFIRMER, false],
        ];
    }

    #[DataProvider('statuts')]
    public function testStatut(int $index, RulebookStatut $attendu, bool $detecte): void
    {
        $regle = (new SpecParser())->parser(self::SPEC)[$index];

        $this->assertSame($attendu, $regle->statut);
        $this->assertSame($detecte, $regle->statutDetecte);
    }

    public function testReferenceAuCodeDuTitreVaDansLeDetail(): void
    {
        $regle = (new SpecParser())->parser(self::SPEC)[0];

        $this->assertStringStartsWith('Référence : `Territoire::getRichesse()`', $regle->detail);
        $this->assertStringContainsString('La richesse dépend du statut.', $regle->detail);
        $this->assertStringContainsString('⚠️ TODO', $regle->detail);
    }

    public function testLeSeparateurFinalNEstPasDansLeDetail(): void
    {
        $this->assertStringEndsNotWith('---', (new SpecParser())->parser(self::SPEC)[0]->detail);
    }

    public function testTitreSansReferenceEntreCodeNEstPasCoupe(): void
    {
        $this->assertSame('Score d\'initiative du domaine (règle reçue 2026-10-22)', (new SpecParser())->parser(self::SPEC)[4]->titre);
    }

    public function testTestsSontExtraitsDeLaSectionEtDesLignesTests(): void
    {
        $regles = (new SpecParser())->parser(self::SPEC);

        $this->assertSame(['TerritoireWealthTest::testRichesse*'], $regles[0]->testsRefs);
        $this->assertSame(['tests/Integration/OrigineTest.php', 'tests/Unit/AutreTest.php'], $regles[5]->testsRefs);
        $this->assertSame([], $regles[1]->testsRefs);
    }

    public function testFichierSansRegleDonneUneListeVide(): void
    {
        $this->assertSame([], (new SpecParser())->parser("# Rien\n\nPas de section."));
    }
}
