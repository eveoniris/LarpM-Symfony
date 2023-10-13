<?php

/**
 * LarpManager - A Live Action Role Playing Manager
 * Copyright (C) 2016 Kevin Polez
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
 * LarpManager\Repository\ReligionRepository
 * 
 * @author kevin
 */
class ReligionRepository extends EntityRepository
{
	/**
	 * Find all religions ordered by label
	 * @return ArrayCollection $religions
	 */
	public function findAllOrderedByLabel()
	{
		$religions = $this->getEntityManager()
				->createQuery('SELECT r FROM App\Entity\Religion r ORDER BY r.label ASC')
				->getResult();
		
		return $religions;
	}

	/**
	 * Find all public religions ordered by label
	 * @return ArrayCollection $religions
	 */
	public function findAllPublicOrderedByLabel()
	{
		$religions = $this->getEntityManager()
				->createQuery('SELECT r FROM App\Entity\Religion r WHERE r.secret = 0 ORDER BY r.label ASC')
				->getResult();
		
		return $religions;
	}	
}