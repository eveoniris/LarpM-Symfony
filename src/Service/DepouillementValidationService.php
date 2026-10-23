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
 * Un dépouillement validé ne peut PAS être rouvert. La validation applique les sorts, prières et potions appris sur la fiche du
 * joueur (DepouillementApprentissageService), dans la même transaction. Les simulations se valident comme les autres mais
 * n'écrivent ni sur les fiches ni dans log_action.
 */
readonly class DepouillementValidationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepouillementApprentissageService $apprentissages,
        private DepouillementFinOpusService $finOpus,
        private DepouillementImportService $import,
    ) {
    }

    /**
     * Valide, applique apprentissages et actions de fin d'Opus, puis reporte vers l'enveloppe suivante (ou met en attente d'import).
     *
     * @throws ValidationInvalideException si le dépouillement n'est pas en attente de validation
     */
    public function valider(Depouillement $depouillement, User $validateur): ResultatValidation
    {
        $this->verifierAValider($depouillement);
        $apprentissages = $this->apprentissages->appliquer($depouillement);
        $finOpus = $this->finOpus->appliquer($depouillement);

        $historique = (new DepouillementHistory())
            ->setUser($validateur)
            ->setActionType(DepouillementHistory::ACTION_VALIDATION)
            ->setStatutAvant($depouillement->getStatut())
            ->setStatutApres(DepouillementStatut::VALIDE)
            ->setMotif($this->motif($depouillement, new ResultatValidation($apprentissages, $finOpus, new ResultatApprentissages())));

        $depouillement
            ->setStatut(DepouillementStatut::VALIDE)
            ->setValidatedAt(new DateTime())
            ->setValidatedBy($validateur)
            ->addHistory($historique);
        $depouillement->touch();

        $this->entityManager->flush();
        // Le report vers l'enveloppe suivante change le statut (terminé ou en attente d'import) et ne concerne jamais une simulation.
        $enveloppeSuivante = $this->import->importer($depouillement, $validateur);
        $resultat = new ResultatValidation($apprentissages, $finOpus, $enveloppeSuivante);
        $this->journaliser($depouillement, $historique, $validateur, 'validation', $resultat);

        return $resultat;
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

    private function motif(Depouillement $depouillement, ResultatValidation $resultat): ?string
    {
        $parties = array_filter([
            $depouillement->isSimulation() ? 'Validation d\'une simulation (aucun effet)' : null,
            $resultat->estVide() ? null : $resultat->resume(),
        ]);

        return [] === $parties ? null : implode('. ', $parties);
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
    private function journaliser(Depouillement $depouillement, DepouillementHistory $historique, User $utilisateur, string $action, ?ResultatValidation $resultat = null): void
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
                ...(null === $resultat || $resultat->apprentissages->estVide() ? [] : ['apprentissages' => $resultat->apprentissages->versTableau()]),
                ...(null === $resultat || $resultat->finOpus->estVide() ? [] : ['fin_opus' => $resultat->finOpus->versTableau()]),
            ]);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
