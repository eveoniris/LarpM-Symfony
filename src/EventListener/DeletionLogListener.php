<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\BilletSync;
use App\Entity\LogAction;
use App\Entity\QrCodeScanLog;
use App\Entity\User;
use App\Enum\LogActionType;
use App\Service\DeletionLogContext;
use App\Service\DeletionSnapshotBuilder;
use App\Service\DeletionTreeResolver;
use App\Service\SoftDeletionCollector;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Écrit une entrée log_action pour chaque suppression (hard ou soft via deletedAt).
 *
 * Seules les entités « racines » sont journalisées : une entité supprimée en cascade avec son
 * parent n'a pas d'entrée propre, elle est comptée dans data.cascade de la racine.
 */
#[AsDoctrineListener(event: Events::onFlush)]
final class DeletionLogListener
{
    /** Entités techniques dont la suppression n'a pas d'intérêt métier. */
    private const array EXCLUDED = [
        LogAction::class,
        QrCodeScanLog::class,
        BilletSync::class,
    ];

    public function __construct(
        private readonly Security $security,
        private readonly DeletionLogContext $context,
        private readonly DeletionSnapshotBuilder $snapshots,
        private readonly DeletionTreeResolver $tree,
        private readonly SoftDeletionCollector $soft,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $deletions = [];
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $deletions[spl_object_id($entity)] = $entity;
        }

        $softDeletions = $this->soft->collect($uow, $deletions);
        $tree = $this->tree->resolve($em, $deletions);

        foreach ($tree['roots'] as $id => $entity) {
            $this->writeLog($em, $entity, [] === ($tree['cascade'][$id] ?? []) ? [] : ['cascade' => $tree['cascade'][$id]]);
        }

        foreach ($softDeletions as $entity) {
            $this->writeLog($em, $entity, ['soft' => true]);
        }
    }

    private function isExcluded(object $entity): bool
    {
        foreach (self::EXCLUDED as $class) {
            if ($entity instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $extra données complémentaires (soft, cascade)
     */
    private function writeLog(EntityManagerInterface $em, object $entity, array $extra): void
    {
        if ($this->isExcluded($entity)) {
            return;
        }

        $annotation = $this->context->consume($entity);
        $data = $this->snapshots->payload($em, $entity, $extra, $annotation['extra'] ?? []);

        $log = new LogAction();
        $log->setDate(new DateTime());
        $log->setUser($this->currentUser($em));
        $log->setType($annotation['type'] ?? LogActionType::ENTITY_DELETE);
        $log->setData($data);

        $em->persist($log);
        $em->getUnitOfWork()->computeChangeSet($em->getClassMetadata(LogAction::class), $log);
    }

    private function currentUser(EntityManagerInterface $em): ?User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            return null;
        }

        return $em->contains($user) ? $user : $em->getReference(User::class, $user->getId());
    }
}
