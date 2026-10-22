<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Les 24 cases du tableau de la fiche de retour de groupe (pièces, ingrédients, potions et ressources), réutilisées pour le
 * surplus trouvé dans une enveloppe de joueur, à reverser dans l'enveloppe de groupe.
 *
 * Les clés sont celles des colonnes de FicheRetourGroupe. La fiche papier dit "Encens" là où la base a "Teintures" : on garde
 * "Teintures" tant que les règles ne changent pas.
 */
final class CompteursEnveloppe
{
    /** @var array<string, string> clé => libellé de la fiche */
    public const array CHAMPS = [
        'pieces_argent' => 'Pièces d\'argent',
        'pieces_or' => 'Pièces d\'or',
        'nb_ingredients' => 'Nombre total d\'ingrédients',
        'nb_potions' => 'Nombre total de potions',
        'armement' => 'Armement',
        'chevaux' => 'Chevaux',
        'fruits_legumes' => 'Fruits et légumes',
        'matieres_simples' => 'Métaux simples',
        'sel' => 'Sel',
        'betail' => 'Bétail',
        'coton' => 'Cotons',
        'gemmes' => 'Gemmes',
        'moutons' => 'Moutons',
        'soie' => 'Soie',
        'bois' => 'Bois',
        'esclaves' => 'Esclaves',
        'ivoire' => 'Ivoire',
        'pierre' => 'Pierre',
        'teinture' => 'Teintures',
        'cereales' => 'Céréales',
        'fourrures' => 'Fourrures',
        'matieres_precieux' => 'Métaux précieux',
        'poisson' => 'Poisson',
        'vin' => 'Vin',
    ];

    /**
     * Ne garde que les cases connues, en entiers positifs ou nuls (les cases vides valent 0).
     *
     * @param array<string, mixed> $valeurs
     *
     * @return array<string, int>
     */
    public static function normaliser(array $valeurs): array
    {
        $normalisees = [];
        foreach (array_keys(self::CHAMPS) as $cle) {
            $normalisees[$cle] = max(0, (int) ($valeurs[$cle] ?? 0));
        }

        return $normalisees;
    }

    /**
     * @param array<string, mixed> $valeurs
     */
    public static function total(array $valeurs): int
    {
        return array_sum(self::normaliser($valeurs));
    }

    /**
     * Cases non nulles seulement, avec leur libellé, pour l'affichage.
     *
     * @param array<string, mixed> $valeurs
     *
     * @return array<string, int> libellé => quantité
     */
    public static function nonNulles(array $valeurs): array
    {
        $resultat = [];
        foreach (self::normaliser($valeurs) as $cle => $quantite) {
            if ($quantite <= 0) {
                continue;
            }

            $resultat[self::CHAMPS[$cle]] = $quantite;
        }

        return $resultat;
    }
}
