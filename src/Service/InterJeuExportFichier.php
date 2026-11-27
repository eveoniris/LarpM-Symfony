<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\InterJeu;
use RuntimeException;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Catalogue des tableaux exportables d'un inter-jeu et mise en forme des fichiers (CSV pour Excel, noms de fichier).
 */
final class InterJeuExportFichier
{
    /** Tableaux exportables => libellé. */
    public const array TABLEAUX = [
        'familles-cles' => 'Familles clés',
        'toutes-familles' => 'Toutes les familles',
        'personnages-par-famille' => 'Personnages par famille (nominatif)',
        'personnages' => 'Personnages et niveaux (nominatif)',
    ];

    /** Tableaux qui contiennent l'identifiant et le nom des personnages : réservés à la gestion des inter-jeux. */
    private const array NOMINATIFS = ['personnages-par-famille', 'personnages'];

    public static function estNominatif(string $tableau): bool
    {
        return \in_array($tableau, self::NOMINATIFS, strict: true) || !\array_key_exists($tableau, self::TABLEAUX);
    }

    /**
     * @return array<string, string> tableau => libellé, sans les tableaux nominatifs si non autorisés
     */
    public static function tableauxDisponibles(bool $nominatif): array
    {
        return array_filter(self::TABLEAUX, static fn (string $tableau): bool => $nominatif || !self::estNominatif($tableau), mode: ARRAY_FILTER_USE_KEY);
    }

    /**
     * CSV prêt pour Excel : BOM UTF-8, séparateur point-virgule, formules neutralisées.
     *
     * @param list<list<string|int>> $lignes
     */
    public static function csv(array $lignes): string
    {
        $flux = fopen('php://temp', mode: 'w+');
        if (false === $flux) {
            throw new RuntimeException('Flux temporaire indisponible.');
        }

        foreach ($lignes as $ligne) {
            $cellules = array_map(static fn (string|int $cellule): string|int => \is_string($cellule) ? InterJeuStatistiqueService::celluleSure($cellule) : $cellule, $ligne);
            fputcsv($flux, $cellules, separator: ';', enclosure: '"', escape: '');
        }
        rewind($flux);
        $contenu = (string) stream_get_contents($flux);
        fclose($flux);

        return "\xEF\xBB\xBF" . $contenu;
    }

    public static function nomFichier(InterJeu $inter, string $suffixe): string
    {
        $nom = (new AsciiSlugger('fr'))
            ->slug($inter->getNom())
            ->lower()
            ->toString();

        return \sprintf('interjeu-%d-%s-%s', $inter->getId(), '' === $nom ? 'inter-jeu' : $nom, $suffixe);
    }
}
