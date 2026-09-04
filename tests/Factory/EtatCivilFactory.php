<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\EtatCivil;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * Informations administratives du joueur.
 *
 * Indispensable dès qu'un test passe par la liste des participants d'un GN :
 * ParticipantRepository::search() fait un INNER JOIN sur user.etatCivil, donc un
 * joueur sans état civil n'apparaît pas dans la liste.
 *
 * @extends PersistentObjectFactory<EtatCivil>
 */
final class EtatCivilFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return EtatCivil::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'nom' => self::faker()->lastName(),
            'prenom' => self::faker()->firstName(),
        ];
    }
}
