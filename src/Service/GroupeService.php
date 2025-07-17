<?php

namespace App\Service;

use App\Entity\Background;
use App\Entity\Bonus;
use App\Entity\Debriefing;
use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\GroupeBonus;
use App\Entity\GroupeGn;
use App\Entity\GroupeHasIngredient;
use App\Entity\GroupeHasRessource;
use App\Entity\GroupeLangue;
use App\Entity\Ingredient;
use App\Entity\Item;
use App\Entity\Loi;
use App\Entity\Merveille;
use App\Entity\Personnage;
use App\Entity\PersonnageIngredient;
use App\Entity\PersonnageLangues;
use App\Entity\PersonnageRessource;
use App\Entity\Rarete;
use App\Entity\Ressource;
use App\Entity\SecondaryGroup;
use App\Entity\Territoire;
use App\Entity\User;
use App\Enum\BonusApplication;
use App\Enum\BonusPeriode;
use App\Enum\BonusType;
use App\Enum\Role;
use App\Enum\TerritoireStatut;
use App\Service\PersonnageService;
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
        protected ValidatorInterface     $validator,
        protected FormFactoryInterface   $formFactory,
        protected UrlGeneratorInterface  $urlGenerator,
        protected ConditionsService      $conditionsService,
        protected Security               $security,
    )
    {
    }

    public function sumAll(Gn $gn, PersonnageService $personnageService): array
    {
        $all = [];

        /** @var GroupeGn $groupeGn */
        foreach ($gn->getGroupeGns() as $groupeGn) {
            $groupe = $groupeGn->getGroupe();
            $id = $groupe->getId();

            $all[$id] = [
                'nom' => $groupe->getNom(),
                'id' => $id,
                'richesse' => $this->getAllRichesse($groupe),
                'renomme' => 0,
                'ingredient' => [],
                'langues' => [],
                'ressources' => [],
            ];

            /** @var GroupeHasIngredient $groupeIngredient */
            foreach ($this->getAllIngredient($groupe) as $groupeIngredient) {
                $ingredient = $groupeIngredient->getIngredient();
                if (!$ingredient) {
                    continue;
                }
                if (!isset($all[$id]['ingredient'][$ingredient->getId()])) {
                    $all[$id]['ingredient'][$ingredient->getId()] = ['nb' => 0, 'label' => strip_tags($ingredient->getLabel())];
                }

                $all[$id]['ingredient'][$ingredient->getId()]['nb'] += $groupeIngredient->getQuantite();
            }

            /** @var GroupeHasRessource $groupeRessource */
            foreach ($this->getAllRessource($groupe) as $groupeRessource) {
                $ressource = $groupeRessource->getRessource();
                if (!$ressource) {
                    continue;
                }

                if (!isset($all[$id]['ressources'][$ressource->getId()])) {
                    $all[$id]['ressources'][$ressource->getId()] = ['nb' => 0, 'label' => strip_tags($ressource->getLabel())];
                }
                $all[$id]['ressources'][$ressource->getId()]['nb'] += $ressource->getQuantite();
            }

            /** @var Personnage $personnage */
            foreach ($groupeGn->getPersonnages() as $personnage) {
                if (!$personnage->getVivant()) {
                    continue;
                }

                $all[$id]['richesse'] += $personnageService->getAllRichesse($personnage);
                $all[$id]['renomme'] += $personnageService->getAllRenomme($personnage);

                /** @var PersonnageIngredient $personnageIngredient */
                foreach ($personnageService->getAllIngredient($personnage) as $personnageIngredient) {
                    $ingredient = $personnageIngredient->getIngredient();
                    if (!$ingredient) {
                        continue;
                    }
                    if (!isset($all[$id]['ingredient'][$ingredient->getId()])) {
                        $all[$id]['ingredient'][$ingredient->getId()] = ['nb' => 0, 'label' => $ingredient->getLabel()];
                    }
                    $all[$id]['ingredient'][$ingredient->getId()]['nb'] += $personnageIngredient->getNombre();
                }

                /** @var PersonnageLangues $personnageLangues */
                foreach ($personnageService->getAllLangues($personnage) as $personnageLangues) {
                    $langue = $personnageLangues->getLangue();
                    if (!$langue) {
                        continue;
                    }
                    if (!isset($all[$id]['langues'][$langue->getId()])) {
                        $all[$id]['langues'][$langue->getId()] = ['nb' => 0, 'label' => $langue->getLabel()];
                    }
                    $all[$id]['langues'][$langue->getId()]['nb'] += 1;
                }

                /** @var PersonnageRessource $personnageRessource */
                foreach ($personnageService->getAllRessource($personnage) as $personnageRessource) {
                    $ressource = $personnageRessource->getRessource();
                    if (!$ressource) {
                        continue;
                    }
                    if (!isset($all[$id]['ressources'][$ressource->getId()])) {
                        $all[$id]['ressources'][$ressource->getId()] = ['nb' => 0, 'label' => $ressource->getLabel()];
                    }
                    $all[$id]['ressources'][$ressource->getId()]['nb'] += $personnageRessource->getNombre();
                }
            }
        }

        return $all;
    }

    public function getAllRichesse(Groupe $groupe): int
    {
        $all = 0;
        foreach ($this->getAllRichesseDisplay($groupe) as $richesse) {
            $all += $richesse['value'] ?? 0;
        }

        return $all;
    }

    public function getAllRichesseDisplay(Groupe $groupe): array
    {
        // (base + bonus) x3 [x0.5 si instable]

        $histories = [];

        /** @var Territoire $territoire */
        foreach ($groupe->getTerritoires() as $territoire) {
            $tresor = $territoire->getTresor();
            $base = $tresor;
            $constructions = [];

            foreach ($territoire->getConstructions() as $construction) {
                //  Comptoir commercial
                if (6 === $construction->getId()) {
                    $tresor += 5;
                    $constructions[] = '+ 5 ' . $construction->getLabel();
                }

                // Foyer d'orfèvre
                if (23 === $construction->getId()) {
                    $tresor += 10;
                    $constructions[] = '+ 10 ' . $construction->getLabel();
                }

                // Port
                if (10 === $construction->getId()) {
                    $tresor += 5;
                    $constructions[] = '+ 5 ' . $construction->getLabel();
                }
            }

            $value = $tresor * 3;

            if (!$territoire->isStable()) {
                $value *= 0.5;
            }

            // arrondi au sup
            $value = ceil($value);

            $label = sprintf(
                "<strong>%s pièces d'argent</strong> fournies par <strong>%s</strong>. Etat %s 3 x (%d %s)",
                $value,
                $territoire->getNom(),
                $territoire->isStable() ? 'stable' : 'instable 0.5 x',
                $base,
                $constructions ? ' ' . implode(', ', $constructions) : '',
            );

            $histories[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        foreach ($this->getAllBonus($groupe, BonusType::RICHESSE) as $bonus) {
            if (!$bonus->isRichesse()) {
                continue;
            }

            if (!$this->conditionsService->isValidConditions(
                $groupe,
                $bonus->getJsonData()['condition'] ?? [],
            )) {
                // on passe à l'item suivant
                continue;
            }

            $source = '';
            if ($bonus->getSourceTmp()) {
                $source .= $bonus->getSourceTmp() . ' - ';
            }
            if ($bonus->getMerveille()) {
                $source .= $bonus->getMerveille()->getLabel();
            }
            if ($bonus->getOrigine()) {
                $source .= $bonus->getOrigine()->getNom();
            }

            $histories[] = [
                'label' => '<strong>' . $bonus->getTitre() . '</strong> fourni(e)s par <strong>' . $source . '</strong></strong> fourni(e)s par <strong>' . $source . '</strong>',
                'value' => (int)$bonus->getValeur(),
            ];
        }

        if ($groupe->getRichesse() > 0) {
            $histories[] = ['label' => $groupe->getRichesse() . " pièces d'argent de richesse supplémentaire"];
        }

        return $histories;
    }

    /**
     * @return Collection<int, Bonus>
     */
    public function getAllBonus(
        Groupe        $groupe,
        ?BonusType    $type = null,
        bool          $withDisabled = false,
        ?BonusPeriode $periode = null,
    ): Collection
    {
        $all = new ArrayCollection();

        $this->getGroupeBonus($groupe, $type, $withDisabled, $periode, $all);

        $this->getMerveilleBonus($groupe, $type, $withDisabled, $periode, $all);

        return $all;
    }

    public function getGroupeBonus(
        Groupe        $groupe,
        ?BonusType    $type = null,
        bool          $withDisabled = false,
        ?BonusPeriode $periode = null,
        ?Collection   &$all = null,
    ): ArrayCollection
    {
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

            $bonus->setSourceTmp('GROUPE');

            if (!$all->containsKey($bonus->getId())) {
                $all->offsetSet($bonus->getId(), $bonus);
            }
        }

        return $all;
    }

    public function getMerveilleBonus(
        Groupe        $groupe,
        ?BonusType    $type = null,
        bool          $withDisabled = false,
        ?BonusPeriode $periode = null,
        ?Collection   &$all = null,
        array         $applications = [],
    ): ArrayCollection
    {
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

                // Todo merveille can had N bonus
                $bonus = $merveille->getBonus();

                if (null === $bonus) {
                    continue;
                }

                // To display on the bonus, Entity can have a lot of merveille
                $bonus->setMerveille($merveille);

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

                if ($bonus->getApplication()) {
                    foreach ($applications as $application) {
                        if ($bonus->getApplication() !== $application) {
                            continue 2;
                        }
                    }
                }

                if (!$all->containsKey($bonus->getId())) {
                    $all->offsetSet($bonus->getId(), $bonus);
                }
            }
        }

        return $all;
    }

    /**
     * @return Collection<int, GroupeHasIngredient>
     */
    public function getAllIngredient(Groupe $groupe): Collection
    {
        $all = clone $groupe->getGroupeHasIngredients();

        /** @var Territoire $territoire */
        foreach ($groupe->getTerritoires() as $territoire) {
            /** @var Ingredient $ingredientProvided */
            foreach ($territoire->getIngredients() as $ingredientProvided) {
                // Clone to avoid unwanted overwriting
                $ingredient = clone $ingredientProvided;
                $nb = $ingredient->getQuantite();
                if (!$territoire->isStable()) {
                    $nb = ceil($nb * 0.5);
                }

                $ingredient->setLabel(
                    '<strong>' . $ingredient->getLabel() . '</strong> fourni(e)s par <strong>' . $territoire->getNom() . '</strong>',
                );

                $groupeHasIngredident = new GroupeHasIngredient();
                $groupeHasIngredident->setQuantite($nb);
                $groupeHasIngredident->setIngredient($ingredient);
                $groupeHasIngredident->setGroupe($groupe);

                $this->addIngredientToAll($groupeHasIngredident, $all);
            }
        }

        foreach ($this->getAllBonus($groupe, BonusType::INGREDIENT) as $bonus) {
            if (!$bonus->isIngredient()) {
                continue;
            }

            $data = $bonus->getDataAsList('ingredient');
            foreach ($data as $ingredientData) {
                if (!isset($ingredientData['id']) || !is_numeric($ingredientData['id'])) {
                    continue;
                }

                if (!$this->conditionsService->isValidConditions(
                    $groupe,
                    $bonus->getConditions($ingredientData['condition'] ?? []),
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

                $source = '';
                if ($bonus->getSourceTmp()) {
                    $source .= $bonus->getSourceTmp() . ' - ';
                }
                if ($bonus->getMerveille()) {
                    $source .= $bonus->getMerveille()->getLabel();
                }
                if ($bonus->getOrigine()) {
                    $source .= $bonus->getOrigine()->getNom();
                }

                $ingredient->setLabel(
                    '<strong>' . $ingredient->getLabel() . '</strong> fourni(e)s par <strong>' . $source . '</strong>',
                );

                $groupeHasIngredident = new GroupeHasIngredient();
                $groupeHasIngredident->setQuantite(((int)($data['nombre'] ?? $bonus->getValeur())) ?: 1);
                $groupeHasIngredident->setIngredient($ingredient);
                $groupeHasIngredident->setGroupe($groupe);

                $this->addIngredientToAll($groupeHasIngredident, $all);
            }
        }

        return $all;
    }

    private function addIngredientToAll(GroupeHasIngredient $groupeIngredient, Collection $all): void
    {
        /** @var GroupeHasIngredient $existing */
        foreach ($all as $existing) {
            if (($existing->getIngredient()?->getId() === $groupeIngredient->getIngredient()?->getId())
                && ($existing->getIngredient()?->getLabel() === $groupeIngredient->getIngredient()?->getLabel())) {
                return;
            }
        }

        $all->add($groupeIngredient);
    }

    /**
     * @return Collection<int, GroupeHasRessource>
     */
    public function getAllRessource(Groupe $groupe): Collection
    {
        // Clone to avoid unwanted overwriting
        $all = clone $groupe->getGroupeHasRessources();

        /** @var Territoire $territoire */
        foreach ($groupe->getTerritoires() as $territoire) {
            /** @var Ressource $ressourceExported */
            foreach ($territoire->getExportations() as $ressourceExported) {
                // Clone to avoid unwanted overwriting
                $ressource = clone $ressourceExported;
                $nbRessource = $ressource->getQuantite();

                if (!$territoire->isStable()) {
                    $nbRessource = ceil($nbRessource * 0.5);
                }
                $ressource->setLabel(
                    '<strong>' . $ressource->getLabel() . '</strong> fourni(e)s par <strong>' . $territoire->getNom() . '</strong>',
                );

                $ressourceGroupe = new GroupeHasRessource();
                $ressourceGroupe->setQuantite($nbRessource);
                $ressourceGroupe->setRessource($ressource);
                $ressource = null;

                $this->addRessourceToAll($ressourceGroupe, $all);
            }
        }

        foreach ($this->getAllBonus($groupe, BonusType::RESSOURCE) as $bonus) {
            if (!$bonus->isRessource()) {
                continue;
            }

            // this condition is tested before JSON value if we use bonus value only
            if (!$this->conditionsService->isValidConditions(
                $groupe,
                $bonus->getConditions()['condition'] ?? [],
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

                // TODO enhance like we do for other with getConditions and multi bonus
                if (!$this->conditionsService->isValidConditions($groupe, $row['condition'] ?? [])) {
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

                $source = '';
                if ($bonus->getSourceTmp()) {
                    $source .= $bonus->getSourceTmp() . ' - ';
                }
                if ($bonus->getMerveille()) {
                    $source .= $bonus->getMerveille()->getLabel();
                }
                if ($bonus->getOrigine()) {
                    $source .= $bonus->getOrigine()->getNom();
                }
                $ressource->setLabel(
                    '<strong>' . $ressource->getLabel() . '</strong> fourni(e)s par <strong>' . $source . '</strong>',
                );

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
            $ressource->setLabel($bonus->getTitre() . ' - ' . $bonus->getDescription());
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

    public function getPersonnages(GroupeGn $groupeGn): Collection
    {
        return $groupeGn->getPersonnages();
    }

    /**
     * @return Collection<int, Item>
     */
    public function getAllItems(Groupe $groupe): Collection
    {
        $all = clone $groupe->getItems();

        foreach ($this->getAllBonus($groupe, BonusType::ITEM) as $bonus) {
            if (!$bonus->isItem()) {
                continue;
            }

            $data = $bonus->getDataAsList('items');

            foreach ($data as $itemData) {
                if (!isset($itemData['id']) || !is_numeric($itemData['id'])) {
                    continue;
                }

                if (!$this->conditionsService->isValidConditions($groupe, $itemData['condition'] ?? [])) {
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

                $source = '';
                if ($bonus->getSourceTmp()) {
                    $source .= $bonus->getSourceTmp() . ' - ';
                }
                if ($bonus->getMerveille()) {
                    $source .= $bonus->getMerveille()->getLabel();
                }
                if ($bonus->getOrigine()) {
                    $source .= $bonus->getOrigine()->getNom();
                }
                $item->setLabel('<strong>' . $item->getLabel() . '</strong> fourni(e)s par <strong>' . $source . '</strong>');

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
            )) {
                // on passe à l'item suivant
                continue;
            }

            $source = '';
            if ($bonus->getSourceTmp()) {
                $source .= $bonus->getSourceTmp() . ' - ';
            }
            if ($bonus->getMerveille()) {
                $source .= $bonus->getMerveille()->getLabel();
            }
            if ($bonus->getOrigine()) {
                $source .= $bonus->getOrigine()->getNom();
            }

            $prefix = '';
            if (BonusPeriode::RETOUR_DE_JEU->value === $bonus->getPeriode()->value) {
                $prefix = 'RETOUR DE JEU : ';
            }

            $all[] = sprintf(
                '%s<strong>%s</strong> : <strong>%s</strong>%s',
                $prefix,
                $source,
                $bonus->getTitre(),
                ' ' . $bonus->getDescription(),
            );
        }

        return $all;
    }

    public function getGroupeBackgroundsVisibleForCurrentUser(
        Groupe $groupe,
    ): ArrayCollection
    {
        return $this->filterVisibilityForCurrentUser($groupe, $groupe->getBackgrounds());
    }

    /**
     * @param Collection<Debriefing|Background> $datas
     */
    public function filterVisibilityForCurrentUser(Groupe $groupe, Collection $datas): ArrayCollection
    {
        $filtred = new ArrayCollection();

        // Public require at least a logged user
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user || !$this->security->isGranted(Role::USER->value)) {
            return $filtred;
        }

        $personnagesIds = [];
        foreach ($user->getPersonnages() as $personnage) {
            $personnagesIds[$personnage->getid()] = $personnage;
        }

        $groupeOwnerForGn = [];
        $groupeMemberForGn = [];
        /** @var GroupeGn $groupeGn */
        foreach ($groupe->getGroupeGns() as $groupeGn) {
            $partPersoId = $groupeGn->getParticipant()?->getPersonnage()?->getId();
            if ($partPersoId && isset($personnagesIds[$partPersoId]) && $groupeGn->getGn()->getId()) {
                $groupeOwnerForGn[$groupeGn->getGn()->getId()] = true;
            }

            /** @var Personnage $personnage */
            foreach ($groupeGn->getPersonnages() as $personnage) {
                if (isset($personnagesIds[$personnage->getId()]) && $groupeGn->getGn()->getId()) {
                    $groupeMemberForGn[$groupeGn->getGn()->getId()] = true;
                }
            }
        }

        foreach ($datas as $data) {
            if ($this->security->isGranted(Role::SCENARISTE->value)
                || $data->getVisibility()?->isPublic()
            ) {
                $filtred->add($data);
                continue;
            }

            // Owner For a specific GN
            if (!isset($groupeOwnerForGn[$data->getGn()?->getId()]) && $data->getVisibility()?->isGroupeOwner()) {
                continue;
            }

            // Member for a specific GN
            if (!isset($groupeMemberForGn[$data->getGn()?->getId()]) && $data->getVisibility()?->isGroupeMember()) {
                continue;
            }

            // Author of it
            if ($data->getVisibility()?->isAuthor() && $user->getId() !== $data->getUser()?->getId()) {
                continue;
            }

            $filtred->add($data);
        }

        return $filtred;
    }

    public function getGroupeDebriefingsVisibleForCurrentUser(
        Groupe $groupe,
    ): ArrayCollection
    {
        return $this->filterVisibilityForCurrentUser($groupe, $groupe->getDebriefings());
    }

    /**
     * @return array|Collection<int, Loi>
     */
    public function getLois(Groupe $groupe): array|Collection
    {
        // Lois du pays du personnage initié
        $lois = new ArrayCollection();
        /** @var Territoire $territoire */
        foreach ($groupe->getTerritoires() ?? new ArrayCollection() as $territoire) {
            /** @var Territoire $fief */
            foreach ($territoire->getAncestors() as $fief) {
                foreach ($fief->getLois() as $loi) {
                    $lois->add($loi);
                }
            }
        }

        return $lois;
    }

    public function getNbRessourcesBase(): int
    {
        return 3;
    }

    public function getNextSessionGn()
    {
        return $this->entityManager->getRepository(Gn::class)->findNext();
    }

    public function getStatutTerritoire(Territoire $territoire): TerritoireStatut
    {
        $dirigeant = $this->getSuzerain($territoire);
        if (!$dirigeant) {
            return TerritoireStatut::INSTABLE;
        }

        $renomme = $dirigeant->getRenomme();
        if (!$renomme) {
            return TerritoireStatut::INSTABLE;
        }

        if ($renomme < $this->getRenommeRequired($territoire)) {
            return TerritoireStatut::INSTABLE;
        }

        return TerritoireStatut::STABLE;
    }

    public function getSuzerain(Territoire $territoire): ?Personnage
    {
        /** @var GroupeGn $lastGroupeGn */
        $lastGroupeGn = $territoire->getGroupe()?->getGroupeGns()?->last();
        if (!$lastGroupeGn) {
            return null;
        }
        $dirigeant = $lastGroupeGn->getSuzerain(false);
        if (!$dirigeant) {
            return null;
        }

        return $dirigeant;
    }

    public function getRenommeRequired(Territoire $territoire): int
    {
        // NbFief => Min renommé
        $renommeByNbFiefs = [
            0 => 0,
            1 => 5,
            2 => 8,
            3 => 11,
            4 => 15,
            5 => 20,
        ];

        return $renommeByNbFiefs[max($territoire->getTerritoires()->count(), 1)] ?? 20;
    }

    public function hasOnePersonnageSuzerain(GroupeGn $groupeGn, ?User $user = null): bool
    {
        /** @var User $user */
        $user ??= $this->security->getUser();
        foreach ($user?->getPersonnages() as $personnage) {
            if ($personnage->getId() === $groupeGn->getSuzerain()?->getId()) {
                return true;
            }
        }

        return false;
    }

    public function hasPersonnageInSecondaryGroup(
        SecondaryGroup $secondaryGroup,
        ?User          $user = null,
    ): bool
    {
        foreach ($user?->getPersonnages() as $personnage) {
            if ($this->isInSecondaryGroup($secondaryGroup, $personnage)) {
                return true;
            }
        }

        return false;
    }

    public function isInSecondaryGroup(
        SecondaryGroup $secondaryGroup,
        ?Personnage    $personnage = null,
        ?User          $user = null,
    ): bool
    {
        // On se basera sur le personnage actif
        if ($user && !$personnage) {
            $personnage = $user->getPersonnage();
        }

        if (!$personnage) {
            return false;
        }

        return $secondaryGroup->isMembre($personnage)
            || $secondaryGroup->isResponsable($personnage);
    }

    public function isUserIsGroupeMember(Groupe $groupe): bool
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user) {
            return false;
        }

        return $user->getLastParticipant()?->getGroupe()?->getId() === $groupe->getId();
    }

    public function isUserIsGroupeResponsable(Groupe $groupe): bool
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user) {
            return false;
        }

        /** @var GroupeGn $groupeGn */
        $groupeGn = $groupe->getGroupeGns()->last();
        if (!$groupeGn) {
            return false;
        }

        return ($groupeGn->getParticipant()?->getUser()?->getId()
                ?? $groupe->getUserRelatedByResponsableId()?->getId()) === $user->getId();
    }
}
