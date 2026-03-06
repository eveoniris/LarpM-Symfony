<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\AttributeType;
use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\Langue;
use App\Entity\Level;
use App\Entity\Personnage;
use App\Entity\PersonnageLangues;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Sort;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Personnage anomalie detection methods.
 * Uses in-memory entity collections (no DB, no HTTP).
 */
#[Group('unit')]
class PersonnageAnomalieTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makePersonnage(): Personnage
    {
        return new Personnage();
    }

    private function makeLangue(string $label): Langue
    {
        $l = new Langue();
        $l->setLabel($label);

        return $l;
    }

    private function makePersonnageLangue(Langue $langue, string $source): PersonnageLangues
    {
        $pl = new PersonnageLangues();
        $pl->setLangue($langue);
        $pl->setSource($source);

        return $pl;
    }

    /**
     * Create a stub Competence with given attribute values.
     *
     * @param array<string, int> $attributes map of AttributeType label → value
     */
    private function makeCompetence(string $familyLabel, int $levelIndex, array $attributes = []): Competence
    {
        $family = $this->createStub(CompetenceFamily::class);
        $family->method('getLabel')->willReturn($familyLabel);

        $level = $this->createStub(Level::class);
        $level->method('getIndex')->willReturn($levelIndex);

        $competence = $this->createStub(Competence::class);
        $competence->method('getCompetenceFamily')->willReturn($family);
        $competence->method('getLevel')->willReturn($level);
        $competence->method('getAttributeValue')->willReturnCallback(static fn (string $key): mixed => $attributes[$key] ?? null);

        return $competence;
    }

    private function makePotion(int $niveau): Potion
    {
        $p = new Potion();
        $p->setNiveau($niveau);

        return $p;
    }

    private function makeSort(int $niveau): Sort
    {
        $s = new Sort();
        $s->setNiveau($niveau);

        return $s;
    }

    private function makePriere(int $niveau): Priere
    {
        $p = new Priere();
        $p->setNiveau($niveau);

        return $p;
    }

    // ── hasAnomalie() ─────────────────────────────────────────────────────────

    public function testHasAnomalieReturnsFalseForEmptyPersonnage(): void
    {
        $personnage = $this->makePersonnage();

        self::assertFalse($personnage->hasAnomalie());
    }

    public function testHasAnomalieReturnsTrueWhenLangueAnomalieExists(): void
    {
        $personnage = $this->makePersonnage();
        // 1 langue without ORIGINE source → 1 en trop (maxLangueConnue stays 0)
        $pl = $this->makePersonnageLangue($this->makeLangue('Commun'), 'MANUAL');
        $personnage->addPersonnageLangues($pl);

        self::assertTrue($personnage->hasAnomalie());
    }

    // ── getLanguesAnomaliesMessage() ──────────────────────────────────────────

    public function testNoLangueAnomalieWhenEmpty(): void
    {
        $personnage = $this->makePersonnage();

        self::assertSame('', $personnage->getLanguesAnomaliesMessage());
    }

    public function testNoLangueAnomalieWhenCountMatchesOriginSource(): void
    {
        $personnage = $this->makePersonnage();
        // 1 langue with ORIGINE source → maxLangueConnue=1, compteLangue=1 → no anomalie
        $pl = $this->makePersonnageLangue($this->makeLangue('Commun'), 'ORIGINE');
        $personnage->addPersonnageLangues($pl);

        self::assertSame('', $personnage->getLanguesAnomaliesMessage());
    }

    public function testLangueAnomalieWhenTooManyLangues(): void
    {
        $personnage = $this->makePersonnage();
        // 2 langues but only 1 has ORIGINE source → maxLangueConnue=1, compteLangue=2
        $personnage->addPersonnageLangues($this->makePersonnageLangue($this->makeLangue('Commun'), 'ORIGINE'));
        $personnage->addPersonnageLangues($this->makePersonnageLangue($this->makeLangue('Elfique'), 'MANUAL'));

        self::assertStringContainsString('en trop', $personnage->getLanguesAnomaliesMessage());
    }

    public function testLangueAnomalieWhenMissingLangues(): void
    {
        $personnage = $this->makePersonnage();
        // ORIGINE source but langue not added as actual langue → maxLangueConnue=1, compteLangue=0
        // We simulate: langue GROUPE gives maxLangueConnue but personnage has 0 actual langues
        // by adding a langue with GROUPE source but no actual language present in the count
        // → in practice: 0 langues but 1 ORIGINE-sourced entry that still increments max
        // Add one langue with ORIGINE source → compteLangue=1, maxLangueConnue=1 (balanced)
        // Then add competence giving +1 langue → maxLangueConnue=2 but compteLangue=1 → 1 manquante
        $pl = $this->makePersonnageLangue($this->makeLangue('Commun'), 'ORIGINE');
        $personnage->addPersonnageLangues($pl);

        // Competence that gives 1 extra langue slot
        $competence = $this->makeCompetence('Littérature', 1, [
            AttributeType::$LANGUE => 1,
        ]);
        $personnage->addCompetence($competence);

        // maxLangueConnue = 1 (ORIGINE) + 1 (competence) = 2, compteLangue = 1 → 1 manquante
        self::assertStringContainsString('manquante', $personnage->getLanguesAnomaliesMessage());
    }

    public function testLangueAncienneAnomalieWhenTooMany(): void
    {
        $personnage = $this->makePersonnage();
        // An "Ancien*" langue without competence → compteLangueAncienne=1, maxLangueAncienneConnue=0
        $pl = $this->makePersonnageLangue($this->makeLangue('Ancien Elfique'), 'MANUAL');
        $personnage->addPersonnageLangues($pl);

        self::assertStringContainsString('ancienne', $personnage->getLanguesAnomaliesMessage());
    }

    public function testDistinguishesCouranteFromAncienne(): void
    {
        $personnage = $this->makePersonnage();
        // Courante langue
        $pl1 = $this->makePersonnageLangue($this->makeLangue('Commun'), 'ORIGINE');
        // Ancienne langue without competence
        $pl2 = $this->makePersonnageLangue($this->makeLangue('Ancien Nain'), 'MANUAL');
        $personnage->addPersonnageLangues($pl1);
        $personnage->addPersonnageLangues($pl2);

        $msg = $personnage->getLanguesAnomaliesMessage();
        // compteLangue=1 vs maxLangueConnue=1 → balanced
        // compteLangueAncienne=1 vs maxLangueAncienneConnue=0 → 1 ancienne en trop
        self::assertStringContainsString('ancienne', $msg);
        self::assertStringNotContainsString('en trop à vérifier,', $msg); // courante is balanced
    }

    // ── getPotionAnomalieMessage() ────────────────────────────────────────────

    public function testNoPotionAnomalieWhenEmpty(): void
    {
        $personnage = $this->makePersonnage();

        self::assertSame('', $personnage->getPotionAnomalieMessage());
    }

    public function testPotionAnomalieWhenTooManyPotions(): void
    {
        $personnage = $this->makePersonnage();
        // 1 potion niveau 1, 0 expected → 1 en trop
        $personnage->addPotion($this->makePotion(1));

        self::assertStringContainsString('en trop', $personnage->getPotionAnomalieMessage());
    }

    public function testPotionAnomalieWhenMissingPotions(): void
    {
        $personnage = $this->makePersonnage();
        // Competence expects 2 potions niveau 1, personnage has 0
        $competence = $this->makeCompetence('Alchimie', 1, [
            AttributeType::$POTIONS[0] => 2,
        ]);
        $personnage->addCompetence($competence);

        self::assertStringContainsString('manquante', $personnage->getPotionAnomalieMessage());
    }

    public function testNoPotionAnomalieWhenCountMatchesExpected(): void
    {
        $personnage = $this->makePersonnage();
        // Competence expects 1 potion niveau 1
        $competence = $this->makeCompetence('Alchimie', 1, [
            AttributeType::$POTIONS[0] => 1,
        ]);
        $personnage->addCompetence($competence);
        // Personnage has exactly 1 potion niveau 1
        $personnage->addPotion($this->makePotion(1));

        self::assertSame('', $personnage->getPotionAnomalieMessage());
    }

    public function testPotionAnomalieChecksCorrectLevel(): void
    {
        $personnage = $this->makePersonnage();
        // Competence expects 1 potion at niveau 1 (satisfied) AND 1 at niveau 2 (missing)
        $competence = $this->makeCompetence('Alchimie', 2, [
            AttributeType::$POTIONS[0] => 1, // expects 1 at niveau 1 → balanced
            AttributeType::$POTIONS[1] => 1, // expects 1 at niveau 2 → missing
        ]);
        $personnage->addCompetence($competence);
        $personnage->addPotion($this->makePotion(1)); // satisfies niveau 1
        // No potion at niveau 2

        // niveau 2: expected=1, count=0 → manquante
        self::assertStringContainsString('manquante', $personnage->getPotionAnomalieMessage());
        self::assertStringContainsString('niveau 2', $personnage->getPotionAnomalieMessage());
    }

    public function testLitteratureApprentiSuppressesTropCheck(): void
    {
        $personnage = $this->makePersonnage();
        // Littérature Apprenti (level index=1) suppresses the "trop" check
        $competence = $this->makeCompetence(CompetenceFamily::$LITTERATURE, 1, []);
        $personnage->addCompetence($competence);

        // Add 2 extra potions with no competence requirement → normally would be "en trop"
        $personnage->addPotion($this->makePotion(1));
        $personnage->addPotion($this->makePotion(1));

        // With Littérature apprenti, no "en trop" message should appear
        self::assertStringNotContainsString('en trop', $personnage->getPotionAnomalieMessage());
    }

    // ── getSortAnomalieMessage() ──────────────────────────────────────────────

    public function testNoSortAnomalieWhenEmpty(): void
    {
        $personnage = $this->makePersonnage();

        self::assertSame('', $personnage->getSortAnomalieMessage());
    }

    public function testSortAnomalieWhenMissingSorts(): void
    {
        $personnage = $this->makePersonnage();
        $competence = $this->makeCompetence('Magie', 1, [
            AttributeType::$SORTS[0] => 2, // expects 2 sorts niveau 1
        ]);
        $personnage->addCompetence($competence);

        self::assertStringContainsString('manquant', $personnage->getSortAnomalieMessage());
    }

    public function testNoSortAnomalieWhenCountMatchesExpected(): void
    {
        $personnage = $this->makePersonnage();
        $competence = $this->makeCompetence('Magie', 1, [
            AttributeType::$SORTS[0] => 1,
        ]);
        $personnage->addCompetence($competence);
        $personnage->addSort($this->makeSort(1));

        self::assertSame('', $personnage->getSortAnomalieMessage());
    }

    // ── getPrieresAnomalieMessage() ───────────────────────────────────────────

    public function testNoPriereAnomalieWhenEmpty(): void
    {
        $personnage = $this->makePersonnage();

        self::assertSame('', $personnage->getPrieresAnomalieMessage());
    }

    public function testPriereAnomalieWhenTooManyPrieres(): void
    {
        $personnage = $this->makePersonnage();
        // 1 prière niveau 1, 0 expected → 1 en trop
        $personnage->addPriere($this->makePriere(1));

        self::assertStringContainsString('en trop', $personnage->getPrieresAnomalieMessage());
    }

    public function testPriereAnomalieWhenMissingPrieres(): void
    {
        $personnage = $this->makePersonnage();
        $competence = $this->makeCompetence('Prêtrise', 1, [
            AttributeType::$PRIERES[0] => 1,
        ]);
        $personnage->addCompetence($competence);

        self::assertStringContainsString('manquant', $personnage->getPrieresAnomalieMessage());
    }

    public function testNoPriereAnomalieWhenCountMatchesExpected(): void
    {
        $personnage = $this->makePersonnage();
        $competence = $this->makeCompetence('Prêtrise', 1, [
            AttributeType::$PRIERES[0] => 1,
        ]);
        $personnage->addCompetence($competence);
        $personnage->addPriere($this->makePriere(1));

        self::assertSame('', $personnage->getPrieresAnomalieMessage());
    }

    public function testPriereAnomaliePerLevelDetection(): void
    {
        $personnage = $this->makePersonnage();
        // Expects 1 prière niveau 2, has 0
        $competence = $this->makeCompetence('Prêtrise', 2, [
            AttributeType::$PRIERES[1] => 1, // niveau 2
        ]);
        $personnage->addCompetence($competence);
        $personnage->addPriere($this->makePriere(1)); // wrong level — surplus at niveau 1

        // niveau 1: expected=0, count=1 → en trop
        self::assertStringContainsString('niveau 1', $personnage->getPrieresAnomalieMessage());
    }
}
