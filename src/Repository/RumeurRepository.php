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

use App\Entity\Rumeur;
use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\RumeurRepository.
 *
 * @author kevin
 */
class RumeurRepository extends BaseRepository
{
    /**
     * Recherche d'une liste d'rumeur.
     *
     * @param unknown $type
     * @param unknown $value
     * @param unknown $limit
     * @param unknown $offset
     */
    public function findList($type, $value, $limit, $offset, array $order = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('i');
        $qb->from(\App\Entity\Rumeur::class, 'i');
        if ($type && $value && 'titre' === $type) {
            $qb->andWhere('i.titre LIKE :value');
            $qb->setParameter('value', '%'.$value.'%');
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('i.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve le nombre d'rumeur correspondant aux critères de recherche.
     */
    public function findCount($type, ?string $value)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('r'));
        $qb->from(\App\Entity\Rumeur::class, 'r');

        if ($type && $value && 'text' === $type) {
            $qb->andWhere('r.text LIKE :value');
            $qb->setParameter('value', '%'.$value.'%');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
