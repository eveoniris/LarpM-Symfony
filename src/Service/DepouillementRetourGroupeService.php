<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Entity\GroupeGn;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Exception\ContenuInvalideException;
use App\Repository\DepouillementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Retour de jeu d'un groupe, gardé dans `donnees['retour_groupe']` : titres par numéro de personnage et ordres par phase.
 * Rien n'est appliqué au groupe avant la validation (voir DepouillementTitresService).
 */
readonly class DepouillementRetourGroupeService
{
    public const string CLE = 'retour_groupe';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepouillementRepository $depouillementRepository,
    ) {
    }

    /**
     * Le retour enregistré ; tant qu'il n'y en a pas, les titres actuels du groupe servent de point de départ.
     */
    public function lire(Depouillement $depouillement): RetourGroupe
    {
        $donnees = $depouillement->getDonnees()[self::CLE] ?? null;
        if (\is_array($donnees)) {
            /** @var array<string, mixed> $donnees */
            return RetourGroupe::depuisTableau($donnees);
        }

        $retour = new RetourGroupe();
        $groupeGn = $depouillement->getGroupeGn();
        foreach ($groupeGn instanceof GroupeGn ? $groupeGn->titulaires() : [] as $cle => $personnage) {
            $retour->titres[$cle] = $personnage?->getId();
        }

        return $retour;
    }

    /**
     * @throws ContenuInvalideException si le dossier n'est pas celui d'un groupe, n'est plus modifiable, ou si un numéro de titre n'est
     *                                  pas celui d'un personnage inscrit à ce GN
     */
    public function enregistrer(Depouillement $depouillement, RetourGroupe $retour, User $utilisateur): void
    {
        if (DepouillementType::GROUPE !== $depouillement->getType()) {
            throw new ContenuInvalideException('Ce retour ne concerne que les dossiers de groupe.');
        }

        if (!$depouillement->getStatut()->isEditable()) {
            throw new ContenuInvalideException(Depouillement::MESSAGE_NON_MODIFIABLE);
        }

        foreach (RetourGroupe::TITRES as $cle => $libelle) {
            $numero = $retour->titres[$cle] ?? null;
            if (null !== $numero && null === $this->depouillementRepository->findParticipantParPersonnage($depouillement->getGn(), $numero)) {
                throw new ContenuInvalideException(\sprintf("%s : le personnage n° %d n'est pas inscrit à ce GN.", $libelle, $numero));
            }
        }

        $avant = $depouillement->getDonnees()[self::CLE] ?? null;
        $apres = $retour->versTableau();
        if ($avant === $apres) {
            return;
        }

        $depouillement->setDonnees([
            ...$depouillement->getDonnees(),
            self::CLE => $apres,
            DepouillementOrdresService::CLE => DepouillementOrdresService::sansPhasesModifiees($depouillement, \is_array($avant) ? $avant['ordres'] ?? [] : [], $apres['ordres']),
        ]);
        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($utilisateur)
                ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                ->setDataBefore(null === $avant ? null : [self::CLE => $avant])
                ->setDataAfter([self::CLE => $apres])
                ->setMotif('Retour du groupe enregistré'),
        );
        $depouillement->touch();
        $this->entityManager->flush();
    }
}
