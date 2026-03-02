<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Gn;
use App\Entity\Personnage;
use Carbon\Carbon;
use Doctrine\DBAL\Result;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class GnRepository extends BaseRepository
{
    /**
     * Trouve tous les gns actifs.
     */
    /** @return list<Gn> */
    public function findActive(): array
    {
        return $this->getEntityManager()->createQuery('SELECT g FROM App\Entity\Gn g WHERE g.actif = true ORDER BY g.date_debut ASC')->getResult();
    }

    /**
     * Classe les gn par date (du plus proche au plus lointain).
     */
    /** @return list<Gn> */
    public function findAll(): array
    {
        return $this->findBy([], ['date_debut' => 'DESC']);
    }

    /** @return Paginator<Gn> */
    public function findPaginated(
        int $page,
        int $limit = 10,
        string $orderby = 'id',
        string $orderdir = 'ASC',
        string $where = '1=1',
    ): Paginator {
        $limit = abs($limit);

        $result = [];

        /*
         * $query = $this->getEntityManager()->createQueryBuilder()
         * ->select('c', 'p')
         * ->from('App\Entity\Products', 'p')
         * ->join('p.categories', 'c')
         * ->where("c.slug = '$slug'")
         * ->setMaxResults($limit)
         * ->setFirstResult(($page * $limit) - $limit);
         */

        $query = $this
            ->getEntityManager()
            ->getRepository(Gn::class)
            ->createQueryBuilder('gn')
            ->orderBy('gn.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit)
            ->getQuery();

        return new Paginator($query);
    }

    /**
     * Recherche le prochain GN (le plus proche de la date du jour).
     */
    public function findNext(): ?Gn
    {
        // AND g.date_debut > CURRENT_DATE()
        $gns = $this->getEntityManager()->createQuery('SELECT g FROM App\Entity\Gn g WHERE g.actif = true ORDER BY g.date_debut DESC')->getResult();

        return $gns[0] ?? null;
    }

    /**
     * Recherche le précédent GN joué encore actif.
     */
    public function findCurrentActive(): ?Gn
    {
        $gns = $this->getEntityManager()->createQuery('SELECT g FROM App\Entity\Gn g WHERE g.actif = true ORDER BY g.date_debut DESC')->getResult();

        return $gns[0] ?? null;
    }

    public function getParticipant(Gn $gn): QueryBuilder
    {
        /** @var ParticipantRepository $participantRepository */
        $participantRepository = $this->entityManager->getRepository(Personnage::class);

        return $participantRepository->createQueryBuilder('participant')->innerJoin('participant.gn', 'gn')->where('gn.id = :gnid')->setParameter('gnid', $gn->getId());
    }

    public function getPersonnages(Gn $gn): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        return $personnageRepository->createQueryBuilder('perso')->innerJoin('perso.gn', 'gn')->where('gn.id = :gnid')->setParameter('gnid', $gn->getId());
    }

    public function lockAllGroup(Gn $gn): Result
    {
        return $this->toggleLock($gn, true);
    }

    public function unlockAllGroup(Gn $gn): Result
    {
        return $this->toggleLock($gn, false);
    }

    private function toggleLock(Gn $gn, bool $lock): Result
    {
        $connection = $this->entityManager->getConnection();

        $sql = <<<SQL
             UPDATE groupe g
             INNER JOIN groupe_gn AS ggn ON g.id = ggn.groupe_id
             SET `lock` = :lockvalue
             WHERE ggn.gn_id = :gnid
            SQL;

        $statement = $connection->prepare($sql);
        $statement->bindValue('lockvalue', $lock ? 1 : 0);
        $statement->bindValue('gnid', $gn->getId());

        return $statement->executeQuery();
    }

    public function giveParticipationXp(Gn $gn, int $xp): Result
    {
        $connection = $this->entityManager->getConnection();

        $text = \sprintf('+ %d XP PARTICIPATION %s PAR GESTION', $xp, $gn->getLabel());
        $sql = <<<SQL
             INSERT into experience_gain (personnage_id, explanation, operation_date, xp_gain, discr)
                SELECT p.personnage_id, ':text1', ':date', :xpgain, 'extended'
                FROM participant p
                where p.gn_id = :gnid
                  and personnage_id is not null
                  and personnage_id not in
                      (select personnage_id from experience_gain where explanation = ':text2')
            SQL;

        // TODO FIX binding error + add 2xp to personnage.xp !
        $statement = $connection->prepare($sql);
        $statement->bindValue('text1', $text);
        $statement->bindValue('text2', $text);
        $statement->bindValue('xpgain', $xp);
        $statement->bindValue('gnid', $gn->getId());
        $statement->bindValue('date', Carbon::now()->format('Y-m-d H:i:s'));

        return $statement->executeQuery();
    }
}
