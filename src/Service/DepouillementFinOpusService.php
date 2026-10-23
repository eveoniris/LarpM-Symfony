<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\Ingredient;
use App\Entity\Personnage;
use App\Entity\PersonnageChronologie;
use App\Entity\PersonnageIngredient;
use App\Entity\PersonnageRessource;
use App\Entity\RenommeHistory;
use App\Entity\Ressource;
use App\Enum\ActionFinOpus;
use App\Enum\ActionStatut;
use App\Enum\ChronologyType;
use App\Enum\DepouillementType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Applique à la validation les actions de fin d'Opus acceptées : fruits et légumes (chronologie), soie (renommée +1), gemme
 * (richesse), sel (ingrédients) et fourrure (ressources). L'ivoire (potions de départ) dépend du GN suivant : voir
 * DepouillementImportService.
 *
 * Une action refusée ou non utilisée ne fait rien. Une simulation calcule le même résultat sans rien écrire. Le flush est laissé à
 * l'appelant (même transaction que la validation).
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
readonly class DepouillementFinOpusService
{
    /** 1 pièce d'or vaut 10 pièces d'argent (la richesse de la fiche est en pièces d'argent). */
    public const int PIECES_ARGENT_PAR_PIECE_OR = 10;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepouillementContenuService $contenuService,
        private DepouillementEnveloppeResolver $resolver,
    ) {
    }

    // @mago-expect lint:halstead
    public function appliquer(Depouillement $depouillement): ResultatApprentissages
    {
        $resultat = new ResultatApprentissages('Actions de fin d\'Opus', 'Aucune action de fin d\'Opus à appliquer.');
        if (DepouillementType::JOUEUR !== $depouillement->getType()) {
            return $resultat;
        }

        $contenu = $this->contenuService->lireContenuJoueur($depouillement);
        $acceptees = array_filter($this->contenuService->evaluerActions($contenu), static fn (ActionFinOpusResultat $action): bool => ActionStatut::ACCEPTEE === $action->statut);
        if ([] === $acceptees && !$contenu->personnageMort) {
            return $resultat;
        }

        $participant = $depouillement->getParticipant();
        $personnage = null === $participant ? null : $this->resolver->personnageDuNumero($participant, (int) $depouillement->getNumero());
        if (null === $personnage) {
            $resultat->anomalies[] = 'Personnage de l\'enveloppe introuvable : rien n\'a été appliqué (actions de fin d\'Opus, décès).';

            return $resultat;
        }

        $resultat->applique = !$depouillement->isSimulation();
        if ($contenu->personnageMort) {
            $this->deces($depouillement, $personnage, $contenu->circonstancesMort, $resultat);
        }

        foreach (array_keys($acceptees) as $action) {
            match ($action) {
                ActionFinOpus::FRUITS_LEGUMES->value => $this->fruits($depouillement, $personnage, $resultat),
                ActionFinOpus::SOIE->value => $this->soie($depouillement, $personnage, $resultat),
                ActionFinOpus::GEMME->value => $this->gemme($personnage, min($contenu->gemmePieces, (int) ActionFinOpus::GEMME->plafond()), $resultat),
                ActionFinOpus::SEL->value => $this->ingredients($personnage, array_slice($contenu->selIngredients, offset: 0, length: (int) ActionFinOpus::SEL->plafond()), $resultat),
                ActionFinOpus::FOURRURE->value => $this->ressources($personnage, array_slice($contenu->fourrureRessources, offset: 0, length: (int) ActionFinOpus::FOURRURE->plafond()), $resultat),
                default => null,
            };
        }

        return $resultat;
    }

    /**
     * Le personnage est mort pendant cet opus : il est marqué mort sur sa fiche et l'événement entre dans sa chronologie. Un personnage
     * déjà mort est ignoré. Rien n'est reporté dans l'enveloppe du GN suivant pour un mort (voir DepouillementImportService).
     */
    private function deces(Depouillement $depouillement, Personnage $personnage, ?string $circonstances, ResultatApprentissages $resultat): void
    {
        if (!$personnage->getVivant()) {
            $resultat->ignores[] = 'décès (le personnage est déjà mort)';

            return;
        }

        $circonstances = null === $circonstances ? '' : trim($circonstances);
        $resultat->ajoutes[] = 'décès du personnage' . ('' === $circonstances ? '' : ' (' . $circonstances . ')');
        if (!$resultat->applique) {
            return;
        }

        $personnage->setVivant(false);
        $annee = $depouillement->getGn()->getDateJeu();
        if (null !== $annee) {
            $this->entityManager->persist(
                (new PersonnageChronologie())
                    ->setAnnee($annee)
                    ->setEvenement('' === $circonstances ? 'Mort' : 'Mort : ' . $circonstances)
                    ->setPersonnage($personnage),
            );
        }
    }

    private function fruits(Depouillement $depouillement, Personnage $personnage, ResultatApprentissages $resultat): void
    {
        $annee = $depouillement->getGn()->getDateJeu();
        if (null === $annee) {
            $resultat->anomalies[] = 'Le GN n\'a pas d\'année de jeu : la consommation de fruits et légumes n\'est pas inscrite à la chronologie.';

            return;
        }

        $resultat->ajoutes[] = 'consommation de fruits et légumes (chronologie)';
        if ($resultat->applique) {
            $this->entityManager->persist(
                (new PersonnageChronologie())
                    ->setAnnee($annee)
                    ->setEvenement(ChronologyType::FRUITS_AND_VEGETABLES->value)
                    ->setPersonnage($personnage),
            );
        }
    }

    private function soie(Depouillement $depouillement, Personnage $personnage, ResultatApprentissages $resultat): void
    {
        $explication = 'LH' . $depouillement->getGn()->getId() . ' - Soie';
        $existe = null !== $this->entityManager->getRepository(RenommeHistory::class)->findOneBy(['personnage' => $personnage, 'explication' => $explication]);
        if ($existe) {
            $resultat->ignores[] = 'renommée +1 (soie, déjà donnée pour ce GN)';

            return;
        }

        $resultat->ajoutes[] = 'renommée +1 (soie)';
        if ($resultat->applique) {
            $this->entityManager->persist(
                (new RenommeHistory())
                    ->setRenomme(1)
                    ->setExplication($explication)
                    ->setPersonnage($personnage),
            );
        }
    }

    private function gemme(Personnage $personnage, int $piecesOr, ResultatApprentissages $resultat): void
    {
        if ($piecesOr < 1) {
            return;
        }

        $argent = $piecesOr * self::PIECES_ARGENT_PAR_PIECE_OR;
        $resultat->ajoutes[] = \sprintf('richesse +%d pièces d\'argent (%d pièces d\'or de gemme)', $argent, $piecesOr);
        if ($resultat->applique) {
            $personnage->setRichesse(($personnage->getRichesse() ?? 0) + $argent);
        }
    }

    /**
     * @param list<Ingredient> $ingredients
     */
    private function ingredients(Personnage $personnage, array $ingredients, ResultatApprentissages $resultat): void
    {
        foreach ($ingredients as $ingredient) {
            $resultat->ajoutes[] = 'ingrédient ' . $ingredient->getLabel();
            if (!$resultat->applique) {
                continue;
            }

            $ligne = $this->ligneIngredient($personnage, $ingredient);
            if (null === $ligne) {
                $this->entityManager->persist(
                    (new PersonnageIngredient())
                        ->setIngredient($ingredient)
                        ->setNombre(1)
                        ->setPersonnage($personnage),
                );

                continue;
            }

            $ligne->setNombre($ligne->getNombre() + 1);
        }
    }

    /**
     * @param list<Ressource> $ressources
     */
    private function ressources(Personnage $personnage, array $ressources, ResultatApprentissages $resultat): void
    {
        foreach ($ressources as $ressource) {
            $resultat->ajoutes[] = 'ressource ' . $ressource->getLabel();
            if (!$resultat->applique) {
                continue;
            }

            $ligne = $this->ligneRessource($personnage, $ressource);
            if (null === $ligne) {
                $this->entityManager->persist(
                    (new PersonnageRessource())
                        ->setRessource($ressource)
                        ->setNombre(1)
                        ->setPersonnage($personnage),
                );

                continue;
            }

            $ligne->setNombre(($ligne->getNombre() ?? 0) + 1);
        }
    }

    private function ligneIngredient(Personnage $personnage, Ingredient $ingredient): ?PersonnageIngredient
    {
        foreach ($personnage->getPersonnageIngredients() as $ligne) {
            if ($ligne->getIngredient()?->getId() === $ingredient->getId()) {
                return $ligne;
            }
        }

        return null;
    }

    private function ligneRessource(Personnage $personnage, Ressource $ressource): ?PersonnageRessource
    {
        foreach ($personnage->getPersonnageRessources() as $ligne) {
            if ($ligne->getRessource()?->getId() === $ressource->getId()) {
                return $ligne;
            }
        }

        return null;
    }
}
