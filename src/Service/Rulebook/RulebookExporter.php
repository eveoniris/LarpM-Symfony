<?php

declare(strict_types=1);

namespace App\Service\Rulebook;

use App\Entity\RulebookDomaine;
use App\Entity\RulebookRegle;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

/**
 * Cache local du rulebook : un fichier markdown par domaine (docs/rulebook/<domaine>.md) et un index.
 *
 * La base reste la source de vérité. Ces fichiers servent de référence rapide en développement et aux sessions de
 * travail ; ils sont régénérés à chaque enregistrement d'une règle et au déploiement, et ne sont pas versionnés.
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
final readonly class RulebookExporter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $cacheDir,
    ) {
    }

    /**
     * Écrit les fichiers du cache. Sans code de domaine : tous les domaines, l'index, et suppression des fichiers obsolètes.
     *
     * @return list<string> chemins des fichiers écrits
     */
    public function exporter(?string $domaineCode = null): array
    {
        if (!is_dir($this->cacheDir) && !mkdir($this->cacheDir, 0o775, recursive: true) && !is_dir($this->cacheDir)) {
            throw new RuntimeException(\sprintf('Impossible de créer le dossier "%s".', $this->cacheDir));
        }

        $domaines = $this->entityManager->getRepository(RulebookDomaine::class)->findBy([], ['position' => 'ASC', 'id' => 'ASC']);
        $ecrits = [];
        $index = [];

        foreach ($domaines as $domaine) {
            $regles = $this->regles($domaine);
            $fichier = $this->nomFichier($domaine);
            $index[] = [$domaine, $fichier, $regles];

            if (null !== $domaineCode && $domaine->getCode() !== $domaineCode) {
                continue;
            }

            $ecrits[] = $this->ecrire($fichier, $this->contenuDomaine($domaine, $regles));
        }

        $ecrits[] = $this->ecrire('index.md', $this->contenuIndex($index));

        if (null === $domaineCode) {
            $this->supprimerObsoletes($ecrits);
        }

        return $ecrits;
    }

    /** @return list<RulebookRegle> */
    private function regles(RulebookDomaine $domaine): array
    {
        /** @var list<RulebookRegle> */
        return $this->entityManager->getRepository(RulebookRegle::class)->findBy(['domaine' => $domaine], ['position' => 'ASC', 'id' => 'ASC']);
    }

    private function nomFichier(RulebookDomaine $domaine): string
    {
        $slug = trim((string) preg_replace('/[^a-z0-9_-]+/', '-', mb_strtolower($domaine->getCode())), '-');

        return ('' === $slug ? 'domaine-' . $domaine->getId() : $slug) . '.md';
    }

    /** @param list<RulebookRegle> $regles */
    private function contenuDomaine(RulebookDomaine $domaine, array $regles): string
    {
        $texte = '# ' . $domaine->getTitre() . "\n\n" . $this->avertissement() . "\n";

        foreach ($regles as $regle) {
            $meta = [
                'Statut : ' . $regle->getStatut()->label(),
                $regle->isPubliee() ? 'publiée' : 'non publiée',
                'version ' . $regle->getVersion(),
            ];
            if ([] !== $regle->getTestsRefs()) {
                $meta[] = 'tests : ' . implode(', ', $regle->getTestsRefs());
            }

            $texte .= "\n## " . $regle->getCode() . ' - ' . $regle->getTitre() . "\n\n" . implode(' · ', $meta) . "\n";
            $texte .= "\n### Version claire\n\n" . $this->bloc($regle->getTexteClair(), 'Pas encore rédigée.') . "\n";
            $texte .= "\n### Détail technique\n\n" . $this->bloc($regle->getDetailTechnique(), 'Aucun détail technique.') . "\n";
        }

        return $texte;
    }

    /** @param list<array{0: RulebookDomaine, 1: string, 2: list<RulebookRegle>}> $index */
    private function contenuIndex(array $index): string
    {
        $texte = "# Rulebook (cache local)\n\n" . $this->avertissement() . "\n\n";
        foreach ($index as [$domaine, $fichier, $regles]) {
            $texte .= '## [' . $domaine->getTitre() . '](' . $fichier . ")\n\n";
            foreach ($regles as $regle) {
                $texte .= '- ' . $regle->getCode() . ' - ' . $regle->getTitre() . ' (' . $regle->getStatut()->label() . ")\n";
            }
            $texte .= "\n";
        }

        return $texte;
    }

    private function avertissement(): string
    {
        return '> Cache local généré depuis la base (app:rulebook:exporter). Ne pas modifier : toute modification est écrasée. La source de vérité est le rulebook en ligne.';
    }

    private function bloc(?string $texte, string $vide): string
    {
        return null === $texte || '' === trim($texte) ? '_' . $vide . '_' : trim($texte);
    }

    private function ecrire(string $nom, string $contenu): string
    {
        $chemin = rtrim($this->cacheDir, '/') . '/' . $nom;
        if (false === file_put_contents($chemin, $contenu, LOCK_EX)) {
            throw new RuntimeException(\sprintf('Écriture impossible : "%s".', $chemin));
        }

        return $chemin;
    }

    /** @param list<string> $conserves */
    private function supprimerObsoletes(array $conserves): void
    {
        foreach (glob(rtrim($this->cacheDir, '/') . '/*.md') ?: [] as $fichier) {
            if (!\in_array($fichier, $conserves, strict: true)) {
                if (is_file($fichier)) {
                    unlink($fichier);
                }
            }
        }
    }
}
