<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\PersonnageBonus;
use App\Service\OrigineBonusService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Décide (et fige) le bonus d'origine dès que la première participation d'un personnage est complète.
 *
 * Déclencheurs, quel que soit le chemin applicatif : création ou rattachement de la première participation,
 * attribution ou changement de son groupe, changement d'origine du personnage (décision reprise).
 * Les participations suivantes ne déclenchent rien : le bonus se décide une seule fois.
 */
#[AsDoctrineListener(event: Events::onFlush)]
final class OrigineBonusListener
{
    public function __construct(
        private readonly OrigineBonusService $service,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $personnages = [];
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->collect($personnages, $entity, []);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->collect($personnages, $entity, $uow->getEntityChangeSet($entity));
        }

        foreach ($personnages as $personnage) {
            $this->service->grant($personnage);
            $this->computeNewRows($em, $personnage);
        }
    }

    /**
     * Retient le personnage dont la décision doit être prise (ou reprise) suite à cette modification.
     * Une insertion est passée avec un changeset vide : tout participant inséré compte comme modifié.
     *
     * @param array<int, Personnage> $personnages
     * @param array<string, mixed>   $changes
     */
    private function collect(array &$personnages, object $entity, array $changes): void
    {
        if ($entity instanceof Personnage && array_key_exists('territoire', $changes)) {
            $this->service->revoke($entity);
            $personnages[spl_object_id($entity)] = $entity;

            return;
        }

        $personnage = $entity instanceof Participant && $this->isFirstParticipation($entity)
            ? $entity->getPersonnage()
            : null;
        if (null !== $personnage && ([] === $changes || array_key_exists('groupeGn', $changes) || array_key_exists('personnage', $changes))) {
            $personnages[spl_object_id($personnage)] = $personnage;
        }
    }

    /**
     * Le participant est la plus ancienne participation de son personnage (l'inverse n'est pas toujours synchronisé).
     */
    private function isFirstParticipation(Participant $participant): bool
    {
        $personnage = $participant->getPersonnage();
        if (null === $personnage) {
            return false;
        }

        if (!$personnage->getParticipants()->contains($participant)) {
            $personnage->getParticipants()->add($participant);
        }

        return $personnage->getFirstParticipant() === $participant;
    }

    /**
     * Les lignes persistées pendant onFlush doivent avoir leur changeset calculé pour être insérées.
     */
    private function computeNewRows(EntityManagerInterface $em, Personnage $personnage): void
    {
        $uow = $em->getUnitOfWork();
        $metadata = $em->getClassMetadata(PersonnageBonus::class);

        foreach ($personnage->getPersonnageBonus()?->filter($uow->isScheduledForInsert(...)) ?? [] as $row) {
            $uow->computeChangeSet($metadata, $row);
        }
    }
}
