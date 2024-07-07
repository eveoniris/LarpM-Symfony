<?php

namespace App\Repository;

use App\Entity\Religion;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;

/**
 * LarpManager\Repository\ReligionRepository.
 *
 * @author kevin
 */
class ReligionRepository extends BaseRepository
{
    /**
     * Find all religions ordered by label.
     *
     * @return ArrayCollection $religions
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT r FROM App\Entity\Religion r ORDER BY r.label ASC')
            ->getResult();
    }

    /**
     * Find all public religions ordered by label.
     *
     * @return ArrayCollection $religions
     */
    public function findAllPublicOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT r FROM App\Entity\Religion r WHERE r.secret = 0 ORDER BY r.label ASC')
            ->getResult();
    }

    public function findAllEmail(Religion $religion, array $religionsLevels = [])
    {
        $query = $this->createQueryBuilder(
            <<<DQL
                SELECT u.email
                 FROM App\Entity\PersonnagesReligions pr
                INNER JOIN App\Entity\User u
                INNER JOIN App\Entity\ReligionLevel rl
                DQL
        )
            ->andWhere("u.email <> '' AND u.email IS NOT NULL")
            ->andWhere('r.id = :religionId')

        ->setParameter(':religionId', $religion->getId());

        $this->religionsLevels($query, $religionsLevels);

        return $query->getQuery();
    }

    public function religionsLevels(QueryBuilder $query, array $religionsLevels): QueryBuilder
    {
        if (!empty($religionsLevels)) {
            foreach ($religionsLevels as $i => $religionLevel) {
                if (!is_numeric($religionLevel) || $religionLevel > 3 || $religionLevel < 1) {
                    unset($religionsLevels[$i]);
                }
            }
            if (!empty($religionsLevels)) {
                $query->andWhere('rl.index IN (:religionLevels)');
                $query->setParameter('religionLevels', $religionsLevels);
            }
        }

        return $query;
    }

    public function getUserEmailsByReligions(): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'gn_id', 'integer');
        $rsm->addScalarResult('label', 'label', 'string');
        $rsm->addScalarResult('level', 'level', 'string');
        $rsm->addScalarResult('emails', 'emails', 'string');

        $query = $this->getEntityManager()
            ->createNativeQuery(
                <<<SQL
                SELECT r.label, rl.label as level, GROUP_CONCAT(u.email SEPARATOR ', ') as emails
                  FROM
                    `personnages_religions` pr
                    INNER JOIN religion r ON r.id = pr.religion_id
                    INNER JOIN personnage p ON p.id = pr.personnage_id
                    INNER JOIN `user` u ON u.id = p.user_id
                    INNER JOIN religion_level rl ON rl.id = pr.religion_level_id
                WHERE u.email is not null AND u.email <> ""
                GROUP BY r.label, rl.label;
                SQL,
                $rsm
            );

        return $query->getResult();
    }
}
