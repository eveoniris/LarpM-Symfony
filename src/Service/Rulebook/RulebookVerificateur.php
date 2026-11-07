<?php

declare(strict_types=1);

namespace App\Service\Rulebook;

use App\Entity\RulebookRegle;
use App\Enum\RulebookStatut;

/**
 * Relie le rulebook aux tests.
 *
 * Une règle est couverte si elle référence au moins un test (champ « tests associés ») ou si un test porte son code
 * en tag (`#[Group('XP-03')]`). Les règles buguées, à confirmer ou à faire ne sont pas attendues avec un test.
 */
final readonly class RulebookVerificateur
{
    /**
     * @param list<RulebookRegle> $regles
     * @param array<string, string> $classesDeTests nom de classe (ou chemin sans extension) => chemin du fichier
     * @param array<string, list<string>> $codesTagues code de règle => fichiers de tests qui le citent en tag
     */
    public function verifier(array $regles, array $classesDeTests, array $codesTagues): RulebookVerification
    {
        $sansTest = [];
        $cassees = [];
        $codesConnus = [];

        foreach ($regles as $regle) {
            $code = $regle->getCode();
            $codesConnus[$code] = true;

            foreach ($regle->getTestsRefs() as $reference) {
                if (!$this->existe($reference, $classesDeTests)) {
                    $cassees[$code][] = $reference;
                }
            }

            $couverte = [] !== $regle->getTestsRefs() || isset($codesTagues[$code]);
            if (!$couverte && \in_array($regle->getStatut(), [RulebookStatut::VALIDE, RulebookStatut::FIXE], strict: true)) {
                $sansTest[] = $code;
            }
        }

        $inconnus = array_diff_key($codesTagues, $codesConnus);
        ksort($inconnus);

        return new RulebookVerification($sansTest, $cassees, $inconnus);
    }

    /** @param array<string, string> $classesDeTests */
    private function existe(string $reference, array $classesDeTests): bool
    {
        // « Classe::methode* » ou « tests/Chemin/Classe » : seule la classe est vérifiée.
        $nom = trim(explode('::', $reference, limit: 2)[0]);
        $nom = preg_replace('/\.php$/', '', $nom) ?? $nom;

        if (isset($classesDeTests[$nom])) {
            return true;
        }

        // Motif avec joker (ex: OrigineBonus*Test) : au moins une classe ou un chemin correspond.
        if (str_contains($nom, '*')) {
            foreach (array_keys($classesDeTests) as $candidat) {
                if (fnmatch($nom, (string) $candidat)) {
                    return true;
                }
            }

            return false;
        }

        return isset($classesDeTests[basename(str_replace('\\', '/', $nom))]);
    }
}
