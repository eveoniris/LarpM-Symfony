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
 * LarpManager\Repository\DebriefingRepository.
 *
 * @author kevin
 */
class DebriefingRepository extends EntityRepository
{
    /**
     * Trouve les debriefing correspondant aux critères de recherche.
     */
    public function findCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('d'));
        $qb->from(\App\Entity\Debriefing::class, 'd');

        foreach ($criteria as $criter) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $criter);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
