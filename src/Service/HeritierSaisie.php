<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Genre;
use App\Entity\Langue;
use App\Entity\Lignee;
use App\Entity\Territoire;

/**
 * Ce que le scénariste choisit pour un héritier à créer (rempli directement par le formulaire).
 */
final class HeritierSaisie
{
    public string $nom = '';

    public ?Genre $genre = null;

    /** Origine de l'héritier (territoire), choisie par le scénariste. */
    public ?Territoire $territoire = null;

    /** Langue des parents transmise à l'héritier (facultative). */
    public ?Langue $langueParents = null;

    public ?Lignee $lignee = null;
}
