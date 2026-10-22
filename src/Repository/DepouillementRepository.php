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
     * Dépouillement réel (hors simulation) déjà ouvert pour une enveloppe : sert à avertir d'une reprise ou d'un doublon.
     */
    public function findReelByEnveloppe(Gn $gn, DepouillementType $type, string $numero): ?Depouillement
    {
        return $this
            ->getEntityManager()
            ->getRepository(Depouillement::class)
            ->findOneBy([
                'gn' => $gn,
                'type' => $type,
                'numero' => $numero,
                'simulation' => false,
            ]);
    }

    /**
     * Dépouillement déjà ouvert pour cette enveloppe (réel ou simulation), quel que soit son statut.
     */
    public function findByEnveloppe(Gn $gn, DepouillementType $type, string $numero, bool $simulation): ?Depouillement
    {
        return $this
            ->getEntityManager()
            ->getRepository(Depouillement::class)
            ->findOneBy([
                'gn' => $gn,
                'type' => $type,
                'numero' => $numero,
                'simulation' => $simulation,
            ]);
    }

    /**
     * Nombre d'enveloppes de joueurs encore en cours de saisie pour une session de groupe (réelles ou simulations).
     */
    public function countJoueursEnCours(GroupeGn $groupeGn, bool $simulation): int
    {
        return (int) $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(d.id)')
            ->from(Depouillement::class, 'd')
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
        $query = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->delete(Depouillement::class, 'd')
            ->where('d.simulation = true');

        if (null !== $gn) {
            $query->andWhere('d.gn = :gn')->setParameter('gn', $gn);
        }

        return (int) $query->getQuery()->execute();
    }
}
