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
use Doctrine\ORM\QueryBuilder;

/**
 * LarpManager\Repository\DocumentRepository.
 *
 * @author kevin
 */
class DocumentRepository extends EntityRepository
{
    /**
     * Find all classes ordered by label.
     *
     * @return ArrayCollection $classes
     *
     * @deprecated
     */
    public function findAllOrderedByCode()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT d FROM App\Entity\Document d ORDER BY d.code ASC')
            ->getResult();
    }

    /**
     * Trouve le nombre de documents correspondant aux critères de recherche.
     */
    public function findCount(?string $type, $value)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->select($qb->expr()->count('d'));

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les documents correspondant aux critères de recherche.
     */
    public function findList(?string $type, $value, array $order = ['by' => 'titre', 'dir' => 'ASC'], int $limit = 50, int $offset = 0)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('d.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    protected function getQueryBuilder(?string $type, $value): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('d');
        $qb->from(\App\Entity\Document::class, 'd');

        // retire les caractères non imprimable d'une chaine UTF-8
        $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', htmlspecialchars((string) $value));

        if ($type && $value) {
            switch ($type) {
                case 'titre':
                    $qb->andWhere('d.titre LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'description':
                    $qb->andWhere('d.description LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'code':
                    $qb->andWhere('d.code LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'auteur':
                    $qb->andWhere('d.auteur LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'id':
                    $qb->andWhere('d.id = :value');
                    $qb->setParameter('value', (int) $value);
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Type "%s" inconnu', $type));
            }
        }

        return $qb;
    }
}
