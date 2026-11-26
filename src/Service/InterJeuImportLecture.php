<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Lecture d'une liste de participants d'inter-jeu : identifiants saisis à la main et fichier CSV
 * (1re colonne l'identifiant, 2e colonne facultative le statut).
 */
final class InterJeuImportLecture
{
    /**
     * @return array<int, string> identifiant => statut saisi ('' = statut par défaut) ; le CSV l'emporte sur la saisie
     */
    public static function lire(string $idsSaisis, ?string $cheminCsv): array
    {
        $saisis = array_fill_keys(self::identifiants(preg_split('/[\s,;]+/', $idsSaisis) ?: []), '');

        return null === $cheminCsv ? $saisis : array_replace($saisis, self::lireCsv($cheminCsv));
    }

    /**
     * @param array<array-key, mixed> $valeurs
     *
     * @return list<int>
     */
    private static function identifiants(array $valeurs): array
    {
        return array_values(array_map(intval(...), array_filter($valeurs, static fn (mixed $v): bool => \is_string($v) && ctype_digit($v))));
    }

    /**
     * Séparateur virgule ou point-virgule (export Excel français), BOM UTF-8 retiré, en-tête et lignes vides ignorés.
     *
     * @return array<int, string> identifiant => statut saisi (éventuellement vide)
     */
    private static function lireCsv(string $chemin): array
    {
        $contenu = (string) file_get_contents($chemin);
        $contenu = str_starts_with($contenu, "\xEF\xBB\xBF") ? substr($contenu, offset: 3) : $contenu;
        $lignesBrutes = preg_split('/\r\n|\r|\n/', $contenu) ?: [];
        $premiere = $lignesBrutes[0] ?? '';
        $separateur = substr_count($premiere, ';') > substr_count($premiere, ',') ? ';' : ',';

        $lignes = [];
        foreach ($lignesBrutes as $brute) {
            $colonnes = str_getcsv($brute, $separateur, enclosure: '"', escape: '');
            $id = trim((string) ($colonnes[0] ?? ''));
            if (ctype_digit($id)) {
                $lignes[(int) $id] = trim((string) ($colonnes[1] ?? ''));
            }
        }

        return $lignes;
    }
}
