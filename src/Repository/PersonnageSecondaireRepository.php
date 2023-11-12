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

class PersonnageSecondaireRepository extends EntityRepository
{
    /**
     * Trouve tous les personnages secondaires.
     *
     * @return ArrayCollection $personnageSecondaire
     */
    public function findAll(): array
    {
        return $this->getEntityManager()
            ->createQuery('SELECT ps FROM App\Entity\PersonnageSecondaire ps')
            ->getResult();
    }
}
