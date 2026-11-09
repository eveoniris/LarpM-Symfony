<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Le retour de jeu d'un groupe : les numéros de personnage des six titres de la session et les ordres, en texte libre, de chacune des
 * trois phases (les ordres de conquête sont validés par le wargame, phase par phase).
 */
// @mago-expect lint:cyclomatic-complexity
final class RetourGroupe
{
    /** @var array<string, string> clé du titre => libellé */
    public const array TITRES = [
        'suzerain' => 'Suzerain',
        'chef_guerre' => 'Chef de guerre',
        'diplomate' => 'Diplomate',
        'intendant' => 'Intendant',
        'navigateur' => 'Navigateur',
        'eminence_grise' => 'Éminence grise',
    ];

    public const int PHASES = 3;

    /** @var array<string, int|null> numéro de personnage de chaque titre */
    public array $titres = [];

    public ?string $ordres1 = null;

    public ?string $ordres2 = null;

    public ?string $ordres3 = null;

    public ?string $precisions = null;

    /**
     * @return array{titres: array<string, int|null>, ordres: array<int, string|null>, precisions: string|null}
     */
    public function versTableau(): array
    {
        $titres = [];
        foreach (array_keys(self::TITRES) as $cle) {
            $titres[$cle] = $this->titres[$cle] ?? null;
        }

        return [
            'titres' => $titres,
            'ordres' => [1 => $this->ordres1, 2 => $this->ordres2, 3 => $this->ordres3],
            'precisions' => $this->precisions,
        ];
    }

    /**
     * @param array<string, mixed> $donnees
     */
    public static function depuisTableau(array $donnees): self
    {
        $retour = new self();
        $titres = \is_array($donnees['titres'] ?? null) ? $donnees['titres'] : [];
        foreach (array_keys(self::TITRES) as $cle) {
            $numero = $titres[$cle] ?? null;
            $retour->titres[$cle] = is_numeric($numero) && (int) $numero > 0 ? (int) $numero : null;
        }

        $ordres = \is_array($donnees['ordres'] ?? null) ? $donnees['ordres'] : [];
        $retour->ordres1 = self::texte($ordres[1] ?? $ordres['1'] ?? null);
        $retour->ordres2 = self::texte($ordres[2] ?? $ordres['2'] ?? null);
        $retour->ordres3 = self::texte($ordres[3] ?? $ordres['3'] ?? null);
        $retour->precisions = self::texte($donnees['precisions'] ?? null);

        return $retour;
    }

    private static function texte(mixed $valeur): ?string
    {
        return \is_string($valeur) && '' !== trim($valeur) ? trim($valeur) : null;
    }
}
