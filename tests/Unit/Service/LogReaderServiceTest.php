<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\LogReaderService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class LogReaderServiceTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir() . '/larpm_log_' . uniqid('', true) . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }
    }

    public function testMissingFileReturnsEmptyList(): void
    {
        $service = new LogReaderService($this->file);

        static::assertFalse($service->exists());
        static::assertSame([], $service->read());
    }

    public function testReadsJsonEntriesNewestFirstAndFiltersByLevel(): void
    {
        file_put_contents($this->file, implode("\n", [
            json_encode(['message' => 'a', 'level_name' => 'INFO', 'channel' => 'app', 'datetime' => '2026-10-15T10:00:00+02:00']),
            json_encode([
                'message' => 'b',
                'level_name' => 'CRITICAL',
                'channel' => 'request',
                'datetime' => '2026-10-15T11:00:00+02:00',
                'extra' => ['url' => '/admin/log', 'requests' => [['route' => 'admin.log']]],
            ]),
            json_encode(['message' => 'c', 'level_name' => 'ERROR', 'channel' => 'app', 'datetime' => '2026-10-15T12:00:00+02:00']),
        ])
            . "\n");

        $entries = (new LogReaderService($this->file))->read(10, ['ERROR', 'CRITICAL']);

        static::assertCount(2, $entries);
        static::assertSame('c', $entries[0]['message']);
        static::assertSame('b', $entries[1]['message']);
        static::assertSame('/admin/log', $entries[1]['url']);
        static::assertSame('admin.log', $entries[1]['route']);
    }

    public function testParsesTextLineAndHonoursLimitAndSearch(): void
    {
        file_put_contents(
            $this->file,
            implode("\n", [
                '[2026-10-15T10:00:00+02:00] app.ERROR: première panne {} []',
                '[2026-10-15T11:00:00+02:00] app.ERROR: seconde panne {} []',
                '[2026-10-15T12:00:00+02:00] app.INFO: tout va bien {} []',
            ])
                . "\n",
        );

        $service = new LogReaderService($this->file);

        $limited = $service->read(1, ['ERROR']);
        static::assertCount(1, $limited);
        static::assertSame('seconde panne {} []', $limited[0]['message']);
        static::assertSame('app', $limited[0]['channel']);

        $searched = $service->read(10, [], 'PREMIÈRE');
        static::assertCount(1, $searched);
        static::assertSame('ERROR', $searched[0]['level']);
    }

    public function testReadsLinesLargerThanChunkSize(): void
    {
        $long = str_repeat('x', 20000);
        file_put_contents($this->file, "[2026-10-15T10:00:00+02:00] app.ERROR: {$long} {} []\n[2026-10-15T11:00:00+02:00] app.ERROR: fin {} []\n");

        $entries = (new LogReaderService($this->file))->read(10);

        static::assertCount(2, $entries);
        static::assertSame('fin {} []', $entries[0]['message']);
        static::assertSame($long . ' {} []', $entries[1]['message']);
    }

    public function testClearTruncatesFile(): void
    {
        file_put_contents($this->file, "[2026-10-15T10:00:00+02:00] app.ERROR: x {} []\n");
        $service = new LogReaderService($this->file);

        $service->clear();

        static::assertSame('', file_get_contents($this->file));
    }
}
