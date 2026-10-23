<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Genre;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Genres de référence (1 masculin, 2 féminin, 3 ambigu) dont certaines règles dépendent par identifiant.
 * La base de test n'en contient aucun : ils sont insérés au besoin, avec leur identifiant.
 */
final class GenresDeReference
{
    private const array LIBELLES = [1 => 'Masculin', 2 => 'Feminin', 3 => 'Ambigu'];

    public static function genre(EntityManagerInterface $em, int $id): Genre
    {
        $genre = $em->getRepository(Genre::class)->find($id);
        if ($genre instanceof Genre) {
            return $genre;
        }

        $em->getConnection()->insert('genre', ['id' => $id, 'label' => self::LIBELLES[$id], 'description' => '', 'discr' => 'extended']);

        return $em->getRepository(Genre::class)->find($id) ?? throw new \LogicException('Genre de référence introuvable.');
    }
}
