<?php

declare(strict_types=1);

namespace App\Service\Discord;

use App\Entity\DiscordGuild;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Synchronise les rôles Discord de tous les comptes liés dans une guilde (cron quotidien, bouton « Tout resynchroniser »).
 */
final readonly class DiscordBulkSyncService
{
    /** Erreurs consécutives au-delà desquelles on suppose Discord injoignable et on s'arrête. */
    private const int MAX_CONSECUTIVE_ERRORS = 5;

    public function __construct(
        private DiscordSyncService $syncService,
        private EntityManagerInterface $entityManager,
        /** Pause (en microsecondes) après chaque compte modifié, pour ménager la limite de débit de Discord. */
        private int $pauseMicroseconds = 100_000,
    ) {
    }

    /**
     * @param int|null $limit  nombre maximal de comptes traités (durée d'une requête web), tous si null
     * @param int      $offset rang du premier compte (reprise d'un traitement par pages)
     */
    public function run(DiscordGuild $guild, bool $apply, ?int $limit = null, int $offset = 0): DiscordBulkSyncReport
    {
        $report = new DiscordBulkSyncReport();
        $report->total = (int) $this->entityManager
            ->createQuery('SELECT COUNT(u.id) FROM ' . User::class . ' u WHERE u.discordId IS NOT NULL')
            ->getSingleScalarResult();

        $query = $this->entityManager
            ->createQuery('SELECT u FROM ' . User::class . ' u WHERE u.discordId IS NOT NULL ORDER BY u.id')
            ->setFirstResult($offset);
        if (null !== $limit) {
            $query->setMaxResults($limit);
        }

        $consecutiveErrors = 0;
        foreach ($query->toIterable() as $user) {
            $result = $this->syncService->sync($user, $guild, $apply);
            ++$report->processed;

            if (null !== $result->error) {
                ++$report->errors;
                ++$consecutiveErrors;
                $report->changes[] = ['user' => $user->getDisplayName(), 'added' => 0, 'removed' => 0, 'error' => $result->error];
                if ($consecutiveErrors >= self::MAX_CONSECUTIVE_ERRORS) {
                    $report->aborted = true;

                    break;
                }

                continue;
            }

            $consecutiveErrors = 0;
            if (!$result->inGuild) {
                ++$report->absent;
            } elseif ([] === $result->added && [] === $result->removed) {
                ++$report->upToDate;
            } else {
                $report->changes[] = ['user' => $user->getDisplayName(), 'added' => \count($result->added), 'removed' => \count($result->removed), 'error' => null];
                if ($apply && $this->pauseMicroseconds > 0) {
                    usleep($this->pauseMicroseconds);
                }
            }
        }

        $next = $offset + $report->processed;
        $report->nextOffset = !$report->aborted && $next < $report->total ? $next : null;

        return $report;
    }
}
