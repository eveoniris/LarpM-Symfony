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
 * LarpManager\Repository\AgeRepository.
 *
 * @author kevin
 */
class AgeRepository extends EntityRepository
{
    /**
     * Trouve tous les ages classé par index.
     *
     * @return ArrayCollection $ages
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT a FROM App\Entity\Age a ORDER BY a.label ASC')
            ->getResult();
    }

    /**
     * Fourni tous les ages disponible à la création d'un personnage.
     */
    public function findAllOnCreation()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT a FROM App\Entity\Age a WHERE a.enableCreation = true ORDER BY a.label ASC')
            ->getResult();
    }
}
