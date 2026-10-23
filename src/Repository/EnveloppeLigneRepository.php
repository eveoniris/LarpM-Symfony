<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EnveloppeLigne;
use App\Entity\Personnage;

class EnveloppeLigneRepository extends BaseRepository
{
    /**
     * Le findAll() de BaseRepository trie par label, que cette entité n'a pas.
     *
     * @return array<int, EnveloppeLigne>
     */
    public function findAll(): array
    {
        return $this->findBy([], ['id' => 'ASC']);
    }

    /**
     * Lignes reportées dans l'enveloppe d'un personnage, tous GN confondus.
     *
     * @return list<EnveloppeLigne>
     */
    public function findParPersonnage(Personnage $personnage): array
    {
        return array_values($this->findBy(['personnage' => $personnage], ['gn' => 'ASC', 'id' => 'ASC']));
    }
}
