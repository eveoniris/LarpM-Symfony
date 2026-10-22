<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DepouillementPhoto;

class DepouillementPhotoRepository extends BaseRepository
{
    /**
     * Le findAll() de BaseRepository trie par label, que cette entité n'a pas.
     *
     * @return array<int, DepouillementPhoto>
     */
    public function findAll(): array
    {
        return $this->findBy([], ['id' => 'ASC']);
    }
}
