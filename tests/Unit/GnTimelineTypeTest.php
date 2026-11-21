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

    public function testLeVieillissementEtLInfoNeSontJamaisAutomatiques(): void
    {
        $this->assertFalse(GnTimelineType::VIEILLISSEMENT->autoAutorise());
        $this->assertFalse(GnTimelineType::INFO->autoAutorise());
        $this->assertTrue(GnTimelineType::XP_PARTICIPATION->autoAutorise());
        $this->assertTrue(GnTimelineType::VERROUILLAGE->autoAutorise());
    }
}
