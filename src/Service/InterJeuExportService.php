<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\InterJeu;
use DateTimeImmutable;
use RuntimeException;
use ZipArchive;

/**
 * Export des tableaux de statistiques d'un inter-jeu : un CSV par tableau, ou tous dans un zip.
 *
 * Version anonyme (comptes seulement) pour tous les lecteurs ; les tableaux nominatifs (identifiant et nom
 * des personnages) sont réservés à la gestion des inter-jeux, le contrôleur le vérifie.
 */
final readonly class InterJeuExportService
{
    public function __construct(
        private InterJeuStatistiqueService $statistiqueService,
        private StatsService $statsService,
    ) {
    }

    /**
     * @return list<list<string|int>>
     */
    public function tableau(InterJeu $inter, string $tableau): array
    {
        return match ($tableau) {
            'familles-cles' => $this->comptes($this->statistiqueService->getStatistiques($inter)['cles']),
            'toutes-familles' => $this->comptes($this->statistiqueService->getStatistiques($inter)['familles']),
            'personnages-par-famille' => $this->personnagesParFamille($inter),
            'personnages' => $this->statistiqueService->getLignesCsv($inter),
            default => throw new RuntimeException(\sprintf('Tableau inconnu : %s', $tableau)),
        };
    }

    /**
     * Zip des tableaux autorisés et d'un lisez-moi ; le fichier temporaire est à supprimer après envoi.
     */
    public function zip(InterJeu $inter, bool $nominatif): string
    {
        $chemin = tempnam(sys_get_temp_dir(), prefix: 'interjeu-export-');
        $zip = new ZipArchive();
        if (false === $chemin || true !== $zip->open($chemin, ZipArchive::OVERWRITE)) {
            throw new RuntimeException('Impossible de créer l\'archive.');
        }

        foreach (array_keys(InterJeuExportFichier::tableauxDisponibles($nominatif)) as $tableau) {
            $zip->addFromString($tableau . '.csv', InterJeuExportFichier::csv($this->tableau($inter, $tableau)));
        }
        $zip->addFromString('lisez-moi.txt', $this->lisezMoi($inter, $nominatif));
        $zip->close();

        return $chemin;
    }

    /**
     * @param list<array{label: string, total: int, niveaux: array<int, int>, ...}> $familles
     *
     * @return list<list<string|int>>
     */
    private function comptes(array $familles): array
    {
        $lignes = [array_merge(['Famille', 'Personnages'], array_values(InterJeuStatistiqueService::NIVEAUX))];
        foreach ($familles as $famille) {
            $ligne = [$famille['label'], $famille['total']];
            foreach (array_keys(InterJeuStatistiqueService::NIVEAUX) as $index) {
                $ligne[] = $famille['niveaux'][$index] ?? 0;
            }
            $lignes[] = $ligne;
        }

        return $lignes;
    }

    /**
     * Une ligne par personnage et par famille, à son niveau le plus haut.
     *
     * @return list<list<string|int>>
     */
    private function personnagesParFamille(InterJeu $inter): array
    {
        /** @var list<array{competence: string, personnageId: int, nom: string, classe: ?string, niveau: string}> $resultats */
        $resultats = $this->statsService->getPersonnagesCompetencesInterJeu($inter)->getResult();
        $lignes = [];
        foreach ($resultats as $ligne) {
            $lignes[] = [(string) $ligne['competence'], (int) $ligne['personnageId'], (string) $ligne['nom'], (string) ($ligne['classe'] ?? ''), (string) $ligne['niveau']];
        }
        usort($lignes, static fn (array $a, array $b): int => [$a[0], $a[2]] <=> [$b[0], $b[2]]);

        return [['Famille', 'ID personnage', 'Nom', 'Classe', 'Niveau'], ...$lignes];
    }

    private function lisezMoi(InterJeu $inter, bool $nominatif): string
    {
        $lignes = [
            \sprintf('Statistiques de l\'inter-jeu « %s »', $inter->getNom()),
            \sprintf('Export du %s, version %s.', (new DateTimeImmutable())->format('d/m/Y H:i'), $nominatif ? 'nominative' : 'anonyme'),
            '',
            'Personnages inscrits et vivants uniquement (les désistés et les personnages en attente ne comptent pas).',
            'Un personnage compte une fois par famille de compétence, à son niveau le plus haut.',
            'Les compétences secrètes ne sont pas comptées.',
            '',
            'familles-cles.csv : Vol, Herboristerie, Alchimie, Commerce, Richesse, Magie.',
            'toutes-familles.csv : toutes les familles de compétences.',
            'personnages-par-famille.csv (nominatif) : une ligne par personnage et par famille.',
            'personnages.csv (nominatif) : une ligne par personnage, une colonne par famille.',
            '',
        ];
        if ($nominatif) {
            $lignes[] = 'Version nominative : à ne pas diffuser au-delà des organisateurs.';
        }

        return implode("\r\n", $lignes) . "\r\n";
    }
}
