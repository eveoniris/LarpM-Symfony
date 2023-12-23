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
 * LarpManager\Repository\AppelationRepository.
 *
 * @author kevin
 */
class AppelationRepository extends BaseRepository
{
    public $app;

    /**
     * Fourni la liste des appelations n'étant pas dépendante d'une autre appelation.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findRoot()
    {
        $query = $this->app['orm.em']->createQuery('SELECT a FROM App\Entity\Appelation a WHERE a.appelation IS NULL');

        return $query->getResult();
    }
}
