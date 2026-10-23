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
 * Contenu constaté dans l'enveloppe d'un groupe, et reliquat du groupe (ce qu'il a collecté plus le surplus trouvé dans les
 * enveloppes de ses joueurs). Le reliquat sert au calcul de l'initiative, dont la formule n'est pas encore figée : on ne fait ici
 * que des additions de quantités.
 *
 * Tant que le dépouillement n'est pas validé, tout est gardé dans `donnees['contenu_groupe']` (brouillon de suivi).
 */
readonly class DepouillementGroupeService
{
    private const string CLE = 'contenu_groupe';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepouillementRepository $depouillementRepository,
    ) {
    }

    /**
     * @throws ContenuInvalideException si le dépouillement n'est pas une enveloppe de groupe ou n'est plus modifiable
     */
    public function enregistrerContenuGroupe(Depouillement $depouillement, ContenuGroupe $contenu, User $utilisateur): void
    {
        if (DepouillementType::GROUPE !== $depouillement->getType()) {
            throw new ContenuInvalideException('Ce contenu ne concerne que les enveloppes de groupe.');
        }

        if (!$depouillement->getStatut()->isEditable()) {
            throw new ContenuInvalideException('Ce dépouillement n\'est plus modifiable.');
        }

        $avant = $depouillement->getDonnees()[self::CLE] ?? null;
        $apres = $contenu->versTableau();

        $depouillement->setDonnees([...$depouillement->getDonnees(), self::CLE => $apres]);
        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($utilisateur)
                ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                ->setDataBefore(null === $avant ? null : [self::CLE => $avant])
                ->setDataAfter([self::CLE => $apres])
                ->setMotif('Contenu de l\'enveloppe de groupe enregistré'),
        );
        $depouillement->touch();
        $this->entityManager->flush();
    }

    public function lireContenuGroupe(Depouillement $depouillement): ContenuGroupe
    {
        /** @var array<string, mixed> $donnees */
        $donnees = $depouillement->getDonnees()[self::CLE] ?? [];

        return ContenuGroupe::depuisTableau($donnees);
    }

    /**
     * Reliquat du groupe, case par case : ce que le groupe a collecté, le surplus déclaré dans les enveloppes de ses joueurs
     * (celles qui sont déjà dépouillées) et le total. Seules les cases non nulles sont retournées.
     *
     * @return array{lignes: array<string, array{groupe: int, joueurs: int, total: int}>, enveloppesJoueurs: int}
     */
    public function reliquat(Depouillement $enveloppeGroupe): array
    {
        $groupeGn = $enveloppeGroupe->getGroupeGn();
        $joueurs = $groupeGn instanceof GroupeGn ? $this->depouillementRepository->findJoueursDuGroupe($groupeGn, $enveloppeGroupe->isSimulation()) : [];

        $collecte = $this->lireContenuGroupe($enveloppeGroupe)->compteurs;
        $surplusJoueurs = array_fill_keys(array_keys(CompteursEnveloppe::CHAMPS), value: 0);
        foreach ($joueurs as $joueur) {
            foreach (CompteursEnveloppe::normaliser($joueur->getDonnees()['contenu_joueur']['surplus'] ?? []) as $cle => $quantite) {
                $surplusJoueurs[$cle] += $quantite;
            }
        }

        $lignes = [];
        foreach (CompteursEnveloppe::CHAMPS as $cle => $libelle) {
            $total = ($collecte[$cle] ?? 0) + $surplusJoueurs[$cle];
            if ($total <= 0) {
                continue;
            }

            $lignes[$libelle] = ['groupe' => $collecte[$cle] ?? 0, 'joueurs' => $surplusJoueurs[$cle], 'total' => $total];
        }

        return ['lignes' => $lignes, 'enveloppesJoueurs' => \count($joueurs)];
    }
}
