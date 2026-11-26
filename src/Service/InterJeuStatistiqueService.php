<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\InterJeu;
use App\Enum\CompetenceFamilyType;

/**
 * Statistiques d'un inter-jeu à transmettre aux orgas des autres associations
 * (petit matériel : voleurs, herboristes, alchimistes, commerce, richesse, magie).
 *
 * Seuls les personnages inscrits et vivants comptent. Un personnage compte une fois par famille,
 * à son niveau le plus haut.
 */
final readonly class InterJeuStatistiqueService
{
    /** Niveaux affichés en colonnes (index de la table level). */
    public const array NIVEAUX = [1 => 'Apprenti', 2 => 'Initié', 3 => 'Expert', 4 => 'Maître', 5 => 'Grand Maître'];

    public function __construct(
        private StatsService $statsService,
    ) {
    }

    /** @return list<CompetenceFamilyType> */
    public static function famillesCles(): array
    {
        return [
            CompetenceFamilyType::THEFT,
            CompetenceFamilyType::HERBALISM,
            CompetenceFamilyType::ALCHEMY,
            CompetenceFamilyType::BUSINESS,
            CompetenceFamilyType::WEALTH,
            CompetenceFamilyType::MAGIC,
        ];
    }

    /**
     * @return array{
     *     inscrits: int,
     *     cles: list<array{label: string, total: int, niveaux: array<int, int>, personnages: list<array{personnageId: int, nom: string, classe: ?string, niveau: string}>}>,
     *     familles: list<array{label: string, total: int, niveaux: array<int, int>}>
     * }
     */
    public function getStatistiques(InterJeu $inter): array
    {
        $lignes = $this->statsService->getPersonnagesCompetencesInterJeu($inter)->getResult();

        /** @var array<int, array{label: string, total: int, niveaux: array<int, int>, personnages: list<array{personnageId: int, nom: string, classe: ?string, niveau: string}>}> $parFamille */
        $parFamille = [];
        foreach ($lignes as $ligne) {
            $id = (int) $ligne['familleId'];
            $parFamille[$id] ??= ['label' => (string) $ligne['competence'], 'total' => 0, 'niveaux' => array_fill_keys(array_keys(self::NIVEAUX), 0), 'personnages' => []];
            ++$parFamille[$id]['total'];
            $niveau = (int) $ligne['niveauMax'];
            $parFamille[$id]['niveaux'][$niveau] = ($parFamille[$id]['niveaux'][$niveau] ?? 0) + 1;
            $parFamille[$id]['personnages'][] = [
                'personnageId' => (int) $ligne['personnageId'],
                'nom' => (string) $ligne['nom'],
                'classe' => $ligne['classe'],
                'niveau' => (string) $ligne['niveau'],
            ];
        }

        $cles = [];
        foreach (self::famillesCles() as $famille) {
            $cles[] = $parFamille[$famille->getId()] ?? [
                'label' => $famille->getLabel(),
                'total' => 0,
                'niveaux' => array_fill_keys(array_keys(self::NIVEAUX), 0),
                'personnages' => [],
            ];
        }

        $familles = array_map(static fn (array $famille): array => ['label' => $famille['label'], 'total' => $famille['total'], 'niveaux' => $famille['niveaux']], array_values($parFamille));
        usort($familles, static fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return [
            'inscrits' => \count($this->statsService->getPersonnagesInscritsInterJeu($inter)->getResult()),
            'cles' => $cles,
            'familles' => $familles,
        ];
    }

    /**
     * Export CSV : une ligne par personnage inscrit, une colonne par famille (niveau le plus haut).
     *
     * @return list<list<string|int>>
     */
    public function getLignesCsv(InterJeu $inter): array
    {
        $niveaux = [];
        $familles = [];
        foreach ($this->statsService->getPersonnagesCompetencesInterJeu($inter)->getResult() as $ligne) {
            $familles[(int) $ligne['familleId']] = (string) $ligne['competence'];
            $niveaux[(int) $ligne['personnageId']][(int) $ligne['familleId']] = (string) $ligne['niveau'];
        }

        // Familles clés d'abord, puis les autres par ordre alphabétique
        $ordre = [];
        foreach (self::famillesCles() as $famille) {
            $ordre[$famille->getId()] = $familles[$famille->getId()] ?? $famille->getLabel();
        }
        $autres = array_diff_key($familles, $ordre);
        asort($autres);
        $ordre += $autres;

        $csv = [array_merge(['ID personnage', 'Nom', 'Classe'], array_values($ordre))];
        foreach ($this->statsService->getPersonnagesInscritsInterJeu($inter)->getResult() as $personnage) {
            $id = (int) $personnage['personnageId'];
            $ligne = [$id, self::celluleSure((string) $personnage['nom']), self::celluleSure((string) ($personnage['classe'] ?? ''))];
            foreach (array_keys($ordre) as $familleId) {
                $ligne[] = $niveaux[$id][$familleId] ?? '';
            }
            $csv[] = $ligne;
        }

        return $csv;
    }

    /**
     * Neutralise une valeur saisie par un joueur qu'un tableur prendrait pour une formule (=, +, -, @, tabulation, retour).
     */
    public static function celluleSure(string $valeur): string
    {
        return '' !== $valeur && str_contains("=+-@\t\r", $valeur[0]) ? "'" . $valeur : $valeur;
    }
}
