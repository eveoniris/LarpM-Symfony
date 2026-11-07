<?php

declare(strict_types=1);

namespace App\Service\Rulebook;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Parcourt le dossier de tests : classes disponibles et codes de règles cités en tag `#[Group('XP-03')]`.
 */
final readonly class TestsScanner
{
    private const string MOTIF_TAG = '/#\[Group\(\s*[\'"]([A-Z][A-Z0-9]*-\d{2,3})[\'"]\s*\)\]/';

    /**
     * classes : nom de classe ou chemin sans extension => chemin relatif ; codes : code de règle => fichiers.
     *
     * @return array{classes: array<string, string>, codes: array<string, list<string>>}
     */
    public function scanner(string $dossierTests, string $racine): array
    {
        $classes = [];
        $codes = [];

        if (!is_dir($dossierTests)) {
            return ['classes' => $classes, 'codes' => $codes];
        }

        $racineNormalisee = rtrim(str_replace('\\', '/', $racine), '/');
        $iterateur = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dossierTests, FilesystemIterator::SKIP_DOTS));
        foreach ($iterateur as $fichier) {
            if (!$fichier->isFile() || 'php' !== $fichier->getExtension()) {
                continue;
            }

            $chemin = str_replace('\\', '/', $fichier->getPathname());
            $relatif = ltrim(substr($chemin, \strlen($racineNormalisee)), '/');
            $classes[$fichier->getBasename('.php')] = $relatif;
            $classes[preg_replace('/\.php$/', '', $relatif) ?? $relatif] = $relatif;

            if (preg_match_all(self::MOTIF_TAG, (string) file_get_contents($chemin), $trouves) > 0) {
                foreach ($trouves[1] as $code) {
                    if (!\in_array($relatif, $codes[$code] ?? [], strict: true)) {
                        $codes[$code][] = $relatif;
                    }
                }
            }
        }

        return ['classes' => $classes, 'codes' => $codes];
    }
}
