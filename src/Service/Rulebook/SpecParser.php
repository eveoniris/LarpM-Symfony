<?php

declare(strict_types=1);

namespace App\Service\Rulebook;

use App\Enum\RulebookStatut;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Lit le fichier de spec de gameplay (gameplay-rules-precise.md) et en tire une règle par titre de niveau 2.
 *
 * - Le domaine est le texte avant " — " dans le titre ("Territoire — Richesse" donne le domaine "Territoire").
 *   Quelques titres sans tiret sont rattachés à un domaine par la table DOMAINES_SANS_TIRET.
 * - Le détail technique reprend le corps de la section tel quel (rien n'est perdu), précédé de la référence au code
 *   quand le titre en porte une.
 * - Le statut vient de la section "Statut" (premier marqueur reconnu), ou du titre pour "À CONFIRMER".
 * - La version claire n'existe pas dans la spec : elle sera rédigée en ligne par l'équipe règle.
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
final class SpecParser
{
    /** Titres sans " — " : début du titre (en minuscules) => domaine. */
    private const array DOMAINES_SANS_TIRET = [
        'capacité d\'un groupe' => 'Groupe',
        'suivi des sessions gn' => 'GN',
        'score d\'initiative' => 'Initiative',
        'bonus d\'origine' => 'Origine',
        'ancien calcul du bonus d\'origine' => 'Origine',
    ];

    /** Marqueurs de statut reconnus dans la section "Statut". */
    private const array MARQUEURS = [
        '🐛' => RulebookStatut::BUGUE,
        'BUGUÉ' => RulebookStatut::BUGUE,
        '✔' => RulebookStatut::FIXE,
        'FIXÉ' => RulebookStatut::FIXE,
        '⚠' => RulebookStatut::TODO,
        'TODO' => RulebookStatut::TODO,
        '🔒' => RulebookStatut::HARDCODE,
        'HARDCODÉ' => RulebookStatut::HARDCODE,
        '✅' => RulebookStatut::VALIDE,
        'VALIDE' => RulebookStatut::VALIDE,
        'À CONFIRMER' => RulebookStatut::A_CONFIRMER,
    ];

    /** @return list<RegleImportee> */
    public function parser(string $contenu): array
    {
        $contenu = str_replace(["\r\n", "\r"], "\n", $contenu);
        $morceaux = preg_split('/^## (?=\S)/m', $contenu);
        if (false === $morceaux) {
            return [];
        }

        // Le premier morceau est l'en-tête du fichier (front matter, légende) : ignoré.
        array_shift($morceaux);

        $regles = [];
        foreach ($morceaux as $morceau) {
            $regle = $this->parserSection($morceau);
            if (null !== $regle) {
                $regles[] = $regle;
            }
        }

        return $regles;
    }

    private function parserSection(string $morceau): ?RegleImportee
    {
        $lignes = explode("\n", $morceau);
        $titreBrut = trim((string) array_shift($lignes));
        $corps = $this->retirerSeparateurFinal(implode("\n", $lignes));
        if ('' === $titreBrut) {
            return null;
        }

        $aConfirmer = str_contains(mb_strtoupper($titreBrut), 'À CONFIRMER');

        // Suffixe de suivi après " - " (À CONFIRMER, CODÉ sur..., TRANSITOIRE) : ce n'est pas le titre de la règle.
        $titreSansSuffixe = explode(' - ', $titreBrut, 2)[0];

        [$domaineTitre, $titreRegle] = $this->separerDomaine($titreSansSuffixe);
        [$titre, $reference] = $this->separerReference($titreRegle);

        $detail = '' === $reference ? $corps : \sprintf("Référence : %s\n\n%s", $reference, $corps);

        $sections = $this->decouperSections($corps);
        [$statut, $detecte] = $aConfirmer ? [RulebookStatut::A_CONFIRMER, true] : $this->detecterStatut($sections['statut'] ?? '');

        return new RegleImportee(
            domaineCode: $this->slug($domaineTitre),
            domaineTitre: $domaineTitre,
            titre: $titre,
            detail: trim($detail),
            statut: $statut,
            statutDetecte: $detecte,
            testsRefs: $this->extraireTests($sections['tests'] ?? '', $corps),
        );
    }

    /** @return array{string, string} [domaine, titre de la règle] */
    private function separerDomaine(string $titre): array
    {
        if (str_contains($titre, ' — ')) {
            [$domaine, $reste] = explode(' — ', $titre, 2);

            return [trim($domaine), trim($reste)];
        }

        $minuscules = mb_strtolower($titre);
        foreach (self::DOMAINES_SANS_TIRET as $debut => $domaine) {
            if (str_starts_with($minuscules, $debut)) {
                return [$domaine, trim($titre)];
            }
        }

        return ['Divers', trim($titre)];
    }

    /**
     * Sépare le titre de la référence au code ("(`Classe::methode()`)") quand le premier groupe entre parenthèses en porte.
     *
     * @return array{string, string} [titre, référence]
     */
    private function separerReference(string $titre): array
    {
        $position = mb_strpos($titre, ' (');
        if (false === $position) {
            return [$titre, ''];
        }

        $reste = mb_substr($titre, $position + 1);
        if (!str_contains($reste, '`')) {
            return [$titre, ''];
        }

        return [trim(mb_substr($titre, 0, $position)), trim($reste, '() ')];
    }

    /** @return array<string, string> sections de niveau 3 indexées par titre en minuscules */
    private function decouperSections(string $corps): array
    {
        $morceaux = preg_split('/^### (.+)$/m', $corps, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (false === $morceaux) {
            return [];
        }

        $sections = [];
        for ($i = 1, $nombre = \count($morceaux); $i < $nombre; $i += 2) {
            $cle = mb_strtolower(trim($morceaux[$i]));
            $sections[$cle] = ($sections[$cle] ?? '') . "\n" . ($morceaux[$i + 1] ?? '');
        }

        return $sections;
    }

    /** @return array{RulebookStatut, bool} */
    private function detecterStatut(string $texte): array
    {
        $meilleur = null;
        $meilleurePosition = null;
        foreach (self::MARQUEURS as $marqueur => $statut) {
            $position = mb_strpos($texte, $marqueur);
            if (false !== $position && (null === $meilleurePosition || $position < $meilleurePosition)) {
                $meilleur = $statut;
                $meilleurePosition = $position;
            }
        }

        return null === $meilleur ? [RulebookStatut::A_CONFIRMER, false] : [$meilleur, true];
    }

    /**
     * Tests cités dans la section "Tests" ou sur une ligne "Tests :" (chemins ou classes entre accents graves).
     *
     * @return list<string>
     */
    private function extraireTests(string $sectionTests, string $corps): array
    {
        $texte = $sectionTests;
        if (preg_match_all('/^.*\bTests?\s*:.*$/mu', $corps, $lignes)) {
            $texte .= "\n" . implode("\n", $lignes[0]);
        }

        if (!preg_match_all('/`([^`]+)`/u', $texte, $correspondances)) {
            return [];
        }

        $tests = [];
        foreach ($correspondances[1] as $candidat) {
            if (str_contains($candidat, 'Test') || str_contains($candidat, 'tests/')) {
                $tests[] = trim($candidat);
            }
        }

        return array_values(array_unique($tests));
    }

    private function retirerSeparateurFinal(string $corps): string
    {
        return trim((string) preg_replace('/\n-{3,}\s*$/', '', rtrim($corps)));
    }

    private function slug(string $texte): string
    {
        return mb_substr(strtolower((string) (new AsciiSlugger('fr'))->slug($texte)), 0, 40);
    }
}
