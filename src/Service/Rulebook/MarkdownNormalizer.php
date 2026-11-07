<?php

declare(strict_types=1);

namespace App\Service\Rulebook;

use Normalizer;

/**
 * Nettoie un texte markdown saisi ou collé avant enregistrement : caractères invisibles, caractères de contrôle, espaces
 * exotiques, fins de ligne. Les guillemets et apostrophes typographiques sont conservés.
 *
 * Le trait de fin de ligne markdown (deux espaces pour un saut de ligne forcé) n'est pas conservé : les espaces en fin de
 * ligne sont retirés. Utiliser une ligne vide ou une liste à la place.
 */
final class MarkdownNormalizer
{
    /** Largeur nulle, marques de direction, séparateurs invisibles, BOM, trait d'union conditionnel. */
    private const string INVISIBLES = '/[\x{00AD}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{2066}-\x{2069}\x{FEFF}]/u';

    /** Caractères de contrôle, hors tabulation et saut de ligne (traités à part). */
    private const string CONTROLES = '/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}-\x{009F}]/u';

    /** Espaces insécables et espaces Unicode de largeur fixe, remplacés par une espace normale. */
    private const string ESPACES_EXOTIQUES = '/[\x{00A0}\x{1680}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]/u';

    /** Séparateurs de ligne et de paragraphe Unicode, remplacés par un saut de ligne. */
    private const string SEPARATEURS_LIGNE = '/[\x{0085}\x{2028}\x{2029}]/u';

    public function normaliser(string $texte): MarkdownNettoye
    {
        $nettoyes = 0;

        // Octets qui ne sont pas de l'UTF-8 valide : retirés.
        if (!mb_check_encoding($texte, 'UTF-8')) {
            $valide = iconv('UTF-8', 'UTF-8//IGNORE', $texte);
            $valide = false === $valide ? '' : $valide;
            $nettoyes += max(1, \strlen($texte) - \strlen($valide));
            $texte = $valide;
        }

        // Fins de ligne : \r\n et \r deviennent \n (pas compté, c'est invisible pour l'auteur).
        $texte = str_replace(["\r\n", "\r"], "\n", $texte);

        // Le U+0085 est un contrôle C1 : à traiter avant la suppression des contrôles.
        $texte = $this->remplacer(self::SEPARATEURS_LIGNE, "\n", $texte, $nettoyes);
        $texte = $this->remplacer(self::INVISIBLES, '', $texte, $nettoyes);
        $texte = $this->remplacer(self::CONTROLES, '', $texte, $nettoyes);
        $texte = $this->remplacer(self::ESPACES_EXOTIQUES, ' ', $texte, $nettoyes);

        $nfc = Normalizer::normalize($texte, Normalizer::FORM_C);
        if (\is_string($nfc)) {
            $texte = $nfc;
        }

        // Espaces en fin de ligne et lignes vides en trop aux extrémités.
        $lignes = array_map(static fn (string $ligne): string => rtrim($ligne, " \t"), explode("\n", $texte));
        $texte = trim(implode("\n", $lignes), "\n");

        return new MarkdownNettoye($texte, $nettoyes);
    }

    private function remplacer(string $motif, string $remplacement, string $texte, int &$nettoyes): string
    {
        $resultat = preg_replace($motif, $remplacement, $texte, -1, $compte);

        $nettoyes += $compte;

        return $resultat ?? $texte;
    }
}
