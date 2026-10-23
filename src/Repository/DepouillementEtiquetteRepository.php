<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DepouillementEtiquette;

class DepouillementEtiquetteRepository extends BaseRepository
{
    /**
     * Le findAll() de BaseRepository trie par label, que cette entité n'a pas.
     *
     * @return array<int, DepouillementEtiquette>
     */
    public function findAll(): array
    {
        return $this->findBy([], ['id' => 'ASC']);
    }
}
