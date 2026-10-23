<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Entity\LogAction;
use App\Entity\User;
use App\Enum\DepouillementStatut;
use App\Enum\LogActionType;
use App\Exception\ValidationInvalideException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Validation d'un dépouillement par un scénariste, un wargame ou un admin, ou renvoi au dépouilleur pour correction.
 *
 * Un dépouillement validé ne peut PAS être rouvert. Cette étape ne fait aucun dispatch : l'application aux joueurs et aux
 * groupes est l'étape suivante du plan. Les simulations se valident comme les autres mais n'écrivent pas dans log_action.
 */
readonly class DepouillementValidationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws ValidationInvalideException si le dépouillement n'est pas en attente de validation
     */
    public function valider(Depouillement $depouillement, User $validateur): void
    {
        $this->verifierAValider($depouillement);

        $historique = (new DepouillementHistory())
            ->setUser($validateur)
            ->setActionType(DepouillementHistory::ACTION_VALIDATION)
            ->setStatutAvant($depouillement->getStatut())
            ->setStatutApres(DepouillementStatut::VALIDE)
            ->setMotif($depouillement->isSimulation() ? 'Validation d\'une simulation (aucun effet)' : null);

        $depouillement
            ->setStatut(DepouillementStatut::VALIDE)
            ->setValidatedAt(new DateTime())
            ->setValidatedBy($validateur)
            ->addHistory($historique);
        $depouillement->touch();

        $this->entityManager->flush();
        $this->journaliser($depouillement, $historique, $validateur, 'validation');
    }

    /**
     * Renvoie le dépouillement au dépouilleur pour correction. Le motif est obligatoire : il est gardé dans l'historique.
     *
     * @throws ValidationInvalideException si le dépouillement n'est pas en attente de validation ou si le motif est vide
     */
    public function renvoyer(Depouillement $depouillement, User $validateur, string $motif): void
    {
        $this->verifierAValider($depouillement);

        $motif = trim($motif);
        if ('' === $motif) {
            throw new ValidationInvalideException('Le motif du renvoi est obligatoire : le dépouilleur doit savoir quoi corriger.');
        }

        $historique = (new DepouillementHistory())
            ->setUser($validateur)
            ->setActionType(DepouillementHistory::ACTION_STATUT)
            ->setStatutAvant($depouillement->getStatut())
            ->setStatutApres(DepouillementStatut::EN_DEPOUILLEMENT)
            ->setMotif('Renvoyé au dépouilleur : ' . $motif);

        $depouillement
            ->setStatut(DepouillementStatut::EN_DEPOUILLEMENT)
            ->setValidatedAt(null)
            ->setValidatedBy(null)
            ->addHistory($historique);
        $depouillement->touch();

        $this->entityManager->flush();
        $this->journaliser($depouillement, $historique, $validateur, 'renvoi');
    }

    private function verifierAValider(Depouillement $depouillement): void
    {
        if (DepouillementStatut::A_VALIDER !== $depouillement->getStatut()) {
            throw new ValidationInvalideException(\sprintf('Ce dépouillement est « %s » : seul un dépouillement « à valider » peut être validé ou renvoyé.', $depouillement->getStatut()->label()));
        }
    }

    /**
     * Événement sommaire dans log_action, qui renvoie à l'entrée d'historique du dépouillement. Les simulations n'y figurent pas.
     */
    private function journaliser(Depouillement $depouillement, DepouillementHistory $historique, User $utilisateur, string $action): void
    {
        if ($depouillement->isSimulation()) {
            return;
        }

        $log = (new LogAction())
            ->setType(LogActionType::DEPOUILLEMENT)
            ->setUser($utilisateur)
            ->setDate(new DateTime())
            ->setData([
                'action' => $action,
                'depouillement_id' => $depouillement->getId(),
                'depouillement_history_id' => $historique->getId(),
            ]);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
