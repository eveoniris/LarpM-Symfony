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
 * Une simulation n'a jamais d'effet sur les données réelles et reste purgeable. Un dossier réel est unique par GN, type et numéro :
 * le joueur peut l'ouvrir (sa déclaration), puis le staff le reprend. Rien n'est appliqué avant la validation.
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:too-many-methods
readonly class DepouillementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepouillementRepository $depouillementRepository,
        private DepouillementEnveloppeResolver $resolver,
        private DepouillementPhotoService $photoService,
    ) {
    }

    /**
     * Crée une simulation en retrouvant la session de groupe ou le participant à partir du numéro saisi et du GN.
     *
     * @throws EnveloppeIntrouvableException si aucun groupe ou personnage de ce GN ne porte ce numéro
     */
    public function creerSimulation(Gn $gn, DepouillementType $type, string $numero, User $depouilleur): Depouillement
    {
        return $this->creer($gn, $type, $numero, $depouilleur, true);
    }

    /**
     * Ouvre le dossier RÉEL d'une enveloppe (rien n'est appliqué aux personnages avant la validation), ou retourne celui qui existe
     * déjà : il n'y a qu'un dossier réel par GN, type et numéro.
     *
     * @throws EnveloppeIntrouvableException si aucun groupe ou personnage de ce GN ne porte ce numéro
     */
    public function ouvrirReel(Gn $gn, DepouillementType $type, string $numero, User $saisisseur): Depouillement
    {
        $existant = $this->trouverReel($gn, $type, $numero);

        return $existant ?? $this->creer($gn, $type, $numero, $saisisseur, false);
    }

    /**
     * Le joueur ouvre le dossier réel de son personnage pour ce GN. Le numéro n'est jamais choisi par le joueur : c'est celui du
     * personnage de sa participation.
     *
     * @throws EnveloppeIntrouvableException si le joueur n'est pas inscrit à ce GN avec un personnage
     */
    public function ouvrirPourJoueur(Gn $gn, User $joueur): Depouillement
    {
        $personnage = $joueur->getParticipant($gn)?->getPersonnage();
        if (null === $personnage) {
            throw new EnveloppeIntrouvableException("Vous n'avez pas de personnage inscrit à ce GN.");
        }

        return $this->ouvrirReel($gn, DepouillementType::JOUEUR, (string) $personnage->getId(), $joueur);
    }

    /**
     * Le groupe dont le joueur peut remplir le retour pour ce GN : celui dont il est le chef ou dont son personnage porte un titre.
     */
    public function groupeDuJoueur(Gn $gn, User $joueur): ?GroupeGn
    {
        $participant = $joueur->getParticipant($gn);
        if (null === $participant) {
            return null;
        }

        $candidats = [$participant->getGroupeGn(), ...$participant->getGroupeGns()->toArray()];
        foreach ($candidats as $groupeGn) {
            if ($groupeGn instanceof GroupeGn && $groupeGn->getGn()?->getId() === $gn->getId() && $groupeGn->peutSaisirRetour($joueur)) {
                return $groupeGn;
            }
        }

        return null;
    }

    /**
     * Le joueur ouvre le dossier réel de son groupe pour ce GN (le numéro est celui du groupe, jamais choisi par le joueur).
     *
     * @throws EnveloppeIntrouvableException si le joueur n'est ni chef de groupe ni titulaire d'un titre de groupe à ce GN
     */
    public function ouvrirPourGroupe(Gn $gn, User $joueur): Depouillement
    {
        $groupeGn = $this->groupeDuJoueur($gn, $joueur);
        if (null === $groupeGn) {
            throw new EnveloppeIntrouvableException("Vous n'êtes ni chef de groupe ni titulaire d'un titre de groupe à ce GN.");
        }

        return $this->ouvrirReel($gn, DepouillementType::GROUPE, (string) $groupeGn->getGroupe()->getNumero(), $joueur);
    }

    /**
     * Dossier réel du personnage du joueur pour ce GN, s'il existe (null aussi quand le joueur n'a pas de personnage inscrit).
     */
    public function trouverPourJoueur(Gn $gn, User $joueur): ?Depouillement
    {
        $personnage = $joueur->getParticipant($gn)?->getPersonnage();

        return null === $personnage ? null : $this->trouverReel($gn, DepouillementType::JOUEUR, (string) $personnage->getId());
    }

    /**
     * Dossier réel déjà ouvert pour cette enveloppe, quel que soit son statut.
     */
    public function trouverReel(Gn $gn, DepouillementType $type, string $numero): ?Depouillement
    {
        return $this->depouillementRepository->findByEnveloppe($gn, $type, $this->resolver->normaliserNumero($numero), false);
    }

    private function creer(Gn $gn, DepouillementType $type, string $numero, User $saisisseur, bool $simulation): Depouillement
    {
        $numero = $this->resolver->normaliserNumero($numero);
        $cible = $this->resolver->resoudre($gn, $type, $numero);
        $depouillement = (new Depouillement())
            ->setGn($gn)
            ->setType($type)
            ->setNumero($numero)
            ->setGroupeGn($cible instanceof GroupeGn ? $cible : null)
            ->setParticipant($cible instanceof Participant ? $cible : null)
            ->setSimulation($simulation)
            ->setDepouilleur($saisisseur);

        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($saisisseur)
                ->setActionType(DepouillementHistory::ACTION_CREATE)
                ->setStatutApres($depouillement->getStatut())
                ->setMotif($simulation ? 'Simulation' : 'Dossier réel'),
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
        // Les lignes partent en cascade, pas les fichiers des photos : on les supprime d'abord.
        foreach ($this->depouillementRepository->findBy($gn instanceof Gn ? ['simulation' => true, 'gn' => $gn] : ['simulation' => true]) as $simulation) {
            $this->photoService->supprimerFichiers($simulation);
        }

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

        $this->photoService->supprimerFichiers($depouillement);
        $this->entityManager->remove($depouillement);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @return list<Depouillement>
     */
    public function listerSimulations(Gn $gn): array
    {
        return $this->depouillementRepository->findSimulationsAvecContexte($gn);
    }
}
