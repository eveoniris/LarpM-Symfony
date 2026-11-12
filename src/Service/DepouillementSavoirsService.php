<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Connaissance;
use App\Entity\Depouillement;
use App\Entity\Langue;
use App\Entity\Personnage;
use App\Entity\RenommeHistory;
use App\Entity\Technologie;
use App\Enum\DepouillementType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Applique sur la fiche du joueur les langues, connaissances et technologies apprises en jeu et déclarées dans son retour, à la
 * validation. Seules les lignes retenues sont dans le contenu : une ligne refusée par le staff en a été retirée (voir
 * DepouillementDecisionService).
 *
 * Ajout direct : appris en jeu, donc hors achat, aucun trigger n'est consommé. Un savoir déjà connu est ignoré (« déjà appliqué »).
 * L'enseignant des technologies est consigné sans contrôle. Une simulation calcule le même résultat sans rien écrire sur la fiche.
 * Le flush est laissé à l'appelant (même transaction que la validation).
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
readonly class DepouillementSavoirsService
{
    public function __construct(
        private DepouillementContenuService $contenuService,
        private DepouillementEnveloppeResolver $resolver,
        private DepouillementDeclarationService $declaration,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function appliquer(Depouillement $depouillement): ResultatApprentissages
    {
        $resultat = new ResultatApprentissages('Langues, connaissances et technologies', 'Aucune langue, connaissance ni technologie à appliquer.');
        if (DepouillementType::JOUEUR !== $depouillement->getType()) {
            return $resultat;
        }

        $contenu = $this->contenuService->lireContenuJoueur($depouillement);
        $points = $this->declaration->lire($depouillement)->renommeePoints;
        if ([] === $contenu->langues && [] === $contenu->connaissances && [] === $contenu->technologies && null === $points) {
            return $resultat;
        }

        $participant = $depouillement->getParticipant();
        $personnage = null === $participant ? null : $this->resolver->personnageDuNumero($participant, (int) $depouillement->getNumero());
        if (null === $personnage) {
            $resultat->anomalies[] = 'Personnage de l\'enveloppe introuvable : aucune langue, connaissance ni technologie appliquée.';

            return $resultat;
        }

        $resultat->applique = !$depouillement->isSimulation();
        foreach ($contenu->langues as $langue) {
            $this->langue($personnage, $langue, $contenu->sourceDeLangue($langue), $resultat);
        }

        foreach ($contenu->connaissances as $connaissance) {
            $this->connaissance($personnage, $connaissance, $resultat);
        }

        foreach ($contenu->technologies as $technologie) {
            $this->technologie($personnage, $technologie, $contenu->enseignantTechnologieNumero, $resultat);
        }

        if (null !== $points) {
            $this->renommee($depouillement, $personnage, $points, $resultat);
        }

        return $resultat;
    }

    /**
     * Renommée déclarée par le joueur : appliquée seulement une fois confirmée par un responsable, une seule fois par GN
     * (l'explication sert de clé, comme pour la soie).
     */
    private function renommee(Depouillement $depouillement, Personnage $personnage, int $points, ResultatApprentissages $resultat): void
    {
        $libelle = \sprintf('renommée %+d (déclarée)', $points);
        $decision = DecisionsDeclaration::lire(DecisionsDeclaration::depuis($depouillement->getDonnees()), 'renommee_points');
        if (DecisionsDeclaration::ETAT_CONFIRME !== $decision['etat']) {
            $resultat->ignores[] = $libelle . ' : non confirmée par un organisateur';

            return;
        }

        $explication = 'LH' . $depouillement->getGn()->getId() . ' - Renommée déclarée';
        if (null !== $this->entityManager->getRepository(RenommeHistory::class)->findOneBy(['personnage' => $personnage, 'explication' => $explication])) {
            $resultat->ignores[] = $libelle . ' (déjà donnée pour ce GN)';

            return;
        }

        $resultat->ajoutes[] = $libelle;
        if ($resultat->applique) {
            $personnage->addRenomme($points);
            $this->entityManager->persist(
                (new RenommeHistory())
                    ->setRenomme($points)
                    ->setExplication($explication)
                    ->setPersonnage($personnage),
            );
        }
    }

    private function langue(Personnage $personnage, Langue $langue, string $source, ResultatApprentissages $resultat): void
    {
        $libelle = 'langue ' . $langue->getLabel();
        if ($personnage->isKnownLanguage($langue)) {
            $resultat->ignores[] = $libelle;

            return;
        }

        $resultat->ajoutes[] = $libelle;
        if (!$resultat->applique) {
            return;
        }

        $ligne = $personnage->addLangueSource($langue, $source);
        if (null !== $ligne) {
            $this->entityManager->persist($ligne);
        }
    }

    private function connaissance(Personnage $personnage, Connaissance $connaissance, ResultatApprentissages $resultat): void
    {
        $libelle = 'connaissance ' . $connaissance->getLabel();
        if ($personnage->isKnownConnaissance($connaissance)) {
            $resultat->ignores[] = $libelle;

            return;
        }

        $resultat->ajoutes[] = $libelle;
        if ($resultat->applique) {
            $personnage->addConnaissance($connaissance);
        }
    }

    private function technologie(Personnage $personnage, Technologie $technologie, ?int $numeroEnseignant, ResultatApprentissages $resultat): void
    {
        $libelle = 'technologie ' . $technologie->getLabel();
        if ($personnage->isKnownTechnologie($technologie)) {
            $resultat->ignores[] = $libelle;

            return;
        }

        $resultat->ajoutes[] = null === $numeroEnseignant ? $libelle : \sprintf('%s (enseignant n°%d)', $libelle, $numeroEnseignant);
        if ($resultat->applique) {
            $personnage->addTechnologie($technologie);
        }
    }
}
