<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Competence;
use App\Entity\Depouillement;
use App\Entity\Personnage;
use App\Entity\PersonnageApprentissage;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Sort;
use App\Enum\CompetenceFamilyType;
use App\Enum\DepouillementType;
use App\Repository\PersonnageApprentissageRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Applique sur la fiche du joueur les sorts, prières et potions appris en jeu et saisis dans son enveloppe, à la validation.
 *
 * Ajout direct : appris en jeu, donc hors achat, aucun trigger n'est consommé. Un élément déjà connu est ignoré. Un niveau ou une
 * religion incohérents sont signalés sans rien bloquer. Une simulation calcule le même résultat mais n'écrit rien sur la fiche.
 * Le flush est laissé à l'appelant (même transaction que la validation).
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
readonly class DepouillementApprentissageService
{
    public function __construct(
        private DepouillementContenuService $contenuService,
        private DepouillementEnveloppeResolver $resolver,
        private EntityManagerInterface $entityManager,
        private ControleApprentissage $controle,
        private PersonnageApprentissageRepository $apprentissageRepository,
    ) {
    }

    public function appliquer(Depouillement $depouillement): ResultatApprentissages
    {
        $resultat = new ResultatApprentissages();
        if (DepouillementType::JOUEUR !== $depouillement->getType()) {
            return $resultat;
        }

        $contenu = $this->contenuService->lireContenuJoueur($depouillement);
        if ([] === $contenu->sorts && [] === $contenu->prieres && [] === $contenu->potions && null === $contenu->competenceApprise) {
            return $resultat;
        }

        $personnage = $this->personnage($depouillement);
        if (null === $personnage) {
            $resultat->anomalies[] = 'Personnage de l\'enveloppe introuvable : rien n\'a été appliqué.';

            return $resultat;
        }

        $resultat->applique = !$depouillement->isSimulation();

        foreach ($contenu->sorts as $sort) {
            $this->sort($personnage, $sort, $resultat);
        }

        foreach ($contenu->prieres as $priere) {
            $this->priere($personnage, $priere, $resultat);
        }

        foreach ($contenu->potions as $potion) {
            $this->potion($personnage, $potion, $resultat);
        }

        if (null !== $contenu->competenceApprise) {
            $this->competence($depouillement, $personnage, $contenu->competenceApprise, $contenu->enseignantNumero, $resultat);
        }

        return $resultat;
    }

    /**
     * Apprentissage d'une compétence auprès d'un enseignant (même règles que la saisie d'un orga : enseignant inscrit à ce GN,
     * niveau expert au moins, compétence accessible, un apprentissage au plus entre deux GN). Un apprentissage qui ne respecte
     * pas ces règles n'est pas enregistré et est signalé. Il ne donne pas la compétence : il réduit son coût à l'achat.
     */
    private function competence(Depouillement $depouillement, Personnage $personnage, Competence $competence, ?int $numeroEnseignant, ResultatApprentissages $resultat): void
    {
        $libelle = 'compétence ' . $competence->getLabel();
        $enseignant = $this->controle->enseignant($depouillement, $numeroEnseignant);
        $refus = $this->controle->refus($personnage, $competence, $enseignant, $numeroEnseignant);
        if (null !== $refus || null === $enseignant) {
            $resultat->anomalies[] = \sprintf('Apprentissage de la %s non enregistré : %s.', $libelle, $refus);

            return;
        }

        $annee = $depouillement->getGn()->getDateJeu();
        if (null !== $annee && $this->apprentissageRepository->hasApprentissage($personnage, $annee, $annee + 1)) {
            $resultat->ignores[] = $libelle . ' (déjà un apprentissage enregistré pour ce GN)';

            return;
        }

        $resultat->ajoutes[] = \sprintf('%s (enseignant : %s)', $libelle, $enseignant->getNom());
        if ($resultat->applique) {
            $this->entityManager->persist(
                (new PersonnageApprentissage())
                    ->setPersonnage($personnage)
                    ->setEnseignant($enseignant)
                    ->setCompetence($competence)
                    ->setCreatedAt(new DateTime())
                    ->setDateEnseignement($annee),
            );
        }
    }

    private function personnage(Depouillement $depouillement): ?Personnage
    {
        $participant = $depouillement->getParticipant();

        return null === $participant ? null : $this->resolver->personnageDuNumero($participant, (int) $depouillement->getNumero());
    }

    private function sort(Personnage $personnage, Sort $sort, ResultatApprentissages $resultat): void
    {
        $libelle = \sprintf('sort %s (niveau %d)', $sort->getLabel(), $sort->getNiveau());
        if ($this->contientId($personnage->getSorts()->toArray(), $sort->getId())) {
            $resultat->ignores[] = $libelle;

            return;
        }

        if ($sort->getNiveau() > $this->niveauMax($personnage, CompetenceFamilyType::MAGIC)) {
            $resultat->anomalies[] = \sprintf('%s est de niveau %d, au-dessus de la compétence Magie de %s.', ucfirst($libelle), $sort->getNiveau(), $personnage->getNom());
        }

        $resultat->ajoutes[] = $libelle;
        if ($resultat->applique) {
            $personnage->addSort($sort);
        }
    }

    private function priere(Personnage $personnage, Priere $priere, ResultatApprentissages $resultat): void
    {
        $libelle = \sprintf('prière %s (niveau %d)', $priere->getLabel(), $priere->getNiveau());
        if ($this->contientId($personnage->getPrieres()->toArray(), $priere->getId())) {
            $resultat->ignores[] = $libelle;

            return;
        }

        $anomalieReligion = $this->anomalieReligion($personnage, $priere, $libelle);
        if (null !== $anomalieReligion) {
            $resultat->anomalies[] = $anomalieReligion;
        }

        if ($priere->getNiveau() > $this->niveauMax($personnage, CompetenceFamilyType::PRIESTHOOD)) {
            $resultat->anomalies[] = \sprintf('%s est de niveau %d, au-dessus de la prêtrise de %s.', ucfirst($libelle), $priere->getNiveau(), $personnage->getNom());
        }

        $resultat->ajoutes[] = $libelle;
        if ($resultat->applique) {
            // Ajoute des deux côtés : Priere::addPersonnage appelle Personnage::addPriere.
            $priere->addPersonnage($personnage);
        }
    }

    /**
     * Une prière appartient à une sphère : le personnage doit avoir une religion qui la porte.
     */
    private function anomalieReligion(Personnage $personnage, Priere $priere, string $libelle): ?string
    {
        $religion = $personnage->getMainReligion();
        if (null === $religion) {
            return \sprintf('%s : %s n\'a pas de religion.', ucfirst($libelle), $personnage->getNom());
        }

        $sphere = $priere->getSphere();
        if (null === $sphere || $this->contientId($religion->getSpheres()->toArray(), $sphere->getId())) {
            return null;
        }

        return \sprintf('%s : la sphère %s n\'est pas celle de la religion %s.', ucfirst($libelle), $sphere->getLabel(), $religion->getLabel());
    }

    private function potion(Personnage $personnage, Potion $potion, ResultatApprentissages $resultat): void
    {
        $libelle = \sprintf('potion %s (niveau %d)', $potion->getLabel(), $potion->getNiveau());
        if ($personnage->isKnownPotion($potion)) {
            $resultat->ignores[] = $libelle;

            return;
        }

        if ($potion->getNiveau() > $this->niveauMax($personnage, CompetenceFamilyType::ALCHEMY)) {
            $resultat->anomalies[] = \sprintf('%s est de niveau %d, au-dessus de l\'alchimie de %s.', ucfirst($libelle), $potion->getNiveau(), $personnage->getNom());
        }

        $resultat->ajoutes[] = $libelle;
        if ($resultat->applique) {
            $personnage->addPotion($potion);
        }
    }

    /**
     * Niveau le plus haut des compétences de la famille donnée (0 si le personnage n'en a aucune).
     */
    private function niveauMax(Personnage $personnage, CompetenceFamilyType $famille): int
    {
        $max = 0;
        foreach ($personnage->getCompetences() as $competence) {
            if ($famille->value !== $competence->getCompetenceFamily()?->getCompetenceFamilyType()?->value) {
                continue;
            }

            $max = max($max, $competence->getLevel()?->getIndex() ?? 0);
        }

        return $max;
    }

    /**
     * @param array<array-key, object> $elements
     */
    private function contientId(array $elements, ?int $id): bool
    {
        foreach ($elements as $element) {
            if (null !== $id && method_exists($element, 'getId') && $element->getId() === $id) {
                return true;
            }
        }

        return false;
    }
}
