<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Ce que le joueur déclare dans son retour de jeu et que le contenu de l'enveloppe ne porte pas : sortie du jeu et textes libres
 * (gérés par les responsables). Les sorts, prières, potions, la compétence apprise et la mort sont dans ContenuJoueur.
 */
final class DeclarationJoueur
{
    /** Le personnage est sorti du jeu (départ, retraite...). */
    public bool $sortiDuJeu = false;

    public ?string $sortiDetails = null;

    /** Enseignements donnés (au moins expert), en texte libre. */
    public ?string $eleves = null;

    /** Constructions auxquelles le joueur a participé (artisan ou expert), en texte libre. */
    public ?string $constructions = null;

    /** Gains et pertes de renommée, en texte libre. */
    public ?string $renommee = null;

    /** Renommée gagnée (ou perdue si négatif) par le personnage, appliquée à la validation une fois confirmée par un responsable. */
    public ?int $renommeePoints = null;

    /** Maladies, folies et autres précisions, en texte libre. */
    public ?string $precisions = null;

    public ?string $autres = null;

    /**
     * @return array<string, bool|int|string|null>
     */
    public function versTableau(): array
    {
        return [
            'sorti_du_jeu' => $this->sortiDuJeu,
            'sorti_details' => $this->sortiDetails,
            'eleves' => $this->eleves,
            'constructions' => $this->constructions,
            'renommee' => $this->renommee,
            'renommee_points' => $this->renommeePoints,
            'precisions' => $this->precisions,
            'autres' => $this->autres,
        ];
    }

    /**
     * @param array<string, mixed> $donnees
     */
    public static function depuisTableau(array $donnees): self
    {
        $declaration = new self();
        $declaration->sortiDuJeu = true === ($donnees['sorti_du_jeu'] ?? false);
        $declaration->sortiDetails = self::texte($donnees['sorti_details'] ?? null);
        $declaration->eleves = self::texte($donnees['eleves'] ?? null);
        $declaration->constructions = self::texte($donnees['constructions'] ?? null);
        $declaration->renommee = self::texte($donnees['renommee'] ?? null);
        $points = $donnees['renommee_points'] ?? null;
        $declaration->renommeePoints = \is_int($points) && 0 !== $points ? $points : null;
        $declaration->precisions = self::texte($donnees['precisions'] ?? null);
        $declaration->autres = self::texte($donnees['autres'] ?? null);

        return $declaration;
    }

    private static function texte(mixed $valeur): ?string
    {
        return \is_string($valeur) && '' !== trim($valeur) ? trim($valeur) : null;
    }
}
