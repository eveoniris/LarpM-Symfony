<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Exception\EnveloppeIntrouvableException;
use App\Repository\DepouillementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Dépouillement des enveloppes de retour de jeu.
 *
 * Phase de développement : seules les simulations peuvent être créées. Une simulation n'a jamais d'effet sur les données réelles
 * et reste purgeable. Le dépouillement réel arrive avec les étapes suivantes du plan.
 */
readonly class DepouillementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepouillementRepository $depouillementRepository,
        private DepouillementEnveloppeResolver $resolver,
    ) {
    }

    /**
     * Crée une simulation en retrouvant la session de groupe ou le participant à partir du numéro saisi et du GN.
     *
     * @throws EnveloppeIntrouvableException si aucun groupe ou personnage de ce GN ne porte ce numéro
     */
    public function creerSimulation(Gn $gn, DepouillementType $type, string $numero, User $depouilleur): Depouillement
    {
        $numero = $this->resolver->normaliserNumero($numero);
        $cible = $this->resolver->resoudre($gn, $type, $numero);
        $depouillement = (new Depouillement())
            ->setGn($gn)
            ->setType($type)
            ->setNumero($numero)
            ->setGroupeGn($cible instanceof GroupeGn ? $cible : null)
            ->setParticipant($cible instanceof Participant ? $cible : null)
            ->setSimulation(true)
            ->setDepouilleur($depouilleur);

        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($depouilleur)
                ->setActionType(DepouillementHistory::ACTION_CREATE)
                ->setStatutApres($depouillement->getStatut())
                ->setMotif('Simulation'),
        );

        $this->entityManager->persist($depouillement);
        $this->entityManager->flush();

        return $depouillement;
    }

    /**
     * Simulation déjà ouverte pour cette enveloppe, quel que soit son statut (sert à avertir d'une reprise).
     */
    public function trouverSimulation(Gn $gn, DepouillementType $type, string $numero): ?Depouillement
    {
        return $this->depouillementRepository->findByEnveloppe($gn, $type, $this->resolver->normaliserNumero($numero), true);
    }

    /**
     * Le dépouilleur prend la suite d'un dépouillement en cours. L'ancien dépouilleur reste dans l'historique.
     * Sans effet (retourne false) si la saisie n'est plus modifiable.
     */
    public function reprendre(Depouillement $depouillement, User $nouveauDepouilleur): bool
    {
        if (!$depouillement->getStatut()->isEditable()) {
            return false;
        }

        $ancien = $depouillement->getDepouilleur();
        if ($ancien?->getId() === $nouveauDepouilleur->getId()) {
            return true;
        }

        $depouillement->setDepouilleur($nouveauDepouilleur)->touch();
        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($nouveauDepouilleur)
                ->setActionType(DepouillementHistory::ACTION_REPRISE)
                ->setStatutAvant($depouillement->getStatut())
                ->setStatutApres($depouillement->getStatut())
                ->setMotif(null === $ancien ? 'Reprise' : \sprintf('Reprise du dépouillement de %s', $ancien->getUsername())),
        );
        $this->entityManager->flush();

        return true;
    }

    /**
     * Enregistre les remarques du dépouilleur (consigne aussi l'avant/après dans l'historique si elles ont changé).
     */
    public function enregistrerRemarques(Depouillement $depouillement, ?string $remarques, User $utilisateur): bool
    {
        if (!$depouillement->getStatut()->isEditable()) {
            return false;
        }

        $remarques = null === $remarques || '' === trim($remarques) ? null : trim($remarques);
        if ($remarques === $depouillement->getRemarques()) {
            return true;
        }

        $depouillement->addHistory((new DepouillementHistory())
            ->setUser($utilisateur)
            ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
            ->setDataBefore(['remarques' => $depouillement->getRemarques()])
            ->setDataAfter(['remarques' => $remarques]));
        $depouillement->setRemarques($remarques)->touch();
        $this->entityManager->flush();

        return true;
    }

    /**
     * Fin de saisie : passe en "à valider". Retourne false si le dépouillement n'est plus en cours de dépouillement.
     */
    public function terminerSaisie(Depouillement $depouillement, User $utilisateur): bool
    {
        if (!$depouillement->getStatut()->isEditable()) {
            return false;
        }

        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($utilisateur)
                ->setActionType(DepouillementHistory::ACTION_STATUT)
                ->setStatutAvant($depouillement->getStatut())
                ->setStatutApres(DepouillementStatut::A_VALIDER),
        );
        $depouillement->setStatut(DepouillementStatut::A_VALIDER)->touch();
        $this->entityManager->flush();

        return true;
    }

    /**
     * Nombre d'enveloppes de joueurs du groupe encore en cours de dépouillement. L'enveloppe de groupe doit être
     * traitée en dernier : elle récupère le reliquat des enveloppes de joueurs.
     */
    public function nombreEnveloppesJoueursEnCours(Depouillement $enveloppeGroupe): int
    {
        $groupeGn = $enveloppeGroupe->getGroupeGn();

        return $groupeGn instanceof GroupeGn
            ? $this->depouillementRepository->countJoueursEnCours($groupeGn, $enveloppeGroupe->isSimulation())
            : 0;
    }

    /**
     * Supprime les simulations (toutes, ou celles d'un GN). Les dépouillements réels ne sont jamais touchés.
     */
    public function purgerSimulations(?Gn $gn = null): int
    {
        return $this->depouillementRepository->purgeSimulations($gn);
    }

    /**
     * Supprime une simulation. Refuse (retourne false) tout dépouillement réel.
     */
    public function supprimerSimulation(Depouillement $depouillement): bool
    {
        if (!$depouillement->isSimulation()) {
            return false;
        }

        $this->entityManager->remove($depouillement);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @return list<Depouillement>
     */
    public function listerSimulations(Gn $gn): array
    {
        return $this->depouillementRepository->findBy(['gn' => $gn, 'simulation' => true], ['createdAt' => 'DESC']);
    }
}
