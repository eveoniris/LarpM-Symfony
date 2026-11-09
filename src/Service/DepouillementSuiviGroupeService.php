<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Exception\ContenuInvalideException;
use App\Repository\DepouillementRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Suivi des retours de jeu des membres d'un groupe : combien sont reçus sur combien attendus, et clôture des cas où le retour ne
 * viendra pas (« pas de retour », « n'a pas participé », avec motif), pour pouvoir terminer un groupe incomplet.
 *
 * Attendu : un participant du groupe qui a un personnage. Reçu : son dossier réel a quitté la saisie (envoyé ou traité). Les clôtures
 * sont gardées dans `donnees['clotures']` du dossier du groupe, par identifiant de participant : le compteur les compte comme traitées.
 */
// @mago-expect lint:cyclomatic-complexity
readonly class DepouillementSuiviGroupeService
{
    public const string CLE = 'clotures';

    /** @var array<string, string> */
    public const array STATUTS = [
        'pas_de_retour' => 'Pas de retour',
        'absent' => "N'a pas participé",
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepouillementRepository $depouillementRepository,
    ) {
    }

    /**
     * @return array{lignes: list<array{participant: Participant, dossier: Depouillement|null, recu: bool, cloture: array{statut: string, motif: string}|null}>, attendus: int, recus: int, clotures: int}
     */
    public function suivi(Depouillement $groupe): array
    {
        $groupeGn = $groupe->getGroupeGn();
        $clotures = self::clotures($groupe);
        $lignes = [];
        $recus = 0;
        $comptees = 0;
        foreach ($groupeGn?->getParticipants() ?? [] as $participant) {
            $personnage = $participant->getPersonnage();
            if (null === $personnage) {
                continue;
            }

            $dossier = $this->depouillementRepository->findByEnveloppe($groupe->getGn(), DepouillementType::JOUEUR, (string) $personnage->getId(), false);
            $recu = null !== $dossier && DepouillementStatut::EN_DEPOUILLEMENT !== $dossier->getStatut();
            $cloture = $clotures[(int) $participant->getId()] ?? null;
            $recus += $recu ? 1 : 0;
            $comptees += !$recu && null !== $cloture ? 1 : 0;
            $lignes[] = ['participant' => $participant, 'dossier' => $dossier, 'recu' => $recu, 'cloture' => $cloture];
        }

        return ['lignes' => $lignes, 'attendus' => \count($lignes), 'recus' => $recus, 'clotures' => $comptees];
    }

    /**
     * @throws ContenuInvalideException si le dossier n'est pas un groupe modifiable, si le participant n'est pas de ce groupe, si le
     *                                  statut est inconnu ou si le motif manque
     */
    public function cloturer(Depouillement $groupe, Participant $participant, string $statut, string $motif, User $utilisateur): void
    {
        $this->verifier($groupe, $participant);
        $motif = trim($motif);
        if (!isset(self::STATUTS[$statut])) {
            throw new ContenuInvalideException('Statut de clôture inconnu.');
        }

        if ('' === $motif || mb_strlen($motif) > 500) {
            throw new ContenuInvalideException('Le motif est obligatoire (500 caractères au plus).');
        }

        $clotures = self::clotures($groupe);
        $clotures[(int) $participant->getId()] = ['statut' => $statut, 'motif' => $motif];
        $this->enregistrer($groupe, $clotures, $utilisateur, \sprintf('%s : %s (%s)', self::STATUTS[$statut], $participant->getUser()?->getUsername() ?? '?', $motif));
    }

    /**
     * @throws ContenuInvalideException si le dossier n'est pas un groupe modifiable ou si le participant n'est pas de ce groupe
     */
    public function rouvrir(Depouillement $groupe, Participant $participant, User $utilisateur): void
    {
        $this->verifier($groupe, $participant);
        $clotures = self::clotures($groupe);
        if (!isset($clotures[(int) $participant->getId()])) {
            return;
        }

        unset($clotures[(int) $participant->getId()]);
        $this->enregistrer($groupe, $clotures, $utilisateur, 'Clôture retirée pour ' . ($participant->getUser()?->getUsername() ?? '?'));
    }

    /**
     * @return array<int, array{statut: string, motif: string}>
     */
    public static function clotures(Depouillement $groupe): array
    {
        $donnees = $groupe->getDonnees()[self::CLE] ?? [];
        $clotures = [];
        foreach (\is_array($donnees) ? $donnees : [] as $id => $entree) {
            if (\is_array($entree) && isset(self::STATUTS[(string) ($entree['statut'] ?? '')])) {
                $clotures[(int) $id] = ['statut' => (string) $entree['statut'], 'motif' => (string) ($entree['motif'] ?? '')];
            }
        }

        return $clotures;
    }

    private function verifier(Depouillement $groupe, Participant $participant): void
    {
        if (DepouillementType::GROUPE !== $groupe->getType()) {
            throw new ContenuInvalideException('Le suivi ne concerne que les dossiers de groupe.');
        }

        if (!$groupe->getStatut()->isEditable()) {
            throw new ContenuInvalideException(Depouillement::MESSAGE_NON_MODIFIABLE);
        }

        if ($participant->getGroupeGn()?->getId() !== $groupe->getGroupeGn()?->getId()) {
            throw new ContenuInvalideException("Ce participant n'est pas dans ce groupe.");
        }
    }

    /**
     * @param array<int, array{statut: string, motif: string}> $clotures
     */
    private function enregistrer(Depouillement $groupe, array $clotures, User $utilisateur, string $motif): void
    {
        $avant = $groupe->getDonnees()[self::CLE] ?? null;
        $groupe->setDonnees([...$groupe->getDonnees(), self::CLE => $clotures]);
        $groupe->addHistory(
            (new DepouillementHistory())
                ->setUser($utilisateur)
                ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                ->setDataBefore(null === $avant ? null : [self::CLE => $avant])
                ->setDataAfter([self::CLE => $clotures, 'le' => (new DateTimeImmutable())->format('c')])
                ->setMotif($motif),
        );
        $groupe->touch();
        $this->entityManager->flush();
    }
}
