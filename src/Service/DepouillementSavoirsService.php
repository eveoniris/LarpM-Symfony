<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Connaissance;
use App\Entity\Depouillement;
use App\Entity\Langue;
use App\Entity\Personnage;
use App\Entity\Technologie;
use App\Enum\DepouillementType;
use App\Enum\LangueSourceType;
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
readonly class DepouillementSavoirsService
{
    public function __construct(
        private DepouillementContenuService $contenuService,
        private DepouillementEnveloppeResolver $resolver,
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
        if ([] === $contenu->langues && [] === $contenu->connaissances && [] === $contenu->technologies) {
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
            $this->langue($personnage, $langue, $resultat);
        }

        foreach ($contenu->connaissances as $connaissance) {
            $this->connaissance($personnage, $connaissance, $resultat);
        }

        foreach ($contenu->technologies as $technologie) {
            $this->technologie($personnage, $technologie, $contenu->enseignantTechnologieNumero, $resultat);
        }

        return $resultat;
    }

    private function langue(Personnage $personnage, Langue $langue, ResultatApprentissages $resultat): void
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

        $ligne = $personnage->addLangueSource($langue, LangueSourceType::JEU);
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
