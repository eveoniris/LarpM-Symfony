<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\RulebookRegle;
use App\Entity\RulebookRevision;
use App\Enum\RulebookStatut;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class RulebookRegleTest extends TestCase
{
    public function testUneRegleNeuveEstUnBrouillonInvisibleDesScenaristes(): void
    {
        $regle = new RulebookRegle();

        $this->assertSame(RulebookStatut::A_CONFIRMER, $regle->getStatut());
        $this->assertFalse($regle->isPubliee());
        $this->assertSame(1, $regle->getVersion());
        $this->assertFalse($regle->isVisibleScenariste());
    }

    public function testStatutsPublics(): void
    {
        $this->assertTrue(RulebookStatut::VALIDE->isPublic());
        $this->assertTrue(RulebookStatut::FIXE->isPublic());
        $this->assertTrue(RulebookStatut::HARDCODE->isPublic());
        $this->assertFalse(RulebookStatut::BUGUE->isPublic());
        $this->assertFalse(RulebookStatut::TODO->isPublic());
        $this->assertFalse(RulebookStatut::A_CONFIRMER->isPublic());
    }

    public function testTestsRefsEstReindexe(): void
    {
        $regle = (new RulebookRegle())->setTestsRefs([3 => 'tests/A.php', 7 => 'tests/B.php']);

        $this->assertSame(['tests/A.php', 'tests/B.php'], $regle->getTestsRefs());
    }

    public function testRevisionCapturaLEtatCourant(): void
    {
        $regle = (new RulebookRegle())
            ->setCode('XP-03')
            ->setTitre('Coût d\'une compétence')
            ->setTexteClair('Clair.')
            ->setDetailTechnique('Formule.')
            ->setStatut(RulebookStatut::VALIDE)
            ->setPubliee(true)
            ->setVersion(4);

        $revision = RulebookRevision::depuis($regle, null, 'Correction du coût');

        $this->assertSame($regle, $revision->getRegle());
        $this->assertSame(4, $revision->getVersion());
        $this->assertSame('Clair.', $revision->getTexteClair());
        $this->assertSame('Formule.', $revision->getDetailTechnique());
        $this->assertSame(RulebookStatut::VALIDE, $revision->getStatut());
        $this->assertTrue($revision->isPubliee());
        $this->assertSame('Correction du coût', $revision->getMotif());
    }
}
