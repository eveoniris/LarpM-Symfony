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
 * LarpManager\Repository\LangueRepository
 * 
 * @author kevin
 */
class LangueRepository extends EntityRepository
{
	/**
	 * Find all visible langues ordered by label
	 * @return ArrayCollection $langues
	 */
	public function findAllVisibleOrderedByLabel()
	{
		$langues = $this->getEntityManager()
				->createQuery('SELECT l FROM App\Entity\Langue l WHERE (l.secret = 0 or l.secret IS NULL) ORDER BY l.label ASC')
				->getResult();
		
		return $langues;
	}

	/**
	 * Find all langues ordered by label
	 * @return ArrayCollection $langues
	 */
	public function findAllOrderedByLabel()
	{
		$langues = $this->getEntityManager()
				->createQuery('SELECT l FROM App\Entity\Langue l ORDER BY l.secret ASC, l.label ASC')
				->getResult();
		
		return $langues;
	}
}