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

use App\Entity\Restauration;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * LarpManager\Repository\RestaurationRepository.
 *
 * @author kevin
 */
class RestaurationRepository extends BaseRepository
{
    /**
     * Fourni la liste des restrictions alimentaires.
     */
    public function findAllOrderedByLabel()
    {
        $query = $this->getEntityManager()->createQuery('SELECT r FROM App\Entity\Restauration r ORDER BY r.label ASC');

        return $query->getResult();
    }

    public function getUsersByGn(Restauration $restauration): array
    {
        $results = [];
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'gn_id', 'integer');
        $rsm->addScalarResult('label', 'label', 'string');
        $rsm->addScalarResult('prenom', 'prenom', 'string');
        $rsm->addScalarResult('nom', 'nom', 'string');
        $rsm->addScalarResult('restriction_id', 'restriction_id', 'integer');
        $rsm->addScalarResult('user_id', 'user_id', 'integer');
        $rsm->addScalarResult('restriction', 'restriction', 'string');

        $query = $this->getEntityManager()
            ->createNativeQuery(
                <<<SQL
                SELECT gn.id, 
                       gn.label,
                       ec.prenom,
                       ec.nom,
                       uhr.restriction_id,
                       uhr.user_id,
                       r.label as restriction
                FROM participant_has_restauration phr
                         INNER JOIN participant p ON p.id = phr.participant_id
                         INNER JOIN gn ON p.gn_id = gn.id
                         INNER JOIN user u ON p.user_id = u.id
                         LEFT JOIN etat_civil ec ON u.etat_civil_id = ec.id
                         LEFT JOIN larpm.user_has_restriction uhr ON uhr.user_id = u.id
                         INNER JOIN restriction r ON uhr.restriction_id = r.id
                WHERE restauration_id = :restaurationId
                SQL,
                $rsm
            );

        $query->setParameter('restaurationId', $restauration->getId());

        foreach ($query->getResult() as $result) {
            $results[$result['gn_id']] ??= [
                'gn' => ['label' => $result['label']],
                'count' => 0,
                'users' => [],
            ];
            ++$results[$result['gn_id']]['count'];
            $results[$result['gn_id']]['users'][$result['user_id']] ??= [
                'id' => $result['user_id'],
                'etatCivil' => [
                    'nom' => $result['nom'],
                    'prenom' => $result['prenom'],
                ],
                'restrictions' => [],
            ];

            $results[$result['gn_id']]['users'][$result['user_id']]['restrictions'][] = $result['restriction'];
        }

        return $results;
    }
}
