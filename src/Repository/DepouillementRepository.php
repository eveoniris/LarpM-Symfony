<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;

class DepouillementRepository extends BaseRepository
{
    /**
     * Le findAll() de BaseRepository trie par label, que cette entité n'a pas.
     *
     * @return array<int, object>
     */
    public function findAll(): array
    {
        return $this->findBy([], ['id' => 'ASC']);
    }

    /**
     * Dépouillement déjà ouvert pour cette enveloppe (réel ou simulation), quel que soit son statut : sert à avertir d'une
     * reprise ou d'un doublon.
     */
    public function findByEnveloppe(Gn $gn, DepouillementType $type, string $numero, bool $simulation): ?Depouillement
    {
        return $this->findOneBy([
            'gn' => $gn,
            'type' => $type,
            'numero' => $numero,
            'simulation' => $simulation,
        ]);
    }

    /**
     * Simulations d'un GN, de la plus récente à la plus ancienne, avec ce que la liste affiche (groupe, joueur, dépouilleur)
     * chargé en une seule requête.
     *
     * @return list<Depouillement>
     */
    public function findSimulationsAvecContexte(Gn $gn): array
    {
        return array_values(
            $this
                ->createQueryBuilder('d')
                ->addSelect('gg', 'g', 'p', 'u', 'dep')
                ->leftJoin('d.groupeGn', 'gg')
                ->leftJoin('gg.groupe', 'g')
                ->leftJoin('d.participant', 'p')
                ->leftJoin('p.user', 'u')
                ->leftJoin('d.depouilleur', 'dep')
                ->where('d.gn = :gn AND d.simulation = true')
                ->setParameter('gn', $gn)
                ->orderBy('d.createdAt', 'DESC')
                ->addOrderBy('d.id', 'DESC')
                ->getQuery()
                ->getResult(),
        );
    }

    /**
     * Nombre d'enveloppes de joueurs encore en cours de saisie pour une session de groupe (réelles ou simulations).
     */
    public function countJoueursEnCours(GroupeGn $groupeGn, bool $simulation): int
    {
        return (int) $this
            ->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->join('d.participant', 'p')
            ->where('d.gn = :gn AND d.type = :type AND d.simulation = :simulation AND d.statut = :statut AND p.groupeGn = :groupeGn')
            ->setParameter('gn', $groupeGn->getGn())
            ->setParameter('type', DepouillementType::JOUEUR)
            ->setParameter('simulation', $simulation)
            ->setParameter('statut', DepouillementStatut::EN_DEPOUILLEMENT)
            ->setParameter('groupeGn', $groupeGn)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Dépouillements des enveloppes de joueurs d'une session de groupe, réels ou simulations selon la nature du dépouillement du
     * groupe (une simulation ne mélange jamais ses totaux avec des dépouillements réels).
     *
     * @return list<Depouillement>
     */
    public function findJoueursDuGroupe(GroupeGn $groupeGn, bool $simulation): array
    {
        return array_values(
            $this
                ->createQueryBuilder('d')
                ->join('d.participant', 'p')
                ->where('d.gn = :gn AND d.type = :type AND d.simulation = :simulation AND p.groupeGn = :groupeGn')
                ->setParameter('gn', $groupeGn->getGn())
                ->setParameter('type', DepouillementType::JOUEUR)
                ->setParameter('simulation', $simulation)
                ->setParameter('groupeGn', $groupeGn)
                ->orderBy('d.id', 'ASC')
                ->getQuery()
                ->getResult(),
        );
    }

    /**
     * Session d'un groupe pour un GN, à partir du numéro de groupe que connaît le dépouilleur.
     */
    public function findGroupeGnParNumero(Gn $gn, int $numeroGroupe): ?GroupeGn
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('gg')
            ->from(GroupeGn::class, 'gg')
            ->join('gg.groupe', 'g')
            ->where('gg.gn = :gn AND g.numero = :numero')
            ->setParameter('gn', $gn)
            ->setParameter('numero', $numeroGroupe)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Dépouillements réels validés dont l'import vers l'enveloppe du GN suivant est en attente.
     *
     * @return list<Depouillement>
     */
    public function findEnAttenteImport(?Gn $gn = null): array
    {
        $qb = $this
            ->createQueryBuilder('d')
            ->where('d.statut = :statut AND d.simulation = false')
            ->setParameter('statut', DepouillementStatut::EN_ATTENTE_IMPORT)
            ->orderBy('d.id', 'ASC');
        if (null !== $gn) {
            $qb->andWhere('d.gn = :gn')->setParameter('gn', $gn);
        }

        return array_values($qb->getQuery()->getResult());
    }

    /**
     * Participant d'un GN à partir du numéro de personnage (son identifiant, imprimé sur l'enveloppe).
     * Le personnage peut être le principal, la substitution ou la relève du participant.
     */
    public function findParticipantParPersonnage(Gn $gn, int $numeroPersonnage): ?Participant
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('p')
            ->from(Participant::class, 'p')
            ->where('p.gn = :gn')
            ->andWhere('IDENTITY(p.personnage) = :id OR IDENTITY(p.personnageSubstitution) = :id OR IDENTITY(p.personnageReleve) = :id')
            ->setParameter('gn', $gn)
            ->setParameter('id', $numeroPersonnage)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Supprime les simulations (toutes, ou celles d'un GN), leur historique part en cascade. Retourne le nombre supprimé.
     */
    public function purgeSimulations(?Gn $gn = null): int
    {
        $query = $this->createQueryBuilder('d')->delete()->where('d.simulation = true');

        if (null !== $gn) {
            $query->andWhere('d.gn = :gn')->setParameter('gn', $gn);
        }

        return (int) $query->getQuery()->execute();
    }
}
