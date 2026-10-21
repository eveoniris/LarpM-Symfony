<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Classe;
use App\Entity\Competence;
use App\Entity\Genre;
use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\Langue;
use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Chiffres de la page /statistique pour un GN, calculés à partir des participants du GN.
 *
 * Les participants dont le billet ne compte pas dans les places (aide de camp) sont exclus des compteurs.
 */
readonly class StatistiqueDashboardService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{
     *     participants: int, joueurs: int, personnages: int, etatCivil: int, sansGroupe: int,
     *     placesTotal: int, placesPrises: int, placesLibres: int,
     *     tauxPersonnages: float, tauxEtatCivil: float, tauxPlaces: float
     * }
     */
    public function getKpis(Gn $gn): array
    {
        $participants = $this->countParticipants($gn, 'COUNT(part.id)');
        $joueurs = $this->countParticipants($gn, 'COUNT(DISTINCT part.user)');
        $personnages = $this->countParticipants($gn, 'COUNT(part.id)', 'part.personnage IS NOT NULL');
        $etatCivil = $this->countParticipants($gn, 'COUNT(DISTINCT part.user)', 'u.etatCivil IS NOT NULL');
        $sansGroupe = $this->countParticipants($gn, 'COUNT(part.id)', 'part.groupeGn IS NULL');

        $placesTotal = 0;
        $placesPrises = 0;
        foreach ($this->getGroupesGnJoueurs($gn) as $groupeGn) {
            $placesTotal += $groupeGn->getPlaceTotal();
            $placesPrises += $groupeGn->getNombreMembres();
        }

        return [
            'participants' => $participants,
            'joueurs' => $joueurs,
            'personnages' => $personnages,
            'etatCivil' => $etatCivil,
            'sansGroupe' => $sansGroupe,
            'placesTotal' => $placesTotal,
            'placesPrises' => $placesPrises,
            'placesLibres' => $placesTotal - $placesPrises,
            'tauxPersonnages' => $this->pourcentage($personnages, $placesTotal),
            'tauxEtatCivil' => $this->pourcentage($etatCivil, $joueurs),
            'tauxPlaces' => $this->pourcentage($placesPrises, $placesTotal),
        ];
    }

    /**
     * Jeux de données des graphiques : chaque entrée est une liste de ['label' => string, 'value' => int].
     *
     * @return array{langues: list<array{label: string, value: int}>, classes: list<array{label: string, value: int}>, genres: list<array{label: string, value: int}>, competences: list<array{label: string, value: int}>, competencesFamily: list<array{label: string, value: int}>, constructions: list<array{label: string, value: int}>}
     */
    public function getRepartitions(Gn $gn): array
    {
        $em = $this->entityManager;

        $langues = $em
            ->createQueryBuilder()
            ->select('l.label', 'COUNT(t.id) AS value')
            ->from(Langue::class, 'l')
            ->leftJoin('l.territoires', 't')
            ->leftJoin('t.groupe', 'g')
            ->leftJoin('g.groupeGns', 'ggn')
            ->where('ggn.gn = :gn')
            ->orWhere('t.groupe IS NULL')
            ->groupBy('l.id', 'l.label')
            ->orderBy('l.label')
            ->setParameter('gn', $gn)
            ->getQuery()
            ->getArrayResult();

        $classes = $em
            ->getRepository(Classe::class)
            ->getQueryBuilderFindAllOrderedByLabel()
            ->select('c.label_masculin AS label', 'COUNT(p.id) AS value')
            ->leftJoin('c.personnages', 'p')
            ->innerJoin('p.participants', 'part')
            ->where('part.gn = :gn')
            ->groupBy('c.id', 'c.label_masculin')
            ->setParameter('gn', $gn)
            ->getQuery()
            ->getArrayResult();

        $competences = $em
            ->getRepository(Competence::class)
            ->getQueryBuilderFindAllOrderedByLabel()
            ->select('cf.label AS family_label', 'l.label AS level_label', 'COUNT(p.id) AS value')
            ->leftJoin('c.personnages', 'p')
            ->innerJoin('p.participants', 'part')
            ->where('part.gn = :gn')
            ->groupBy('c.id', 'cf.label', 'l.index', 'l.label')
            ->setParameter('gn', $gn)
            ->getQuery()
            ->getArrayResult();

        $genres = $em
            ->createQueryBuilder()
            ->select('g.label', 'COUNT(p.id) AS value')
            ->from(Genre::class, 'g')
            ->leftJoin('g.personnages', 'p')
            ->innerJoin('p.participants', 'part')
            ->where('part.gn = :gn')
            ->groupBy('g.id', 'g.label')
            ->orderBy('g.label')
            ->setParameter('gn', $gn)
            ->getQuery()
            ->getArrayResult();

        $constructions = $em
            ->createQueryBuilder()
            ->select('t.nom AS label', 'COUNT(con.id) AS value')
            ->from('App\\Entity\\Territoire', 't')
            ->leftJoin('t.constructions', 'con')
            ->leftJoin('t.groupe', 'g')
            ->leftJoin('g.groupeGns', 'ggn')
            ->where('ggn.gn = :gn')
            ->orWhere('t.groupe IS NULL')
            ->groupBy('t.id', 't.nom')
            ->orderBy('t.nom')
            ->setParameter('gn', $gn)
            ->getQuery()
            ->getArrayResult();

        $parNiveau = [];
        $parFamille = [];
        foreach ($competences as $row) {
            $parNiveau[] = ['label' => $row['family_label'] . ' - ' . $row['level_label'], 'value' => (int) $row['value']];
            $parFamille[$row['family_label']] = ($parFamille[$row['family_label']] ?? 0) + (int) $row['value'];
        }

        $competencesFamily = [];
        foreach ($parFamille as $label => $value) {
            $competencesFamily[] = ['label' => (string) $label, 'value' => $value];
        }

        return [
            'langues' => $this->toSeries($langues),
            'classes' => $this->toSeries($classes),
            'genres' => $this->toSeries($genres),
            'competences' => $parNiveau,
            'competencesFamily' => $competencesFamily,
            'constructions' => $this->toSeries($constructions),
        ];
    }

    /**
     * Groupes PJ du GN (les groupes PNJ sont exclus), avec leurs fiefs et participants chargés d'un coup.
     *
     * @return list<GroupeGn>
     */
    private function getGroupesGnJoueurs(Gn $gn): array
    {
        return $this->entityManager
            ->getRepository(GroupeGn::class)
            ->createQueryBuilder('gg')
            ->innerJoin('gg.groupe', 'g')
            ->addSelect('g')
            ->leftJoin('g.territoires', 't')
            ->addSelect('t')
            ->leftJoin('gg.participants', 'p')
            ->addSelect('p')
            ->leftJoin('p.billet', 'b')
            ->addSelect('b')
            ->where('gg.gn = :gn')
            ->andWhere('g.pj = true OR g.pj IS NULL')
            ->setParameter('gn', $gn)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte sur les participants du GN qui comptent dans les places (aides de camp exclus).
     */
    private function countParticipants(Gn $gn, string $select, ?string $condition = null): int
    {
        /** @var QueryBuilder $qb */
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select($select)
            ->from(Participant::class, 'part')
            ->innerJoin('part.user', 'u')
            ->leftJoin('part.billet', 'b')
            ->where('part.gn = :gn')
            ->andWhere('b.id IS NULL OR b.compteDansPlaces = true')
            ->setParameter('gn', $gn);

        if (null !== $condition) {
            $qb->andWhere($condition);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function pourcentage(int $valeur, int $total): float
    {
        return $total > 0 ? min(100.0, round(($valeur * 100) / $total, precision: 1)) : 0.0;
    }

    /**
     * @param list<array{label: string, value: int|string}> $rows
     *
     * @return list<array{label: string, value: int}>
     */
    private function toSeries(array $rows): array
    {
        return array_map(static fn (array $row): array => ['label' => (string) $row['label'], 'value' => (int) $row['value']], $rows);
    }
}
