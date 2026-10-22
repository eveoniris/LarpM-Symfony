<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Ingredient;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Ressource;
use App\Entity\Sort;

/**
 * Ce que le dépouilleur constate dans une enveloppe de joueur (rempli directement par le formulaire).
 * Les listes de sorts, prières, ingrédients, ressources et potions sont des entités choisies par auto-complétion.
 */
// @mago-expect lint:too-many-properties
final class ContenuJoueur
{
    // Pièces jointes à l'enveloppe.
    public bool $fichePersonnage = false;

    public bool $ficheRetour = false;

    public bool $ficheSort = false;

    public int $corruptionOuvertes = 0;

    public int $corruptionNonOuvertes = 0;

    public bool $fichePriere = false;

    public bool $carteAlchimiste = false;

    public int $pincesALinge = 0;

    /** Numéros des documents de jeu officiels numérotés (parchemins), en texte libre. */
    public ?string $documentsNumerotes = null;

    public bool $copiesRecettesSorts = false;

    // Appris pendant le jeu.
    /** @var list<Sort> */
    public array $sorts = [];

    /** @var list<Priere> */
    public array $prieres = [];

    /** Recettes (souvent une potion, avec la façon de la réaliser) : texte libre à vérifier par un orga. */
    public ?string $recettes = null;

    /** Ce que le dépouilleur n'a pas trouvé dans les listes : un orga sera sollicité. */
    public ?string $introuvables = null;

    // Les six actions de fin d'Opus : case "utilisée" et "ressources jointes".
    public bool $fruitsUtilise = false;

    public bool $fruitsJointes = false;

    public bool $soieUtilise = false;

    public bool $soieJointes = false;

    public bool $gemmeUtilise = false;

    public bool $gemmeJointes = false;

    public int $gemmePieces = 0;

    public bool $selUtilise = false;

    public bool $selJointes = false;

    /** @var list<Ingredient> */
    public array $selIngredients = [];

    public bool $fourrureUtilise = false;

    public bool $fourrureJointes = false;

    /** @var list<Ressource> */
    public array $fourrureRessources = [];

    public bool $ivoireUtilise = false;

    public bool $ivoireJointes = false;

    /** @var list<Potion> */
    public array $ivoirePotions = [];

    /** Surplus trouvé dans l'enveloppe, à reverser dans l'enveloppe de groupe. @var array<string, int> */
    public array $surplus = [];
}
