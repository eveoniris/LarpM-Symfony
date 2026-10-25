<?php

declare(strict_types=1);

namespace App\Service\Competence;

use App\Entity\Gn;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\PersonnageTrigger;
use App\Enum\CompetenceFamilyType;
use App\Enum\LevelType;
use App\Enum\LogActionType;
use App\Enum\TriggerType;
use App\Repository\ParticipantRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Retrouve les triggers sort et potion que la Littérature aurait dû donner pour un GN.
 */
class LitteratureTriggerRattrapage
{
    private const string SQL_SORT = <<<'SQL'
        SELECT COUNT(*) FROM log_action l
        WHERE l.type = :type AND l.date >= :since
          AND JSON_UNQUOTE(JSON_EXTRACT(l.data, '$.personnage_id')) = :pid
          AND JSON_UNQUOTE(JSON_EXTRACT(l.data, '$.niveau')) = :niveau
        SQL;

    private const string SQL_POTION = <<<'SQL'
        SELECT COUNT(*) FROM log_action l
        INNER JOIN potion p ON p.id = JSON_UNQUOTE(JSON_EXTRACT(l.data, '$.potion'))
        WHERE l.type = :type AND l.date >= :since
          AND JSON_UNQUOTE(JSON_EXTRACT(l.data, '$.personnage_id')) = :pid
          AND p.niveau = :niveau
        SQL;

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Date de fin du GN précédent, au-delà de laquelle un choix déjà fait compte comme consommé.
     */
    public function getDefaultSince(Gn $gn): ?DateTime
    {
        $previous = $this->entityManager
            ->createQuery('SELECT g FROM App\Entity\Gn g WHERE g.id != :id AND g.date_debut < :debut ORDER BY g.date_debut DESC')
            ->setParameter('id', $gn->getId())
            ->setParameter('debut', $gn->getDateDebut())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $previous instanceof Gn ? $previous->getDateFin() ?? $previous->getDateDebut() : null;
    }

    /**
     * Triggers attendus pour le GN et absents (ni trigger du GN, ni choix déjà fait depuis la date).
     *
     * @param array{trigger: int, choix: int} $skipped compteurs des cas ignorés, remplis au passage
     *
     * @return iterable<array{Personnage, LevelType, TriggerType}>
     */
    public function findMissing(Gn $gn, ?DateTime $since, array &$skipped): iterable
    {
        /** @var ParticipantRepository $participantRepository */
        $participantRepository = $this->entityManager->getRepository(Participant::class);

        // Niveau de Littérature => niveau du choix (celui de la compétence moins un), tag du sort, tag de la potion
        foreach (LitteratureService::CHOICE_TAGS as $levelIndex => [$sortTag, $potionTag]) {
            $level = LevelType::getFromIndex($levelIndex);
            $niveau = $levelIndex - 1;

            foreach ($participantRepository->findAllByCompentenceFamilyLevel($gn, CompetenceFamilyType::LITERATURE, $level) as $participant) {
                $personnage = $participant->getPersonnage();
                if (!$personnage instanceof Personnage) {
                    continue;
                }

                foreach (
                    [
                        [$sortTag,   LogActionType::ADD_SORT],
                        [$potionTag, LogActionType::ADD_POTION],
                    ] as [$tag, $logType]
                ) {
                    if ($this->hasTriggerForGn($personnage, $tag, $gn)) {
                        ++$skipped['trigger'];

                        continue;
                    }

                    if ($this->hasChosenSince($personnage, $logType, $niveau, $since)) {
                        ++$skipped['choix'];

                        continue;
                    }

                    yield [$personnage, $level, $tag];
                }
            }
        }
    }

    /**
     * Personnages vivants avec Littérature Initié ou plus, sans aucun trigger (actif, consommé ou d'achat) du tag
     * attendu et sans choix déjà fait depuis la date : le trigger d'achat leur est dû.
     *
     * @param array{trigger: int, choix: int} $skipped compteurs des cas ignorés, remplis au passage
     *
     * @return iterable<array{Personnage, LevelType, TriggerType}>
     */
    public function findMissingAchats(?DateTime $since, array &$skipped): iterable
    {
        $rows = $this->entityManager
            ->createQuery(<<<'DQL'
                SELECT pe AS personnage, l.index AS idx FROM App\Entity\Personnage pe
                INNER JOIN pe.competences c
                INNER JOIN c.competenceFamily cf
                INNER JOIN c.level l
                WHERE cf.id = :cfid AND l.index IN (:idx) AND pe.vivant = true
                ORDER BY pe.id, l.index
                DQL)
            ->setParameter('cfid', CompetenceFamilyType::LITERATURE->getId())
            ->setParameter('idx', array_keys(LitteratureService::CHOICE_TAGS))
            ->getResult();

        foreach ($rows as $row) {
            /** @var Personnage $personnage */
            $personnage = $row['personnage'];
            $levelIndex = (int) $row['idx'];
            [$sortTag, $potionTag] = LitteratureService::CHOICE_TAGS[$levelIndex];

            foreach (
                [
                    [$sortTag,   LogActionType::ADD_SORT],
                    [$potionTag, LogActionType::ADD_POTION],
                ]  as [$tag, $logType]
            ) {
                if ($personnage->getAllPersonnageTriggers()->exists(static fn (int $key, PersonnageTrigger $trigger): bool => $trigger->getTag() === $tag)) {
                    ++$skipped['trigger'];

                    continue;
                }

                if ($this->hasChosenSince($personnage, $logType, $levelIndex - 1, $since)) {
                    ++$skipped['choix'];

                    continue;
                }

                yield [$personnage, LevelType::getFromIndex($levelIndex), $tag];
            }
        }
    }

    private function hasTriggerForGn(Personnage $personnage, TriggerType $tag, Gn $gn): bool
    {
        return $personnage
            ->getAllPersonnageTriggers()
            ->exists(static fn (int $key, PersonnageTrigger $trigger): bool => $trigger->getTag() === $tag && ($trigger->getGn()?->getId() === $gn->getId() || $trigger->isAchatNonRattache()));
    }

    /** Cherche dans log_action un choix de ce niveau fait par le personnage depuis la date donnée. */
    private function hasChosenSince(Personnage $personnage, LogActionType $logType, int $niveau, ?DateTime $since): bool
    {
        $count = $this->entityManager->getConnection()->fetchOne(LogActionType::ADD_SORT === $logType ? self::SQL_SORT : self::SQL_POTION, [
            'type' => $logType->value,
            'pid' => $personnage->getId(),
            'niveau' => $niveau,
            'since' => ($since ?? new DateTime('@0'))->format('Y-m-d H:i:s'),
        ]);

        return (int) $count > 0;
    }
}
