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

use App\Entity\Gn;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\GnRepository.
 *
 * @author kevin
 */
class GnRepository extends EntityRepository
{
    /**
     * Recherche le prochain GN (le plus proche de la date du jour).
     */
    public function findNext()
    {
        // AND g.date_debut > CURRENT_DATE()
        $gns = $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\Gn g WHERE g.actif = true ORDER BY g.date_debut DESC')
            ->getResult();

        return $gns[0] ?? null;
    }

    /**
     * Classe les gn par date (du plus proche au plus lointain).
     */
    public function findAll()
    {
        return $this->findBy([], ['date_debut' => 'DESC']);
    }

    /**
     * Trouve les gns correspondant aux critères de recherche.
     */
    public function findCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('g'));
        $qb->from(\App\Entity\Gn::class, 'g');

        foreach ($criteria as $criter) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $criter);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve tous les gns actifs.
     *
     * @return ArrayCollection $gns
     */
    public function findActive()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\Gn g WHERE g.actif = true ORDER BY g.date_debut ASC')
            ->getResult();
    }
}
