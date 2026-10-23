<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Conception;
use App\Entity\Gn;
use App\Entity\Personnage;

class ConceptionRepository extends BaseRepository
{
    /**
     * Le findAll() de BaseRepository trie par label, que cette entité n'a pas.
     *
     * @return array<int, Conception>
     */
    public function findAll(): array
    {
        return $this->findBy([], ['id' => 'ASC']);
    }

    /**
     * Conception déjà enregistrée pour ce couple de parents dans ce GN, quel que soit l'ordre (réelle ou simulation selon l'indicateur).
     * Évite qu'un même couple signalé deux fois (les deux joueurs le déclarent) donne deux tirages.
     */
    public function findParCouple(Gn $gn, Personnage $a, Personnage $b, bool $simulation): ?Conception
    {
        return $this
            ->createQueryBuilder('c')
            ->where('c.gn = :gn AND c.simulation = :simulation')
            ->andWhere('(c.parent1 = :a AND c.parent2 = :b) OR (c.parent1 = :b AND c.parent2 = :a)')
            ->setParameter('gn', $gn)
            ->setParameter('simulation', $simulation)
            ->setParameter('a', $a)
            ->setParameter('b', $b)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
