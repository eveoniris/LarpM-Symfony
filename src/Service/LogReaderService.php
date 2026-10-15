<?php

declare(strict_types=1);

namespace App\Service;

use SplFileObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Lit les dernières entrées du fichier de log de l'environnement courant (texte en dev, JSON en prod).
 */
class LogReaderService
{
    private const CHUNK_SIZE = 8192;

    public function __construct(
        #[Autowire('%kernel.logs_dir%/%kernel.environment%.log')]
        private readonly string $logFile,
    ) {
    }

    public function getFilename(): string
    {
        return $this->logFile;
    }

    public function exists(): bool
    {
        return is_file($this->logFile) && is_readable($this->logFile);
    }

    /**
     * Retourne au plus $limit entrées correspondant aux filtres, de la plus récente à la plus ancienne.
     *
     * @param list<string> $levels niveaux acceptés (vide = tous)
     *
     * @return list<array{datetime: string, level: string, channel: string, message: string, url: string, route: string, context: string, raw: string}>
     */
    public function read(int $limit = 200, array $levels = [], string $search = ''): array
    {
        if (!$this->exists()) {
            return [];
        }

        $levels = array_map(strtoupper(...), $levels);
        $search = mb_strtolower($search);
        $entries = [];

        foreach ($this->readLinesReversed() as $line) {
            if ('' === trim($line)) {
                continue;
            }

            if ('' !== $search && !str_contains(mb_strtolower($line), $search)) {
                continue;
            }

            $entry = LogLineParser::parse($line);
            if ([] !== $levels && !\in_array($entry['level'], $levels, true)) {
                continue;
            }

            $entries[] = $entry;
            if (\count($entries) >= $limit) {
                break;
            }
        }

        return $entries;
    }

    /**
     * Vide le fichier de log courant.
     */
    public function clear(): void
    {
        if ($this->exists()) {
            file_put_contents($this->logFile, '');
        }
    }

    /**
     * Génère les lignes du fichier de la dernière à la première, en lisant par blocs depuis la fin.
     *
     * @return \Generator<int, string>
     */
    private function readLinesReversed(): \Generator
    {
        $file = new SplFileObject($this->logFile, 'rb');
        $position = (int) $file->getSize();
        $buffer = '';

        while ($position > 0) {
            $read = min(self::CHUNK_SIZE, $position);
            $position -= $read;
            $file->fseek($position);
            $buffer = (string) $file->fread($read) . $buffer;

            $parts = explode("\n", $buffer);
            // le premier morceau peut être incomplet : on le garde pour le bloc suivant
            $buffer = (string) array_shift($parts);

            for ($i = \count($parts) - 1; $i >= 0; --$i) {
                yield $parts[$i];
            }
        }

        if ('' !== $buffer) {
            yield $buffer;
        }
    }
}
