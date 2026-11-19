<?php

declare(strict_types=1);

namespace App\Security;

use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Journal fichier des refus d'accès (une ligne JSON par refus), à part du log applicatif.
 * Purgé par app:acces-log:purger (voir bin/cron-purge-logs.php).
 */
final readonly class AccesRefuseJournal
{
    public const string FILE_NAME = 'error_acces.log';

    private string $path;

    public function __construct(#[Autowire('%kernel.logs_dir%')] string $logsDir)
    {
        $this->path = rtrim($logsDir, characters: '/\\') . \DIRECTORY_SEPARATOR . self::FILE_NAME;
    }

    /** @param array<string, mixed> $ligne */
    public function ecrire(array $ligne): void
    {
        $json = json_encode(['date' => (new DateTimeImmutable())->format(\DATE_ATOM)] + $ligne, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
        if (false === $json) {
            return;
        }
        file_put_contents($this->path, $json . "\n", \FILE_APPEND | \LOCK_EX);
    }

    /** Nombre de lignes antérieures à la date donnée (simulation de la purge). */
    public function compter(DateTimeImmutable $avant): int
    {
        return \count($this->lignesAnciennes($avant));
    }

    /**
     * Supprime les lignes antérieures à la date donnée (les lignes illisibles sont conservées).
     *
     * @return int nombre de lignes supprimées
     */
    public function purger(DateTimeImmutable $avant): int
    {
        $anciennes = $this->lignesAnciennes($avant);
        if ([] === $anciennes) {
            return 0;
        }

        $gardees = array_diff_key($this->lignes(), $anciennes);
        file_put_contents($this->path, [] === $gardees ? '' : implode("\n", $gardees) . "\n", \LOCK_EX);

        return \count($anciennes);
    }

    /** @return array<int, string> */
    private function lignes(): array
    {
        if (!is_file($this->path)) {
            return [];
        }
        $lignes = file($this->path, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);

        return false === $lignes ? [] : $lignes;
    }

    /** @return array<int, string> lignes datées d'avant la limite, indexées par numéro de ligne */
    private function lignesAnciennes(DateTimeImmutable $avant): array
    {
        return array_filter($this->lignes(), static function (string $ligne) use ($avant): bool {
            $date = json_decode($ligne, associative: true)['date'] ?? null;
            $horodatage = \is_string($date) ? strtotime($date) : false;

            return false !== $horodatage && $horodatage < $avant->getTimestamp();
        });
    }
}
