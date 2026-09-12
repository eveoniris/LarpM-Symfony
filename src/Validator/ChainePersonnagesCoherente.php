<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Vérifie la cohérence de la chaîne de personnages d'une participation :
 * les rôles (principal, relève, substitution) doivent être tenus par des
 * personnages distincts, appartenant au joueur, et aucun d'eux ne doit déjà être
 * engagé par une autre participation au même GN.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ChainePersonnagesCoherente extends Constraint
{
    public string $messageDoublonInterne = 'Le personnage « {{ personnage }} » ne peut pas tenir à la fois le rôle de {{ role1 }} et de {{ role2 }}.';

    public string $messageDejaEngage = 'Le personnage « {{ personnage }} » est déjà engagé sur ce GN par la participation de {{ joueur }}.';

    public string $messageAutreJoueur = 'Le personnage « {{ personnage }} » appartient à un autre joueur.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
