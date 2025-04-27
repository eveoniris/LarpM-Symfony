<?php

namespace App\Service;

use App\Entity\Bonus;
use App\Entity\Groupe;
use App\Entity\GroupeBonus;
use App\Entity\GroupeGn;
use App\Entity\GroupeHasIngredient;
use App\Entity\GroupeHasRessource;
use App\Entity\Ingredient;
use App\Entity\Item;
use App\Entity\Loi;
use App\Entity\Merveille;
use App\Entity\Personnage;
use App\Entity\PersonnageIngredient;
use App\Entity\Rarete;
use App\Entity\Ressource;
use App\Entity\Territoire;
use App\Entity\User;
use App\Enum\BonusPeriode;
use App\Enum\BonusType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class GroupeService
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ValidatorInterface $validator,
        protected FormFactoryInterface $formFactory,
        protected UrlGeneratorInterface $urlGenerator,
        protected ConditionsService $conditionsService,
        protected Security $security,
    ) {
    }

    /**
     * @return Collection<int, GroupeHasIngredient>
     */
    public function getAllIngredient(Groupe $groupe): Collection
    {
        $all = $groupe->getIngredients();

        foreach ($this->getAllBonus($groupe, BonusType::INGREDIENT) as $bonus) {
            if (!$bonus->isIngredient()) {
                continue;
            }

            $data = $bonus->getDataAsList('ingredients');

            foreach ($data as $ingredientData) {
                if (!isset($ingredientData['id']) || !is_numeric($ingredientData['id'])) {
                    continue;
                }

                if (!$this->conditionsService->isValidConditions(
                    $groupe,
                    $bonus->getConditions($ingredientData['condition']),
                    $bonus,
                )) {
                    // on passe à l'item suivant
                    continue;
                }

                // Attribution
                $ingredient = $this->entityManager
                    ->getRepository(Ingredient::class)
                    ->findOneBy(['id' => $ingredientData['id']]);

                if (!$ingredient) {
                    // Generate one
                    $ingredient = new Ingredient();
                    $ingredient->setLabel($data['label'] ?? $bonus->getTitre() ?: 'BONUS');
                    $ingredient->setNiveau((int)($data['niveau'] ?? 1));
                    $ingredient->setDose($data['dose'] ?? 'unité');
                }

                $this->addIngredientToAll($groupe, $ingredient, $all);
            }
        }

        return $all;
    }

    /**
     * @return Collection<int, Bonus>
     */
    public function getAllBonus(
        Groupe $groupe,
        ?BonusType $type = null,
        bool $withDisabled = false,
        ?BonusPeriode $periode = null,
    ): Collection {
        $all = new ArrayCollection();

        $this->getGroupeBonus($groupe, $type, $withDisabled, $periode, $all);

        $this->getMerveilleBonus($groupe, $type, $withDisabled, $periode, $all);

        return $all;
    }

    public function getGroupeBonus(
        Groupe $groupe,
        ?BonusType $type = null,
        bool $withDisabled = false,
        ?BonusPeriode $periode = null,
        ?Collection &$all = null,
    ): ArrayCollection {
        $all ??= new ArrayCollection();

        /** @var GroupeBonus $groupeBonus */
        foreach ($groupe->getGroupeBonus() as $groupeBonus) {
            // On évite les types non désirés
            if (!$withDisabled && !$groupeBonus->isValid()) {
                continue;
            }

            $bonus = $groupeBonus->getBonus();
            if (!$bonus || !$bonus->isTypeAndPeriode($type, $periode)) {
                continue;
            }

            if (!$all->containsKey($bonus->getId())) {
                $all->offsetSet($bonus->getId(), $bonus);
            }
        }

        return $all;
    }

    public function getMerveilleBonus(
        Groupe $groupe,
        ?BonusType $type = null,
        bool $withDisabled = false,
        ?BonusPeriode $periode = null,
        ?Collection &$all = null,
    ): ArrayCollection {
        $all ??= new ArrayCollection();
        // On ne prend que celui du dernier groupe actif
        /** @var Territoire $territoire */
        foreach ($groupe->getTerritoires() as $territoire) {
            if (null === $territoire->getMerveilles()) {
                continue;
            }

            /** @var Merveille $merveille */
            foreach ($territoire->getMerveilles() as $merveille) {
                if (!$merveille->isActive()) {
                    continue;
                }
                $bonus = $merveille->getBonus();

                if (null === $bonus) {
                    continue;
                }

                // On évite les types non désirés
                if (!$withDisabled && !$bonus->isValid()) {
                    continue;
                }

                if ($type && $bonus->getType() !== $type) {
                    continue;
                }

                if ($periode && $bonus->getPeriode() !== $periode) {
                    continue;
                }

                if (!$all->containsKey($bonus->getId())) {
                    $all->offsetSet($bonus->getId(), $bonus);
                }
            }
        }

        return $all;
    }

    private function addIngredientToAll(Groupe $groupe, Ingredient $ingredient, Collection $all): void
    {
        /** @var PersonnageIngredient $existing */
        foreach ($all as $existing) {
            if ((int)$existing->getIngredient()?->getId() === (int)$ingredient->getId()
                && $existing->getIngredient()?->getLabel() === $ingredient->getLabel()) {
                return;
            }
        }

        $groupeIngredient = new GroupeHasIngredient();
        $groupeIngredient->setGroupe($groupe);
        $groupeIngredient->setIngredient($ingredient);
        $all->add($groupeIngredient);
    }

    /**
     * @return Collection<int, Item>
     */
    public function getAllItems(Groupe $groupe): Collection
    {
        $all = $groupe->getItems();

        foreach ($this->getAllBonus($groupe, BonusType::ITEM) as $bonus) {
            if (!$bonus->isItem()) {
                continue;
            }

            $data = $bonus->getDataAsList('items');

            foreach ($data as $itemData) {
                if (!isset($itemData['id']) || !is_numeric($itemData['id'])) {
                    continue;
                }

                if (!$this->conditionsService->isValidConditions($groupe, $itemData['condition'] ?? [], $bonus)) {
                    // on passe à l'item suivant
                    continue;
                }

                // Attribution
                $item = $this->entityManager
                    ->getRepository(Item::class)
                    ->findOneBy(['id' => $itemData['id']]);

                if (!$item) {
                    // Generate one
                    $item = new Item();
                    $item->setLabel($itemData['label'] ?? $bonus->getTitre());
                    $item->setNumero($itemData['numero'] ?? (int)$bonus->getValeur());
                    $item->setIdentification($itemData['identification'] ?? 0);
                    $item->setCouleur($itemData['couleur'] ?? 'aucune');
                    $item->setDescription($itemData['description'] ?? $bonus->getDescription());
                    $item->setSpecial($itemData['special'] ?? null);
                    $item->setQuality(isset($itemData['quality']) ? (int)$itemData['quality'] : null);
                }

                $this->addItemToAll($groupe, $item, $all);
            }
        }

        return $all;
    }

    private function addItemToAll(Groupe $groupe, Item $item, Collection $items): void
    {
        /** @var Item $row */
        foreach ($items as $row) {
            if (
                $row?->getId() === $item->getId()
                && $row?->getLabel() === $item->getLabel()
            ) {
                return;
            }
        }

        $items->add($item);
    }

    public function getAllMateriel(Groupe $groupe): array
    {
        $all = $groupe->getMateriel() ? [$groupe->getMateriel()] : [];

        foreach ($this->getAllBonus($groupe, BonusType::MATERIEL) as $bonus) {
            if (!$bonus->isMateriel()) {
                continue;
            }

            if (!$this->conditionsService->isValidConditions(
                $groupe,
                $bonus->getJsonData()['condition'] ?? [],
                $bonus,
            )) {
                // on passe à l'item suivant
                continue;
            }

            $all[] = $bonus->getTitre().' - '.$bonus->getDescription();
        }

        return $all;
    }

    /**
     * @return Collection<int, GroupeHasRessource>
     */
    public function getAllRessource(Groupe $groupe): Collection
    {
        $all = $groupe->getGroupeHasRessources();

        foreach ($this->getAllBonus($groupe, BonusType::RESSOURCE) as $bonus) {
            if (!$bonus->isRessource()) {
                continue;
            }

            // this condition is tested before JSON value if we use bonus value only
            if (!$this->conditionsService->isValidConditions(
                $groupe,
                $bonus->getJsonData()['condition'] ?? [],
                $bonus,
            )) {
                // on passe au bonus suivant
                continue;
            }

            $data = $bonus->getDataAsList('ressources');

            // If we prefer Json instead of Bonus value
            foreach ($data as $row) {
                if (!isset($row['id']) || !is_numeric($row['id'])) {
                    continue;
                }

                if (!$this->conditionsService->isValidConditions($groupe, $row['condition'] ?? [], $bonus)) {
                    // on passe à l'item suivant
                    continue;
                }

                // Attribution
                $ressource = $this->entityManager
                    ->getRepository(Ressource::class)
                    ->findOneBy(['id' => $row['id']]);

                if (!$ressource) {
                    // Generate one
                    $ressource = new Ressource();
                    $ressource->setLabel($data['titre'] ?? $bonus->getTitre());
                    $rarete = new Rarete();
                    $rarete->setLabel($data['rarete'] ?? 'Commun');
                    $ressource->setRarete($rarete);
                }

                $ressourceGroupe = new GroupeHasRessource();
                $ressourceGroupe->setQuantite(((int)$data['nombre']) ?: 1);
                $ressourceGroupe->setRessource($ressource);

                $this->addRessourceToAll($ressourceGroupe, $all);
                continue 2; // next bonus
            }

            // If we use bonus values instead (condition tested before)
            $ressourceGroupe = new GroupeHasRessource();
            $ressourceGroupe->setQuantite((int)$bonus->getValeur());
            $ressource = new Ressource();
            $ressource->setLabel($bonus->getTitre().' - '.$bonus->getDescription());
            $rarete = new Rarete();
            $rarete->setLabel($data['rarete'] ?? 'Commun');
            $ressource->setRarete($rarete);
            $ressourceGroupe->setRessource($ressource);

            $this->addRessourceToAll($ressourceGroupe, $all);
        }

        return $all;
    }

    private function addRessourceToAll(GroupeHasRessource $groupeHasRessource, Collection $all): void
    {
        /** @var GroupeHasRessource $existing */
        foreach ($all as $existing) {
            if (
                ($existing->getRessource()->getId() === $groupeHasRessource->getRessource()->getId())
                && ($existing->getRessource()->getLabel() === $groupeHasRessource->getRessource()->getLabel())
            ) {
                return;
            }
        }

        $all->add($groupeHasRessource);
    }

    public function getAllRichesse(Groupe $groupe): int
    {
        $all = $groupe->getRichesse();

        foreach ($this->getAllBonus($groupe, BonusType::RICHESSE) as $bonus) {
            if (!$bonus->isRichesse()) {
                continue;
            }

            if (!$this->conditionsService->isValidConditions(
                $groupe,
                $bonus->getJsonData()['condition'] ?? [],
                $bonus,
            )) {
                // on passe à l'item suivant
                continue;
            }

            $all += (int)$bonus->getValeur();
        }

        return $all ?? 0;
    }

    /**
     * @return array|Collection<int, Loi>
     */
    public function getLois(Groupe $groupe): array|Collection
    {
        // Lois du pays du personnage initié
        $lois = new ArrayCollection();
        /** @var Territoire $territoire */
        foreach ($groupe?->getTerritoires() ?? new ArrayCollection() as $territoire) {
            /** @var Territoire $fief */
            foreach ($territoire->getAncestors() as $fief) {
                foreach ($fief->getLois() as $loi) {
                    $lois->add($loi);
                }
            }
        }

        return $lois;
    }

    public function getPersonnages(GroupeGn $groupeGn): Collection
    {
        return $groupeGn->getPersonnages();
    }

    public function isUserIsGroupeMember(Groupe $groupe): bool
    {
        /** @var User $user */
        if (!$user = $this->security->getUser()) {
            return false;
        }

        return $user->getLastParticipant()?->getGroupe()?->getId() === $groupe->getId();
    }

    public function isUserIsGroupeResponsable(Groupe $groupe): bool
    {
        /** @var User $user */
        if (!$user = $this->security->getUser()) {
            return false;
        }

        /** @var GroupeGn $groupeGn */
        if (!$groupeGn = $groupe->getGroupeGns()->last()) {
            return false;
        }

        return ($groupeGn->getParticipant()?->getUser()?->getId()
                ?? $groupe->getUserRelatedByResponsableId()?->getId()) === $user->getId();
    }
}
