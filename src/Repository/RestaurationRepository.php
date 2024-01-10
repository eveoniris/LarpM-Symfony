<?php


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

    public function gerRestrictionByGn(Restauration $restauration): array
    {
        $results = [];
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'gn_id', 'integer');
        $rsm->addScalarResult('label', 'label', 'string');
        $rsm->addScalarResult('restriction', 'restriction', 'string');
        $rsm->addScalarResult('restriction_id', 'restriction_id', 'integer');
        $rsm->addScalarResult('total', 'total', 'integer');

        $query = $this->getEntityManager()
            ->createNativeQuery(
                <<<SQL
                SELECT gn.id,
                       gn.label,
                       r.label                   as restriction,
                       uhr.restriction_id        as restriction_id,
                       COUNT(phr.participant_id) as total
                FROM participant_has_restauration phr
                         INNER JOIN participant p ON p.id = phr.participant_id
                         INNER JOIN gn ON p.gn_id = gn.id
                         INNER JOIN user u ON p.user_id = u.id
                         LEFT JOIN user_has_restriction uhr ON uhr.user_id = u.id
                         INNER JOIN restriction r ON uhr.restriction_id = r.id
                WHERE restauration_id = :restaurationId
                SQL,
                $rsm
            );

        $query->setParameter('restaurationId', $restauration->getId());

        foreach ($query->getResult() as $result) {
            $results[$result['gn_id']] ??= [
                'gn' => ['label' => $result['label']],
                'total' => $result['total'],
                'restrictions' => [],
            ];

            $results[$result['gn_id']]['restrictions'][$result['restriction_id']] ??= [
                'label' => $result['restriction'],
            ];
        }

        return $results;
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
                         LEFT JOIN user_has_restriction uhr ON uhr.user_id = u.id
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
