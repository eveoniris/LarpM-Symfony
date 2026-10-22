<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\EtiquetteCouleur;
use App\Enum\EtiquetteNature;
use App\Enum\EtiquetteSituation;

/**
 * Ce que le dépouilleur saisit pour une étiquette (rempli directement par le formulaire).
 */
final class EtiquetteSaisie
{
    public EtiquetteNature $nature = EtiquetteNature::OBJET;

    public EtiquetteCouleur $couleur = EtiquetteCouleur::ORANGE;

    /** Numéro en haut à gauche. */
    public ?int $numero = null;

    /** Qualité en haut à droite : trois chiffres. */
    public ?string $qualite = null;

    public int $quantite = 1;

    public EtiquetteSituation $situation = EtiquetteSituation::SANS_OBJET;

    public ?string $remarque = null;
}
