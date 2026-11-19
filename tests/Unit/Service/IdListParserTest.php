<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\IdListParser;
use PHPUnit\Framework\TestCase;

final class IdListParserTest extends TestCase
{
    public function testVirgulesRetoursLigneEtDoublons(): void
    {
        static::assertSame([12, 45, 7, 8], (new IdListParser())->parse("12, 45;7\r\n8\n12\n"));
    }

    public function testIgnoreLeBruit(): void
    {
        static::assertSame([3], (new IdListParser())->parse('abc, 0, -4, 1.5, 3, '));
        static::assertSame([], (new IdListParser())->parse('   '));
    }
}
