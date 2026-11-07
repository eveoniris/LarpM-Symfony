<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Rulebook;

use App\Entity\RulebookDomaine;
use App\Entity\RulebookRegle;
use App\Enum\RulebookStatut;
use App\Service\Rulebook\RulebookVerificateur;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class RulebookVerificateurTest extends TestCase
{
    /** @param list<string> $tests */
    private function regle(string $code, RulebookStatut $statut, array $tests = []): RulebookRegle
    {
        return (new RulebookRegle())
            ->setDomaine(new RulebookDomaine())
            ->setCode($code)
            ->setTitre($code)
            ->setStatut($statut)
            ->setTestsRefs($tests);
    }

    public function testRegleValideSansTestNiTagEstSignalee(): void
    {
        $resultat = (new RulebookVerificateur())->verifier(
            [
                $this->regle('XP-01', RulebookStatut::VALIDE),
                $this->regle('XP-02', RulebookStatut::BUGUE),
                $this->regle('XP-03', RulebookStatut::VALIDE),
                $this->regle('XP-04', RulebookStatut::VALIDE, ['XpTest::testQuatre*']),
            ],
            ['XpTest' => 'tests/Unit/XpTest.php'],
            ['XP-03' => ['tests/Unit/AutreTest.php']],
        );

        $this->assertSame(['XP-01'], $resultat->sansTest);
        $this->assertFalse($resultat->aDesErreurs());
    }

    public function testReferenceCasseeEtCodeInconnu(): void
    {
        $resultat = (new RulebookVerificateur())->verifier(
            [$this->regle('XP-01', RulebookStatut::VALIDE, ['Disparu::testX', 'tests/Unit/XpTest'])],
            ['XpTest' => 'tests/Unit/XpTest.php', 'tests/Unit/XpTest' => 'tests/Unit/XpTest.php'],
            ['ZZ-99' => ['tests/Unit/XpTest.php']],
        );

        $this->assertSame(['XP-01' => ['Disparu::testX']], $resultat->referencesCassees);
        $this->assertSame(['ZZ-99' => ['tests/Unit/XpTest.php']], $resultat->codesInconnus);
        $this->assertTrue($resultat->aDesErreurs());
    }

    public function testMotifAvecJoker(): void
    {
        $resultat = (new RulebookVerificateur())->verifier(
            [$this->regle('ORI-01', RulebookStatut::VALIDE, ['tests/Integration/OrigineBonus*Test.php', 'Rien*Test'])],
            ['tests/Integration/OrigineBonusListenerTest' => 'x'],
            [],
        );

        $this->assertSame(['ORI-01' => ['Rien*Test']], $resultat->referencesCassees);
    }
}
