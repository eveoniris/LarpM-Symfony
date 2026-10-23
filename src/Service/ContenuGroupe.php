<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Ce que le dépouilleur constate dans l'enveloppe d'un groupe, d'après la fiche "Instructions Retour de jeu GROUPE" :
 * fiche remplie, matériel de jeu individuel non gardé par les joueurs, et le tableau des 24 cases (pièces, ingrédients,
 * potions et ressources collectés par le groupe).
 *
 * Rempli directement par le formulaire ; ne contient que des valeurs simples, gardées telles quelles dans le brouillon.
 */
final class ContenuGroupe
{
    /** Clés du brouillon JSON => propriété entière du matériel individuel. */
    public const array MATERIEL = [
        'bandeaux_blancs' => 'Bandeaux blancs',
        'bandeaux_rouges' => 'Bandeaux rouges',
        'pieges' => 'Pièges',
        'cadenas' => 'Cadenas avec leurs clés',
        'broches_renommee' => 'Broches de renommée',
        'bracelets_langues' => 'Bracelets de langues Eve-Oniris',
        'panneaux_hors_jeu' => 'Panneaux hors-jeu',
        'brassards_arbitre' => 'Brassards d\'arbitre',
        'brassards_sensible' => 'Brassards de personne sensible',
    ];

    public bool $ficheRetour = false;

    /** @var array<string, int> quantité de chaque élément de MATERIEL */
    public array $materiel = [];

    /** Autre matériel de jeu individuel laissé dans l'enveloppe, en texte libre. */
    public ?string $autreMateriel = null;

    /** Les 24 cases du tableau de la fiche. @var array<string, int> */
    public array $compteurs = [];

    /**
     * @return array<string, mixed>
     */
    public function versTableau(): array
    {
        return [
            'fiche_retour' => $this->ficheRetour,
            'materiel' => $this->materielNormalise(),
            'autre_materiel' => $this->autreMateriel,
            'compteurs' => CompteursEnveloppe::normaliser($this->compteurs),
        ];
    }

    /**
     * @param array<string, mixed> $donnees
     */
    public static function depuisTableau(array $donnees): self
    {
        $contenu = new self();
        $contenu->ficheRetour = (bool) ($donnees['fiche_retour'] ?? false);
        $contenu->materiel = self::normaliserMateriel($donnees['materiel'] ?? []);
        $contenu->autreMateriel = $donnees['autre_materiel'] ?? null;
        $contenu->compteurs = CompteursEnveloppe::normaliser($donnees['compteurs'] ?? []);

        return $contenu;
    }

    /**
     * @return array<string, int>
     */
    public function materielNormalise(): array
    {
        return self::normaliserMateriel($this->materiel);
    }

    /**
     * @param mixed $valeurs
     *
     * @return array<string, int>
     */
    private static function normaliserMateriel(mixed $valeurs): array
    {
        $valeurs = \is_array($valeurs) ? $valeurs : [];
        $normalise = [];
        foreach (array_keys(self::MATERIEL) as $cle) {
            $normalise[$cle] = max(0, (int) ($valeurs[$cle] ?? 0));
        }

        return $normalise;
    }
}
