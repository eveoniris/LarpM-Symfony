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

use App\Entity\Groupe;
use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\GroupeRepository.
 *
 * @author kevin
 */
class GroupeRepository extends EntityRepository
{
    /**
     * Trouve tous les groupes classé par ordre alphabétique.
     */
    public function findAll()
    {
        return $this->findBy([], ['nom' => 'ASC']);
    }

    /**
     * Trouve tous les groupes de PJ classé par numéro.
     */
    public function findAllPj()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\Groupe g WHERE g.pj = true ORDER BY g.numero ASC')
            ->getResult();
    }

    /**
     * Recherche d'une liste de groupe.
     *
     * @param unknown $type
     * @param unknown $value
     * @param unknown $limit
     * @param unknown $offset
     */
    public function findList($type, $value, $limit, $offset, array $order = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('g');
        $qb->from(\App\Entity\Groupe::class, 'g');
        if ($type && $value) {
            switch ($type) {
                case 'numero':
                    $qb->andWhere('g.numero = :value');
                    $qb->setParameter('value', $value);
                    break;
                case 'nom':
                    $qb->andWhere('g.nom LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
            }
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('g.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les annonces correspondant aux critères de recherche.
     */
    public function findCount($type, ?string $value)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('g'));
        $qb->from(\App\Entity\Groupe::class, 'g');

        if ($type && $value) {
            switch ($type) {
                case 'numero':
                    $qb->andWhere('g.numero = :value');
                    $qb->setParameter('value', $value);
                    break;
                case 'nom':
                    $qb->andWhere('g.nom LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
            }
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Fourni la liste de tous les groupes classé par numéro.
     *
     * @return Collection App\Entity\Groupe $groupes
     */
    public function findAllOrderByNumero()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\Groupe g ORDER BY g.numero ASC')
            ->getResult();
    }

    /**
     * Trouve un groupe en fonction de son code.
     *
     * @param string $code
     *
     * @return App\Entity\Groupe $groupe
     */
    public function findOneByCode($code)
    {
        $groupes = $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\Groupe g WHERE g.code = :code')
            ->setParameter('code', $code)
            ->getResult();

        return reset($groupes);
    }
}
