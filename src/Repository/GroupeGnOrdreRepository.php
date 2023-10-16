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
 * LarpManager\Repository\GroupeGnOrdreRepository.
 *
 * @author Kevin F.
 */
class GroupeGnOrdreRepository extends EntityRepository
{
    /**
     * Trouve un groupe en fonction de son code.
     *
     * @return App\Entity\GroupeGnOrdre $groupeGnOrdre
     */
    public function findByGn($gnId)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\GroupeGnOrdre g JOIN g.groupeGn ggn JOIN g.gn gn WHERE gn.id = :gnId')
            ->setParameter('gnId', $gnId)
            ->getResult();
    }
}
