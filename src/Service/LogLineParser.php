<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Transforme une ligne de log Monolog (JSON en prod, texte en dev) en entrée structurée.
 */
final class LogLineParser
{
    /**
     * @return array{datetime: string, level: string, channel: string, message: string, url: string, route: string, context: string, raw: string}
     */
    public static function parse(string $line): array
    {
        $line = rtrim($line, "\r\n");
        $entry = [
            'datetime' => '',
            'level' => 'INFO',
            'channel' => '',
            'message' => $line,
            'url' => '',
            'route' => '',
            'context' => '',
            'raw' => $line,
        ];

        $data = str_starts_with($line, '{') ? json_decode($line, true) : null;
        if (\is_array($data)) {
            $entry['datetime'] = (string) ($data['datetime'] ?? '');
            $entry['level'] = strtoupper((string) ($data['level_name'] ?? 'INFO'));
            $entry['channel'] = (string) ($data['channel'] ?? '');
            $entry['message'] = (string) ($data['message'] ?? '');
            $extra = \is_array($data['extra'] ?? null) ? $data['extra'] : [];
            $entry['url'] = (string) ($extra['url'] ?? '');
            $entry['route'] = (string) ($extra['requests'][0]['route'] ?? '');
            if ([] !== ($data['context'] ?? [])) {
                $entry['context'] = (string) json_encode($data['context'], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
            }

            return $entry;
        }

        // Format texte Monolog : [2026-10-15T11:43:49.595858+02:00] request.CRITICAL: message {...} []
        if (1 === preg_match('/^\[([^\]]+)\]\s+([\w.-]+)\.([A-Z]+):\s*(.*)$/s', $line, $m)) {
            $entry['datetime'] = $m[1];
            $entry['channel'] = $m[2];
            $entry['level'] = $m[3];
            $entry['message'] = $m[4];
        }

        return $entry;
    }
}
