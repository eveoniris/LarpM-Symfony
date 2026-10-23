<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementEtiquette;
use App\Entity\DepouillementHistory;
use App\Entity\EnveloppeLigne;
use App\Entity\LogAction;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\Potion;
use App\Entity\User;
use App\Enum\ActionFinOpus;
use App\Enum\ActionStatut;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Enum\EnveloppeLigneNature;
use App\Enum\EtiquetteActionObjet;
use App\Enum\EtiquetteDestination;
use App\Exception\ValidationInvalideException;
use App\Repository\DepouillementRepository;
use App\Repository\GnRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Reporte pour le GN suivant ce qui doit figurer dans l'enveloppe de l'opus suivant : étiquettes à réinsérer et objets à remettre au PC
 * joueur (lignes de l'enveloppe du personnage), potions conservées avec l'ivoire (potions de départ du participant).
 *
 * Le GN suivant est le premier GN actif qui commence après celui du dépouillement, et le personnage doit y être inscrit. Sinon
 * le dépouillement passe en « en attente d'import » et l'import est retenté (bouton ou commande). Une simulation n'importe jamais rien.
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
readonly class DepouillementImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GnRepository $gnRepository,
        private DepouillementRepository $depouillementRepository,
        private DepouillementContenuService $contenuService,
        private DepouillementEnveloppeResolver $resolver,
    ) {
    }

    /**
     * Lignes à reporter dans l'enveloppe du personnage, déduites des étiquettes (non rattachées : ni personnage, ni GN, ni persistées).
     *
     * @return list<EnveloppeLigne>
     */
    public function lignes(Depouillement $depouillement): array
    {
        if (DepouillementType::JOUEUR !== $depouillement->getType()) {
            return [];
        }

        $lignes = [];
        foreach ($depouillement->getEtiquettes() as $etiquette) {
            if (EtiquetteDestination::ENVELOPPE === $etiquette->getDestination() || EtiquetteDestination::AVEC_OBJET_AU_PC === $etiquette->getDestination()) {
                $lignes[] = $this->ligne($etiquette, EnveloppeLigneNature::ETIQUETTE_A_REINSERER, 'Étiquette');
            }

            // Un objet à remettre au PC joueur : objet de l'association ou objet trouvé. Un objet déjà remis au PC n'a rien à reporter.
            if (\in_array($etiquette->getActionObjet(), [EtiquetteActionObjet::REMETTRE_AU_PC, EtiquetteActionObjet::OBJET_TROUVE], strict: true)) {
                $lignes[] = $this->ligne($etiquette, EnveloppeLigneNature::OBJET_AU_PC, EtiquetteActionObjet::OBJET_TROUVE === $etiquette->getActionObjet() ? 'Objet trouvé' : 'Objet');
            }
        }

        return $lignes;
    }

    /**
     * Potions conservées avec l'ivoire, quand l'action est acceptée.
     *
     * @return list<Potion>
     */
    public function potionsIvoire(Depouillement $depouillement): array
    {
        if (DepouillementType::JOUEUR !== $depouillement->getType()) {
            return [];
        }

        $contenu = $this->contenuService->lireContenuJoueur($depouillement);
        $resultat = $this->contenuService->evaluerActions($contenu)[ActionFinOpus::IVOIRE->value] ?? null;

        return null !== $resultat && ActionStatut::ACCEPTEE === $resultat->statut ? $contenu->ivoirePotions : [];
    }

    public function aImporter(Depouillement $depouillement): bool
    {
        return !$depouillement->isSimulation() && ([] !== $this->lignes($depouillement) || [] !== $this->potionsIvoire($depouillement));
    }

    /**
     * Importe pour le GN suivant s'il existe et si le personnage y est inscrit, sinon met le dépouillement en attente d'import.
     * Sans rien à importer (ou pour une simulation), ne change rien.
     *
     * @throws ValidationInvalideException si le dépouillement n'est ni validé ni en attente d'import
     */
    public function importer(Depouillement $depouillement, ?User $utilisateur): ResultatApprentissages
    {
        $resultat = new ResultatApprentissages('Enveloppe suivante', 'Rien à reporter dans l\'enveloppe suivante.');
        if (!\in_array($depouillement->getStatut(), [DepouillementStatut::VALIDE, DepouillementStatut::EN_ATTENTE_IMPORT], strict: true)) {
            throw new ValidationInvalideException(\sprintf(
                'Ce dépouillement est « %s » : seul un dépouillement validé ou en attente d\'import peut être importé.',
                $depouillement->getStatut()->label(),
            ));
        }

        if (!$this->aImporter($depouillement)) {
            return $resultat;
        }

        $personnage = $this->personnage($depouillement);
        $gnSuivant = $this->gnRepository->findSuivant($depouillement->getGn());
        $participant = null === $personnage || null === $gnSuivant ? null : $this->depouillementRepository->findParticipantParPersonnage($gnSuivant, (int) $personnage->getId());

        if (null === $personnage || null === $gnSuivant || null === $participant) {
            $resultat->anomalies[] = match (true) {
                null === $personnage => 'Personnage de l\'enveloppe introuvable.',
                null === $gnSuivant => 'Aucun GN actif ne suit celui-ci : l\'import est en attente.',
                default => \sprintf('Le personnage n\'est pas encore inscrit au GN « %s » : l\'import est en attente.', $gnSuivant->getLabel()),
            };
            $this->changerStatut($depouillement, DepouillementStatut::EN_ATTENTE_IMPORT, $utilisateur, $resultat->resume());

            return $resultat;
        }

        $resultat->applique = true;
        $this->ecrire($depouillement, $personnage, $participant, $resultat);
        $this->changerStatut($depouillement, DepouillementStatut::TERMINE, $utilisateur, $resultat->resume());
        $this->journaliser($depouillement, $utilisateur, $resultat);

        return $resultat;
    }

    private function ecrire(Depouillement $depouillement, Personnage $personnage, Participant $participant, ResultatApprentissages $resultat): void
    {
        foreach ($this->lignes($depouillement) as $ligne) {
            $ligne->setPersonnage($personnage)->setGn($participant->getGn())->setDepouillement($depouillement);
            $this->entityManager->persist($ligne);
            $resultat->ajoutes[] = \sprintf('%s x%d', $ligne->getLibelle(), $ligne->getQuantite());
        }

        foreach ($this->potionsIvoire($depouillement) as $potion) {
            $libelle = 'potion de départ ' . $potion->getLabel();
            if ($participant->getPotionsDepart()->exists(static fn (int $cle, Potion $connue): bool => $connue->getId() === $potion->getId())) {
                $resultat->ignores[] = $libelle;

                continue;
            }

            $participant->addPotionDepart($potion);
            $resultat->ajoutes[] = $libelle;
        }
    }

    private function ligne(DepouillementEtiquette $etiquette, EnveloppeLigneNature $nature, string $debut): EnveloppeLigne
    {
        $libelle = \sprintf(
            '%s %s%s%s',
            $debut,
            $etiquette->getCouleur()->value,
            null === $etiquette->getNumero() ? '' : \sprintf(' n°%d', $etiquette->getNumero()),
            null === $etiquette->getQualite() ? '' : \sprintf(' qualité %s', $etiquette->getQualite()),
        );

        return (new EnveloppeLigne())
            ->setNature($nature)
            ->setLibelle($libelle)
            ->setQuantite($etiquette->getQuantite())
            ->setNumeroObjet($etiquette->getNumero());
    }

    private function personnage(Depouillement $depouillement): ?Personnage
    {
        $participant = $depouillement->getParticipant();

        return null === $participant ? null : $this->resolver->personnageDuNumero($participant, (int) $depouillement->getNumero());
    }

    private function changerStatut(Depouillement $depouillement, DepouillementStatut $statut, ?User $utilisateur, string $motif): void
    {
        $depouillement
            ->addHistory(
                (new DepouillementHistory())
                    ->setUser($utilisateur)
                    ->setActionType(DepouillementHistory::ACTION_STATUT)
                    ->setStatutAvant($depouillement->getStatut())
                    ->setStatutApres($statut)
                    ->setMotif($motif),
            )
            ->setStatut($statut);
        $depouillement->touch();
        $this->entityManager->flush();
    }

    private function journaliser(Depouillement $depouillement, ?User $utilisateur, ResultatApprentissages $resultat): void
    {
        $this->entityManager->persist((new LogAction())
            ->setType(\App\Enum\LogActionType::DEPOUILLEMENT)
            ->setUser($utilisateur)
            ->setDate(new DateTime())
            ->setData(['action' => 'import', 'depouillement_id' => $depouillement->getId(), 'enveloppe_suivante' => $resultat->versTableau()]));
        $this->entityManager->flush();
    }
}
