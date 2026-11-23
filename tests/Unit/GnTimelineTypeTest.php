<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Enum\GnTimelineType;
use PHPUnit\Framework\TestCase;

final class GnTimelineTypeTest extends TestCase
{
    public function testSeulLInfoEstSansAction(): void
    {
        $this->assertFalse(GnTimelineType::INFO->estAction());
        $this->assertTrue(GnTimelineType::XP_PARTICIPATION->estAction());
        $this->assertTrue(GnTimelineType::VERROUILLAGE->estAction());
        $this->assertTrue(GnTimelineType::VIEILLISSEMENT->estAction());
    }

    public function testEtapesLourdesEtTechniques(): void
    {
        foreach ([GnTimelineType::XP_PARTICIPATION, GnTimelineType::VERROUILLAGE, GnTimelineType::VIEILLISSEMENT] as $type) {
            $this->assertTrue($type->estLourde(), $type->value);
        }
        foreach ([GnTimelineType::INFO, GnTimelineType::VERROUILLAGE_STRICT, GnTimelineType::DEVERROUILLAGE] as $type) {
            $this->assertFalse($type->estLourde(), $type->value);
        }
        $this->assertTrue(GnTimelineType::VERROUILLAGE_STRICT->estTechnique());
        $this->assertTrue(GnTimelineType::DEVERROUILLAGE->estTechnique());
        $this->assertFalse(GnTimelineType::VERROUILLAGE->estTechnique());
        $this->assertTrue(GnTimelineType::DEVERROUILLAGE->autoAutorise());
    }

    public function testSeulLInfoNEstJamaisAutomatique(): void
    {
        $this->assertTrue(GnTimelineType::VIEILLISSEMENT->autoAutorise());
        $this->assertFalse(GnTimelineType::INFO->autoAutorise());
        $this->assertTrue(GnTimelineType::XP_PARTICIPATION->autoAutorise());
        $this->assertTrue(GnTimelineType::VERROUILLAGE->autoAutorise());
    }
}
