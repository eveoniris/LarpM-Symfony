<?php

declare(strict_types=1);

namespace App\Service\GnTimeline;

use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineStatut;
use App\Repository\GnTimelineStepRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Throwable;

/**
 * Exécute les étapes d'action de la chronologie, à la main ou depuis le cron.
 *
 * Chaque étape est « réclamée » par un UPDATE conditionnel : le cron et le bouton manuel ne peuvent pas la lancer deux fois.
 * Le statut final est écrit par DBAL, car l'EntityManager peut être fermé après l'échec d'une action.
 */
final readonly class GnTimelineExecutor
{
    /** @var array<string, GnTimelineHandlerInterface> */
    private array $handlers;

    /** @param iterable<GnTimelineHandlerInterface> $handlers */
    public function __construct(
        #[AutowireIterator('app.gn_timeline_handler')]
        iterable $handlers,
        private Connection $connection,
        private EntityManagerInterface $entityManager,
        private GnTimelineStepRepository $stepRepository,
        private LoggerInterface $logger,
    ) {
        $map = [];
        foreach ($handlers as $handler) {
            $map[$handler->type()->value] = $handler;
        }
        $this->handlers = $map;
    }

    /**
     * Étapes automatiques échues et encore à faire (types autorisés en automatique uniquement).
     *
     * @return list<GnTimelineStep>
     */
    public function echues(DateTimeImmutable $now): array
    {
        return array_values(array_filter($this->stepRepository->findAutoAFaireAvant($now), static fn (GnTimelineStep $e): bool => $e->getType()->autoAutorise()));
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function executer(GnTimelineStep $etape): array
    {
        $handler = $this->handlers[$etape->getType()->value] ?? null;
        if (null === $handler) {
            return ['ok' => false, 'message' => "Ce type d'étape n'a pas d'action à exécuter."];
        }

        $id = $etape->getId();
        $reclame = $this->connection->executeStatement('UPDATE gn_timeline_step SET statut = :en_cours WHERE id = :id AND statut IN (:a_faire, :erreur)', [
            'en_cours' => GnTimelineStatut::EN_COURS->value,
            'id' => $id,
            'a_faire' => GnTimelineStatut::A_FAIRE->value,
            'erreur' => GnTimelineStatut::ERREUR->value,
        ]);
        if (0 === $reclame) {
            return ['ok' => false, 'message' => 'Étape déjà exécutée ou en cours.'];
        }

        try {
            $message = $handler->executer($etape);
            $this->terminer($id, GnTimelineStatut::EXECUTE, $message);
            $this->logger->info('Étape de chronologie exécutée', ['id' => $id, 'type' => $etape->getType()->value]);
            $ok = true;
        } catch (Throwable $e) {
            $message = $e->getMessage();
            $this->terminer($id, GnTimelineStatut::ERREUR, $message);
            $this->logger->error("Échec d'une étape de chronologie", ['id' => $id, 'type' => $etape->getType()->value, 'exception' => $e]);
            $ok = false;
        }

        if ($this->entityManager->isOpen()) {
            $this->entityManager->refresh($etape);
        }

        return ['ok' => $ok, 'message' => $message];
    }

    private function terminer(?int $id, GnTimelineStatut $statut, string $message): void
    {
        $this->connection->executeStatement('UPDATE gn_timeline_step SET statut = :statut, executed_at = :now, resultat = :resultat WHERE id = :id', [
            'statut' => $statut->value,
            'now' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'resultat' => $message,
            'id' => $id,
        ]);
    }
}
