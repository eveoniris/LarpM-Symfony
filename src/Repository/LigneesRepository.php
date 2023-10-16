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
 * LarpManager\Repository\LigneesRepository.
 *
 * @author Kevin F.
 */
class LigneesRepository extends EntityRepository
{
    /**
     * Trouve toutes les lignées.
     */
    public function findAll()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('l');
        $qb->from(\App\Entity\Lignee::class, 'l');
        $qb->orderBy('l.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche d'une liste de lignees.
     *
     * @param unknown $type
     * @param unknown $value
     * @param unknown $limit
     * @param unknown $offset
     */
    public function findList($type, $value, $limit, $offset, array $order = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('l');
        $qb->from(\App\Entity\Lignee::class, 'l');
        if ($type && $value) {
            switch ($type) {
                case 'id':
                    $qb->andWhere('l.id = :value');
                    $qb->setParameter('value', $value);
                    break;
                case 'nom':
                    $qb->andWhere('l.nom LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
            }
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('l.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne le nombre de résultats correspondant à un critère.
     */
    public function findCount($type, ?string $value)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('l'));
        $qb->from(\App\Entity\Lignee::class, 'l');

        if ($type && $value) {
            switch ($type) {
                case 'id':
                    $qb->andWhere('l.id = :value');
                    $qb->setParameter('value', $value);
                    break;
                case 'nom':
                    $qb->andWhere('l.nom LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
            }
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
