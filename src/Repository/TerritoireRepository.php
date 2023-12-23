<?php

/**
 * LarpManager - A Live Action Role Playing Manager
 * Copyright (C) 2016 Kevin Polez.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\TerritoireRepository.
 *
 * @author kevin
 */
class TerritoireRepository extends BaseRepository
{
    /**
     * Find all territoire ordered by nom.
     *
     * @return ArrayCollection $territoires
     */
    public function findAllOrderedByNom()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT t FROM App\Entity\Territoire t ORDER BY t.nom ASC')
            ->getResult();
    }

    /**
     * Fourni la liste des territoires n'étant pas dépendant d'un autre territoire.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findRoot()
    {
        $query = $this->getEntityManager()->createQuery('SELECT t FROM App\Entity\Territoire t WHERE t.territoire IS NULL ORDER BY t.nom ASC');

        return $query->getResult();
    }

    /**
     * Fourni la liste des territoires étant dépendant d'un autre territoire et possédant des territoires.
     *
     * @return mixed[]
     */
    public function findRegions(): array
    {
        $query = $this->getEntityManager()->createQuery('SELECT t FROM App\Entity\Territoire t  WHERE t.territoire IS NOT NULL ORDER BY t.nom ASC');
        $territoires = $query->getResult();

        $result = [];
        foreach ($territoires as $territoire) {
            if ($territoire->getTerritoires()->count() > 0) {
                $result[] = $territoire;
            }
        }

        return $result;
    }

    /**
     * Fourni la liste des territoires étant dépendant d'un autre territoire et ne possédant pas de territoires.
     *
     * @return mixed[]
     */
    public function findFiefs(): array
    {
        $query = $this->getEntityManager()->createQuery('SELECT t FROM App\Entity\Territoire t  WHERE t.territoire IS NOT NULL ORDER BY t.nom ASC');
        $territoires = $query->getResult();

        $result = [];
        foreach ($territoires as $territoire) {
            if (0 == $territoire->getTerritoires()->count()) {
                $result[] = $territoire;
            }
        }

        return $result;
    }

    /**
     * Trouve les fiefs correspondant aux critères de recherche.
     *
     * @param unknown $limit
     * @param unknown $offset
     */
    public function findFiefsList($limit, $offset, array $criteria = [], array $order = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('distinct t');
        $qb->from(\App\Entity\Territoire::class, 't');
        if (array_key_exists('tgr.id', $criteria)) {
            $qb->join('t.groupe', 'tgr');
        }

        $qb->join('t.territoire', 'tpr');
        $qb->leftjoin('tpr.territoire', 'tp');
        $qb->andWhere('t.territoire IS NOT NULL');

        $count = 0;
        foreach ($criteria as $key => $value) {
            if ('t.nom' == $key) {
                $qb->andWhere(sprintf('LOWER(%s) LIKE ?', $key).$count)
                    ->setParameter($count, '%'.preg_replace('/[\'"<>=*;]/', '', strtolower((string) $value)).'%');
            } else {
                $qb->andWhere($key.' = ?'.$count)
                    ->setParameter($count, $value);
            }

            ++$count;
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        $defaultEntityAlias = strstr((string) $order['by'], '.') ? '' : 't.';
        $qb->orderBy($defaultEntityAlias.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve le nombre de fiefs correspondant aux critères de recherche.
     */
    public function findFiefsCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('distinct t'));
        $qb->from(\App\Entity\Territoire::class, 't');
        if (array_key_exists('groupe', $criteria)) {
            $qb->join('t.groupe', 'tgr');
        }

        $qb->join('t.territoire', 'tpr');
        $qb->join('tpr.territoire', 'tp');
        $qb->andWhere('t.territoire IS NOT NULL');
        $qb->andWhere('tp.territoire IS NULL');

        $count = 0;
        foreach ($criteria as $key => $value) {
            if ('t.nom' == $key) {
                $qb->andWhere($key.sprintf(' LIKE %%?%d%%', $count))
                    ->setParameter(''.$count, $value);
            } else {
                $qb->andWhere($key.(' = ?'.$count))
                    ->setParameter(''.$count, $value);
            }

            ++$count;
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findProvinces()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('distinct tpr');
        $qb->from(\App\Entity\Territoire::class, 'tpr');
        $qb->leftJoin(\App\Entity\Territoire::class, 't', 'WITH', 'tpr.id = t.territoire');
        $qb->join('tpr.territoire', 'tp');
        $qb->andWhere('tpr.territoire IS NOT NULL');
        $qb->andWhere('tp.territoire IS NULL');
        $qb->orderBy('tpr.nom', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
