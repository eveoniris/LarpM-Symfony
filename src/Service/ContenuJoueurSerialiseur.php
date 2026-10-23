<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Ingredient;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Ressource;
use App\Entity\Sort;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Passage entre le contenu saisi pour une enveloppe de joueur (entités choisies par auto-complétion) et le brouillon JSON gardé
 * sur le dépouillement (identifiants seulement).
 */
// @mago-expect lint:cyclomatic-complexity
readonly class ContenuJoueurSerialiseur
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, string> $statuts statut de chaque action, calculé par les règles
     *
     * @return array<string, mixed>
     */
    public function versTableau(ContenuJoueur $contenu, array $statuts): array
    {
        return [
            'pieces' => [
                'fiche_personnage' => $contenu->fichePersonnage,
                'fiche_retour' => $contenu->ficheRetour,
                'fiche_sort' => $contenu->ficheSort,
                'corruption_ouvertes' => max(0, $contenu->corruptionOuvertes),
                'corruption_non_ouvertes' => max(0, $contenu->corruptionNonOuvertes),
                'fiche_priere' => $contenu->fichePriere,
                'carte_alchimiste' => $contenu->carteAlchimiste,
                'pinces_a_linge' => max(0, $contenu->pincesALinge),
                'documents_numerotes' => $contenu->documentsNumerotes,
                'copies_recettes_sorts' => $contenu->copiesRecettesSorts,
            ],
            'sorts' => $this->ids($contenu->sorts),
            'prieres' => $this->ids($contenu->prieres),
            'potions' => $this->ids($contenu->potions),
            'introuvables' => $contenu->introuvables,
            'actions' => [
                'fruits_legumes' => ['utilise' => $contenu->fruitsUtilise, 'jointes' => $contenu->fruitsJointes],
                'soie' => ['utilise' => $contenu->soieUtilise, 'jointes' => $contenu->soieJointes],
                'gemme' => ['utilise' => $contenu->gemmeUtilise, 'jointes' => $contenu->gemmeJointes, 'pieces_or' => max(0, $contenu->gemmePieces)],
                'sel' => ['utilise' => $contenu->selUtilise, 'jointes' => $contenu->selJointes, 'ids' => $this->ids($contenu->selIngredients)],
                'fourrure' => ['utilise' => $contenu->fourrureUtilise, 'jointes' => $contenu->fourrureJointes, 'ids' => $this->ids($contenu->fourrureRessources)],
                'ivoire' => ['utilise' => $contenu->ivoireUtilise, 'jointes' => $contenu->ivoireJointes, 'ids' => $this->ids($contenu->ivoirePotions)],
            ],
            'surplus' => CompteursEnveloppe::normaliser($contenu->surplus),
            'statuts' => $statuts,
        ];
    }

    /**
     * @param array<string, mixed> $donnees
     */
    // @mago-expect lint:halstead
    public function depuisTableau(array $donnees): ContenuJoueur
    {
        $contenu = new ContenuJoueur();
        /** @var array<string, mixed> $pieces */
        $pieces = $donnees['pieces'] ?? [];
        /** @var array<string, array<string, mixed>> $actions */
        $actions = $donnees['actions'] ?? [];

        $contenu->fichePersonnage = (bool) ($pieces['fiche_personnage'] ?? false);
        $contenu->ficheRetour = (bool) ($pieces['fiche_retour'] ?? false);
        $contenu->ficheSort = (bool) ($pieces['fiche_sort'] ?? false);
        $contenu->corruptionOuvertes = (int) ($pieces['corruption_ouvertes'] ?? 0);
        $contenu->corruptionNonOuvertes = (int) ($pieces['corruption_non_ouvertes'] ?? 0);
        $contenu->fichePriere = (bool) ($pieces['fiche_priere'] ?? false);
        $contenu->carteAlchimiste = (bool) ($pieces['carte_alchimiste'] ?? false);
        $contenu->pincesALinge = (int) ($pieces['pinces_a_linge'] ?? 0);
        $contenu->documentsNumerotes = $pieces['documents_numerotes'] ?? null;
        $contenu->copiesRecettesSorts = (bool) ($pieces['copies_recettes_sorts'] ?? false);
        $contenu->sorts = $this->entites(Sort::class, $donnees['sorts'] ?? []);
        $contenu->prieres = $this->entites(Priere::class, $donnees['prieres'] ?? []);
        $contenu->potions = $this->entites(Potion::class, $donnees['potions'] ?? []);
        $contenu->introuvables = $donnees['introuvables'] ?? null;
        $contenu->surplus = CompteursEnveloppe::normaliser($donnees['surplus'] ?? []);

        $contenu->fruitsUtilise = (bool) ($actions['fruits_legumes']['utilise'] ?? false);
        $contenu->fruitsJointes = (bool) ($actions['fruits_legumes']['jointes'] ?? false);
        $contenu->soieUtilise = (bool) ($actions['soie']['utilise'] ?? false);
        $contenu->soieJointes = (bool) ($actions['soie']['jointes'] ?? false);
        $contenu->gemmeUtilise = (bool) ($actions['gemme']['utilise'] ?? false);
        $contenu->gemmeJointes = (bool) ($actions['gemme']['jointes'] ?? false);
        $contenu->gemmePieces = (int) ($actions['gemme']['pieces_or'] ?? 0);
        $contenu->selUtilise = (bool) ($actions['sel']['utilise'] ?? false);
        $contenu->selJointes = (bool) ($actions['sel']['jointes'] ?? false);
        $contenu->selIngredients = $this->entites(Ingredient::class, $actions['sel']['ids'] ?? []);
        $contenu->fourrureUtilise = (bool) ($actions['fourrure']['utilise'] ?? false);
        $contenu->fourrureJointes = (bool) ($actions['fourrure']['jointes'] ?? false);
        $contenu->fourrureRessources = $this->entites(Ressource::class, $actions['fourrure']['ids'] ?? []);
        $contenu->ivoireUtilise = (bool) ($actions['ivoire']['utilise'] ?? false);
        $contenu->ivoireJointes = (bool) ($actions['ivoire']['jointes'] ?? false);
        $contenu->ivoirePotions = $this->entites(Potion::class, $actions['ivoire']['ids'] ?? []);

        return $contenu;
    }

    /**
     * @param iterable<object> $entites
     *
     * @return list<int>
     */
    private function ids(iterable $entites): array
    {
        $ids = [];
        foreach ($entites as $entite) {
            $ids[] = (int) $entite->getId();
        }

        return $ids;
    }

    /**
     * @param class-string<T> $classe
     * @param list<int>|mixed $ids
     *
     * @return list<T>
     *
     * @template T of object
     */
    private function entites(string $classe, mixed $ids): array
    {
        if (!\is_array($ids) || [] === $ids) {
            return [];
        }

        return array_values($this->entityManager->getRepository($classe)->findBy(['id' => $ids]));
    }
}
