<?php

namespace App\Service;

use App\Entity\Background;
use App\Entity\Bonus;
use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\Debriefing;
use App\Entity\Domaine;
use App\Entity\Espece;
use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\GroupeLangue;
use App\Entity\HeroismeHistory;
use App\Entity\Ingredient;
use App\Entity\Item;
use App\Entity\Langue;
use App\Entity\Level;
use App\Entity\Loi;
use App\Entity\OrigineBonus;
use App\Entity\Personnage;
use App\Entity\PersonnageIngredient;
use App\Entity\PersonnageLangues;
use App\Entity\PersonnageRessource;
use App\Entity\PugilatHistory;
use App\Entity\Rarete;
use App\Entity\Religion;
use App\Entity\RenommeHistory;
use App\Entity\Ressource;
use App\Entity\Sort;
use App\Entity\User;
use App\Enum\BonusPeriode;
use App\Enum\BonusType;
use App\Enum\CompetenceFamilyType;
use App\Enum\LevelType;
use App\Enum\Role;
use App\Form\PersonnageFindForm;
use App\Repository\ReligionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PersonnageService
{
    public array $columnDefinitions = [
        'colId' => [
            'label' => '#',
            'fieldName' => 'id',
            'sortFieldName' => 'id',
            'tooltip' => 'Numéro d\'identifiant',
            'colMd' => 'col-md-1',
            'css' => 'col-md-1 align-middle text-center',
        ],
        'colStatut' => [
            'label' => 'Statut',
            'fieldName' => 'status',
            'sortFieldName' => 'status',
            'tooltip' => 'Statut',
            'canOrder' => false,
            'colMd' => 'col-md-1',
            'css' => 'col-md-1 align-middle text-center',
        ],
        'colNom' => [
            'label' => 'Nom',
            'fieldName' => 'nom',
            'sortFieldName' => 'nom',
            'tooltip' => 'Nom et surnom du personnage',
            'css' => 'align-middle',
        ],
        'colClasse' => [
            'label' => 'Classe',
            'fieldName' => 'classe',
            'sortFieldName' => 'classe',
            'tooltip' => 'Classe du personnage',
            'css' => 'align-middle',
        ],
        'colGroupe' => [
            'label' => 'Groupe',
            'fieldName' => 'groupe',
            'sortFieldName' => 'groupe',
            'tooltip' => 'Dernier GN - Groupe participant',
            'css' => 'align-middle',
        ],
        'colRenommee' => [
            'label' => 'Renommée',
            'fieldName' => 'renomme',
            'sortFieldName' => 'renomme',
            'tooltip' => 'Points de renommée',
            'css' => 'col-md-1 align-middle text-center',
        ],
        'colPugilat' => [
            'label' => 'Pugilat',
            'fieldName' => 'pugilat',
            'sortFieldName' => 'pugilat',
            'canOrder' => false,
            'tooltip' => 'Points de pugilat',
            'css' => 'col-md-1 align-middle text-center',
        ],
        'colHeroisme' => [
            'label' => 'Héroïsme',
            'fieldName' => 'heroisme',
            'sortFieldName' => 'heroisme',
            'tooltip' => 'Points d\'héroisme',
            'css' => 'col-md-1 align-middle text-center',
        ],
        'colUser' => [
            'label' => 'Utilisateur',
            'fieldName' => 'user',
            'sortFieldName' => 'user',
            'tooltip' => 'Liste des utilisateurs (Nom et prénom) par GN',
            'css' => 'align-middle',
        ],
        'colXp' => [
            'label' => 'Points d\'expérience',
            'fieldName' => 'xp',
            'sortFieldName' => 'xp',
            'tooltip' => 'Points d\'expérience actuels sur le total max possible',
            'css' => 'col-md-1 align-middle text-center',
        ],
        'colHasAnomalie' => [
            'label' => 'Ano.',
            'fieldName' => 'hasAnomalie',
            'sortFieldName' => 'hasAnomalie',
            'tooltip' => 'Une pastille orange indique une anomalie',
            'css' => 'col-md-1 align-middle text-center',
        ],
    ];

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ValidatorInterface $validator,
        protected readonly FormFactoryInterface $formFactory,
        protected readonly UrlGeneratorInterface $urlGenerator,
        protected readonly CompetenceService $competenceService,
        protected readonly GroupeService $groupeService,
        protected readonly Security $security,
        protected readonly ConditionsService $conditionsService,
    ) {
    }

    public function addClasseCompetencesFamilyCreation(Personnage $personnage): ?CompetenceService
    {
        $personnage->setIsCreation(true);

        // ajout des compétences acquises à la création
        foreach ($personnage->getClasse()->getCompetenceFamilyCreations() as $competenceFamily) {
            if ($firstCompetence = $competenceFamily->getFirstCompetence()) {
                $competenceHandler = $this->addCompetence($personnage, $firstCompetence, true);
                if ($competenceHandler->hasErrors()) {
                    return $competenceHandler;
                }
            }
        }

        return null;
    }

    /**
     * Ajoute une compétence à un personnage existant.
     *
     * Si cela est impossible remonte un tableau d'erreur
     */
    public function addCompetence(
        Personnage $personnage,
        Competence $competence,
        bool $gratuite = false,
    ): CompetenceService {
        return $this->getCompetenceHandler($personnage, $competence)
            ->addCompetence(
                $gratuite
                    ? CompetenceService::COUT_GRATUIT
                    : $this->getCompetenceCout($personnage, $competence),
            );
    }

    /**
     * Fournis le handler qui attribut les bonus d'une compétence.
     */
    public function getCompetenceHandler(Personnage $personnage, Competence $competence): CompetenceService
    {
        return $this->competenceService->getCompetenceService($competence)->setPersonnage($personnage);
    }

    /**
     * Calcul le cout d'une compétence en fonction de la classe du personnage.
     */
    public function getCompetenceCout(Personnage $personnage, Competence $competence): int
    {
        return $this->getCompetenceHandler($personnage, $competence)->getCompetenceCout();
    }

    /**
     * Récupére la liste de toutes les religions non connue du personnage, vue admin.
     *
     * @return ArrayCollection|Religion[]
     */
    public function getAdminAvailableReligions(
        Personnage $personnage,
    ): ?ArrayCollection {
        // Pour le moment aucune différence entre vue user et vue admin
        return $this->getAvailableReligions($personnage);
    }

    /**
     * Récupére la liste de toutes les religions non connues du personnage.
     *
     * @return ArrayCollection|Religion[]
     */
    public function getAvailableReligions(
        Personnage $personnage,
    ): ArrayCollection {
        $availableReligions = new ArrayCollection();

        $repo = $this->entityManager->getRepository(Religion::class);
        $religions = $repo->findAllPublicOrderedByLabel();

        foreach ($religions as $religion) {
            if (!$this->knownReligion($personnage, $religion)) {
                $availableReligions->add($religion);
            }
        }

        return $availableReligions;
    }

    public function knownReligion(Personnage $personnage, Religion $religion): bool
    {
        $personnageReligions = $personnage->getPersonnagesReligions();

        foreach ($personnageReligions as $personnageReligion) {
            if ($personnageReligion->getReligion() === $religion) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, Competence>
     */
    public function getAllCompetences(Personnage $personnage): Collection
    {
        try {
            $all = new ArrayCollection(
                $this->entityManager->getRepository(Personnage::class)
                    ->findCompetencesOrdered($personnage),
            );
            $all = $personnage->getCompetences();
        } catch (\Exception $exception) {
            $all = new ArrayCollection();
        }

        foreach ($this->getAllBonus($personnage, BonusType::COMPETENCE) as $bonus) {
            if (!$bonus->isCompetence()) {
                continue;
            }

            // Si aucune n'est valide, on passe au bonus suivant
            // if (!$this->conditionsService->isAllConditionsValid($personnage, $bonus->getConditions())) {
            //    continue;
            // }

            // On récupère les éventuelles données de bonus à donner de type "competence"
            $competencesBonus = $bonus->getDataAsList('competence');

            // On résoud chaque possibilité
            foreach ($competencesBonus as $competenceBonus) {
                if (!$this->conditionsService->isValidConditions(
                    $personnage,
                    $competenceBonus,
                    $this,
                    isDataSet: true,
                )) {
                    continue;
                }

                if ($bonus->getCompetence()) {
                    $all->add($bonus->getCompetence());
                    continue;
                }

                // On utilise les données brutes du bonus en attendant leur existence en réel "Competence".
                $competence = new Competence();
                $competence->setDescription($bonus->getDescription());

                // cas spéciaux description
                $competence->setDescription($bonus->getDescription());
                if ($description = $this->conditionsService->getKeyValue('description', $competenceBonus)) {
                    $competence->setDescription($description);
                }

                // cas spéciaux index/level
                $index = $bonus->getData('index', Level::NIVEAU_1);
                if ($level = $this->conditionsService->getKeyValue('level', $competenceBonus)) {
                    $index = $level;
                } elseif ($level = $this->conditionsService->getKeyValue('index', $competenceBonus)) {
                    $index = $level;
                }

                if (is_numeric($index)) {
                    $competence->setLevel(
                        $this->entityManager->getRepository(Level::class)->findOneBy(['index' => $index]),
                    );
                } else {
                    $competence->setLevel(
                        $this->entityManager->getRepository(Level::class)->findOneBy(['label' => $index]),
                    );
                }

                $materiel = $bonus->getData('materiel', null);
                if ($mat = $this->conditionsService->getKeyValue('materiel', $competenceBonus)) {
                    $materiel = $mat;
                }
                if (empty($materiel) || !is_string($materiel)) {
                    $materiel = $bonus->getDescription();
                }
                $competence->setMateriel($materiel);

                $family = new CompetenceFamily();
                $family->setId($bonus->getId() * -1);

                $titre = $bonus->getTitre();
                if ($tit = $this->conditionsService->getKeyValue('titre', $competenceBonus)) {
                    $titre = $tit;
                }
                if ($bonus->getOrigine()) {
                    $titre = "Bonus d'origine : ".$personnage->getOrigine()?->getNom();
                }
                if (empty($titre)) {
                    $titre = $bonus->getTitre();
                }

                $family->setLabel($titre);
                $competence->setCompetenceFamily($family);

                $this->addCompetenceToAll($competence, $all);
            }
        }

        return $all;
    }

    /**
     * @return Collection<int, Bonus>
     */
    public function getAllBonus(
        Personnage $personnage,
        ?BonusType $type = null,
        bool $withDisabled = false,
        ?BonusPeriode $periode = null,
    ): Collection {
        $all = new ArrayCollection();

        // Todo voir les appel pour mieux gérer les "periodes" selon les type "once/unique" et constant

        // Origine/territoire
        $this->getOrigineBonus($personnage, $type, $withDisabled, $periode, $all);

        $this->getGroupeBonus($personnage, $type, $withDisabled, $periode, $all);

        $this->getMerveilleBonus($personnage, $type, $withDisabled, $periode, $all);

        // Ajout des bonus liés au personnage
        $this->getPersonnageBonus($personnage, $type, $withDisabled, $periode, $all);

        return $all;
    }

    public function getOrigineBonus(
        Personnage $personnage,
        ?BonusType $type = null,
        bool $withDisabled = false,
        ?BonusPeriode $periode = null,
        ?ArrayCollection &$all = null,
    ): ArrayCollection {
        $all ??= new ArrayCollection();

        // Ajout des bonus lié à l'origine / territoire
        $originesBonus = $withDisabled
            ? $personnage->getOrigine()?->getOriginesBonus()
            : $personnage->getOrigine()?->getValideOrigineBonus();
        $originesBonus ??= []; // avoid null on foreach
        /** @var OrigineBonus $origineBonus */
        foreach ($originesBonus as $origineBonus) {
            if (!$withDisabled && !$origineBonus->isValid()) {
                continue;
            }

            $bonus = $origineBonus->getBonus();

            // On évite les non désirés
            if (!$bonus || !$bonus->isTypeAndPeriode($type, $periode)) {
                continue;
            }

            // TODO ? tant que on a pas fait de reprise pour les placer en création dans personnage_bonus
            // Le bonus n'est actif que si le personnage est natif d'un territoire dont son 1er groupe est à l'origine.
            if (BonusPeriode::NATIVE->value !== $bonus->getPeriode()?->value
                && $personnage->getFirstParticipantGnGroupe()?->getTerritoire()?->getId() !== $personnage->getOrigine(
                )?->getId()
            ) {
                continue;
            }
            unset($origineBonus);

            $bonus->setSourceTmp('ORIGINE');

            if (!$all->containsKey($bonus->getId())) {
                $all->offsetSet($bonus->getId(), $bonus);
            }
        }

        return $all;
    }

    public function getGroupeBonus(
        Personnage $personnage,
        ?BonusType $type = null,
        bool $withDisabled = false,
        ?BonusPeriode $periode = null,
        ?Collection &$all = null,
    ): ArrayCollection {
        return $this->groupeService->getGroupeBonus(
            $personnage->getLastParticipantGnGroupe(),
            $type,
            $withDisabled,
            $periode,
            $all,
        );
    }

    public function getMerveilleBonus(
        Personnage $personnage,
        ?BonusType $type = null,
        bool $withDisabled = false,
        ?BonusPeriode $periode = null,
        ?Collection &$all = null,
    ): ArrayCollection {
        $all ??= new ArrayCollection();

        return $this->groupeService->getMerveilleBonus(
            $personnage->getLastParticipantGnGroupe(),
            $type,
            $withDisabled,
            $periode,
            $all,
        );
    }

    public function getPersonnageBonus(
        Personnage $personnage,
        ?BonusType $type = null,
        bool $withDisabled = false,
        ?BonusPeriode $periode = null,
        ?Collection &$all = null,
    ): ArrayCollection {
        $all ??= new ArrayCollection();

        foreach ($personnage->getPersonnageBonus() as $personnageBonus) {
            // On évite les types non désirés
            if (!$withDisabled && !$personnageBonus->isValid()) {
                continue;
            }

            $bonus = $personnageBonus->getBonus();
            if (!$bonus || !$bonus->isTypeAndPeriode($type, $periode)) {
                continue;
            }

            if (empty($bonus->getSourceTmp())) {
                $bonus->setSourceTmp('PERSONNEL');
            }

            if (!$all->containsKey($bonus->getId())) {
                $all->offsetSet($bonus->getId(), $bonus);
            }
        }

        return $all;
    }

    public function getTitre(
        Personnage $personnage,
        ?Gn $gn = null,
    ): ?string {
        return $this->entityManager->getRepository(GroupeGn::class)->getTitres($personnage, $gn);
    }

    private function addCompetenceToAll(
        Competence $competence,
        Collection $all,
    ): void {
        /** @var Competence $existing */
        foreach ($all as $existing) {
            if (
                ($existing->getId() === $competence->getId())
                && ($existing->getLabel() === $competence->getLabel())
            ) {
                return;
            }
        }

        $all->add($competence);
    }

    public function getAllHeroisme(
        Personnage $personnage,
    ): int {
        $all = $personnage->getHeroisme();

        foreach ($this->getAllBonus($personnage, BonusType::HEROISME) as $bonus) {
            if (!$bonus->isHeroisme()) {
                continue;
            }

            if (!$this->conditionsService->isValidConditions(
                $personnage,
                $bonus->getJsonData()['condition'] ?? [],
            )) {
                // on passe à l'item suivant
                continue;
            }

            $all += (int) $bonus->getValeur();
        }

        return $all;
    }

    public function getAllHeroismeDisplay(
        Personnage $personnage,
    ): array {
        $history = $personnage->getDisplayHeroisme();

        foreach ($this->getAllBonus($personnage, BonusType::HEROISME) as $bonus) {
            if (!$bonus->isHeroisme()) {
                continue;
            }

            if (!$this->conditionsService->isValidConditions(
                $personnage,
                $bonus->getJsonData()['condition'] ?? [],
            )) {
                // on passe au bonus suivant
                continue;
            }

            $heroismeHistory = new HeroismeHistory();
            $heroismeHistory->setHeroisme((int) $bonus->getValeur());
            $heroismeHistory->setExplication($bonus->getTitre());

            // test if this contains work well
            if (!$history->contains($heroismeHistory)) {
                $history->add($heroismeHistory);
            }
        }

        return $history;
    }

    /**
     * @return Collection<int, PersonnageIngredient>
     */
    public function getAllIngredient(
        Personnage $personnage,
    ): Collection {
        $all = $personnage->getPersonnageIngredients();

        foreach ($this->getAllBonus($personnage, BonusType::INGREDIENT) as $bonus) {
            if (!$bonus->isIngredient()) {
                continue;
            }

            $data = $bonus->getDataAsList('ingredients');

            foreach ($data as $ingredientData) {
                if (!isset($ingredientData['id']) || !is_numeric($ingredientData['id'])) {
                    continue;
                }

                if (!$this->conditionsService->isValidConditions(
                    $personnage,
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
                    $ingredient->setNiveau((int) ($data['niveau'] ?? 1));
                    $ingredient->setDose($data['dose'] ?? 'unité');
                }

                $this->addIngredientToAll($personnage, $ingredient, $all);
            }
        }

        return $all;
    }

    private function addIngredientToAll(
        Personnage $personnage,
        Ingredient $ingredient,
        Collection $all,
    ): void {
        /** @var PersonnageIngredient $existing */
        foreach ($all as $existing) {
            if (
                $existing->getIngredient()?->getId() === $ingredient->getId()
                && $existing->getIngredient()?->getLabel() === $ingredient->getLabel()
            ) {
                return;
            }
        }

        $personnageIngredient = new PersonnageIngredient();
        $personnageIngredient->setPersonnage($personnage);
        $personnageIngredient->setIngredient($ingredient);
        $all->add($personnageIngredient);
    }

    /**
     * @return Collection<int, Item>
     */
    public function getAllItems(
        Personnage $personnage,
    ): Collection {
        $all = $personnage->getItems();

        foreach ($this->getAllBonus($personnage, BonusType::ITEM) as $bonus) {
            if (!$bonus->isItem()) {
                continue;
            }

            $data = $bonus->getDataAsList('items');

            foreach ($data as $itemData) {
                if (!isset($itemData['id']) || !is_numeric($itemData['id'])) {
                    continue;
                }

                if (!$this->conditionsService->isValidConditions($personnage, $itemData['condition'] ?? [])) {
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
                    $item->setNumero($itemData['numero'] ?? (int) $bonus->getValeur());
                    $item->setIdentification($itemData['identification'] ?? 0);
                    $item->setCouleur($itemData['couleur'] ?? 'aucune');
                    $item->setDescription($itemData['description'] ?? $bonus->getDescription());
                    $item->setSpecial($itemData['special'] ?? null);
                    $item->setQuality(isset($itemData['quality']) ? (int) $itemData['quality'] : null);
                }

                $this->addItemToAll($personnage, $item, $all);
            }
        }

        return $all;
    }

    private function addItemToAll(
        Personnage $personnage,
        Item $item,
        Collection $items,
    ): void {
        /** @var Item $row */
        foreach ($items as $row) {
            if (
                $row->getId() === $item->getId()
                && $row->getLabel() === $item->getLabel()
            ) {
                return;
            }
        }

        $items->add($item);
    }

    public function getAllLangues(
        Personnage $personnage,
    ): Collection {
        /** @var Collection<int, PersonnageLangues> $allLanguages */
        $all = $personnage->getPersonnageLangues();

        /** @var Bonus $bonus */
        foreach ($this->getAllBonus($personnage, BonusType::LANGUE) as $bonus) {
            if (!$bonus->isLanguage()) {
                continue;
            }

            // Si aucune n'est valide, on passe au bonus suivant
            // if (!$this->conditionsService->isAllConditionsValid($personnage, $bonus->getConditions())) {
            //    continue;
            // }

            // On récupère les éventuelles données de bonus à donner de type "langue"
            $languesBonus = $bonus->getDataAsList('langue');

            // On résoud chaque possibilité
            foreach ($languesBonus as $langueBonus) {
                // Rien à donner ?
                $id = $this->conditionsService->getKeyValue('id', $langueBonus);
                if (empty($id) || !is_numeric($id)) {
                    continue;
                }

                if (!$this->conditionsService->isValidConditions(
                    $personnage,
                    $langueBonus,
                    $this,
                    isDataSet: true,
                )) {
                    continue;
                }

                // Attribution
                $langue = $this->entityManager
                    ->getRepository(Langue::class)
                    ->findOneBy(['id' => $id]);

                if (!$langue) {
                    // Generate one
                    $langue = new Langue();
                    $langue->setDescription($bonus->getDescription());
                    $langue->setLabel($bonus->getTitre());
                    $langue->setSecret(true);
                    $groupeLangue = new GroupeLangue();
                    $groupeLangue->setCouleur('Aucune');
                    $langue->setGroupeLangue($groupeLangue);
                }

                $this->addLangueToAll($personnage, $langue, $all, $bonus);
            }
        }

        return $all;
    }

    private function addLangueToAll(
        Personnage $personnage,
        Langue $langue,
        Collection $all,
        Bonus $bonus,
    ): void {
        /** @var PersonnageLangues $existing */
        foreach ($all as $existing) {
            if (
                $existing->getLangue()?->getId() === $langue->getId()
                && $existing->getLangue()?->getLabel() === $langue->getLabel()
            ) {
                return;
            }
        }

        $personnageLangue = new PersonnageLangues();
        $personnageLangue->setPersonnage($personnage);
        $personnageLangue->setLangue($langue);
        $personnageLangue->setSource($bonus->getSourceTmp() ?: 'BONUS');
        $all->add($personnageLangue);
    }

    public function getAllMateriel(
        Personnage $personnage,
    ): array {
        $all = $personnage->getMateriel() ? [$personnage->getMateriel()] : [];

        foreach ($this->getAllBonus($personnage, BonusType::MATERIEL) as $bonus) {
            if (!$bonus->isMateriel()) {
                continue;
            }

            if (!$this->conditionsService->isValidConditions(
                $personnage,
                $bonus->getJsonData()['condition'] ?? [],
            )) {
                // on passe à l'item suivant
                continue;
            }

            $all[] = $bonus->getTitre().' - '.$bonus->getDescription();
        }

        return $all;
    }

    public function getAllPugilat(
        Personnage $personnage,
    ): int {
        $pugilat = $personnage->getPugilat();

        foreach ($this->getAllBonus($personnage, BonusType::PUGILAT) as $bonus) {
            if (!$bonus->isPugilat()) {
                continue;
            }

            if (!$this->conditionsService->isValidConditions(
                $personnage,
                $bonus->getJsonData()['condition'] ?? [],
            )) {
                // on passe à l'item suivant
                continue;
            }

            $pugilat += (int) $bonus->getValeur();
        }

        return $pugilat;
    }

    public function getAllPugilatDisplay(
        Personnage $personnage,
    ): array {
        $histories = $personnage->getDisplayPugilat();

        foreach ($this->getAllBonus($personnage, BonusType::PUGILAT) as $bonus) {
            if (!$bonus->isPugilat()) {
                continue;
            }

            if (!$this->conditionsService->isValidConditions(
                $personnage,
                $bonus->getJsonData()['condition'] ?? [],
            )) {
                // on passe au bonus suivant
                continue;
            }

            $pugilatHistory = new PugilatHistory();
            $pugilatHistory->setPugilat((int) $bonus->getValeur());
            $pugilatHistory->setExplication($bonus->getTitre());

            $histories[] = $pugilatHistory;
        }

        return $histories;
    }

    public function getAllRenomme(
        Personnage $personnage,
    ): int {
        $allRenomme = $personnage->getRenomme();

        foreach ($this->getAllBonus($personnage, BonusType::RENOMME) as $bonus) {
            if (!$bonus->isRenomme()) {
                continue;
            }

            if (!$this->conditionsService->isValidConditions(
                $personnage,
                $bonus->getJsonData()['condition'] ?? [],
            )) {
                // on passe à l'item suivant
                continue;
            }

            $allRenomme += (int) $bonus->getValeur();
        }

        return $allRenomme;
    }

    public function getAllRenommeDisplay(
        Personnage $personnage,
    ): Collection {
        $history = $personnage->getRenommeHistories();

        foreach ($this->getAllBonus($personnage, BonusType::RENOMME) as $bonus) {
            if (!$bonus->isRenomme()) {
                continue;
            }

            if (!$this->conditionsService->isValidConditions(
                $personnage,
                $bonus->getJsonData()['condition'] ?? [],
            )) {
                // on passe au bonus suivant
                continue;
            }

            $renommeHistory = new RenommeHistory();
            $renommeHistory->setRenomme((int) $bonus->getValeur());
            $renommeHistory->setExplication($bonus->getTitre());

            // test if this contains work well
            if (!$history->contains($renommeHistory)) {
                $history->add($renommeHistory);
            }
        }

        return $history;
    }

    /**
     * @return Collection<int, PersonnageRessource>
     */
    public function getAllRessource(
        Personnage $personnage,
    ): Collection {
        $all = $personnage->getPersonnageRessources();

        foreach ($this->getAllBonus($personnage, BonusType::RESSOURCE) as $bonus) {
            if (!$bonus->isRessource()) {
                continue;
            }

            // this condition is tested before JSON value if we use bonus value only
            if (!$this->conditionsService->isValidConditions(
                $personnage,
                $bonus->getJsonData()['condition'] ?? [],
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

                if (!$this->conditionsService->isValidConditions($personnage, $row['condition'] ?? [])) {
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

                $ressourcePersonnage = new PersonnageRessource();
                $ressourcePersonnage->setNombre(((int) $data['nombre']) ?: 1);
                $ressourcePersonnage->setRessource($ressource);

                $this->addRessourceToAll($ressourcePersonnage, $all);
                continue 2; // next bonus
            }

            // If we use bonus values instead (condition tested before)
            $ressourcePersonnage = new PersonnageRessource();
            $ressourcePersonnage->setNombre((int) $bonus->getValeur());
            $ressource = new Ressource();
            $ressource->setLabel($bonus->getTitre().' - '.$bonus->getDescription());
            $rarete = new Rarete();
            $rarete->setLabel($data['rarete'] ?? 'Commun');
            $ressource->setRarete($rarete);
            $ressourcePersonnage->setRessource($ressource);

            $this->addRessourceToAll($ressourcePersonnage, $all);
        }

        return $all;
    }

    private function addRessourceToAll(
        PersonnageRessource $personnageRessource,
        Collection $all,
    ): void {
        /** @var PersonnageRessource $existing */
        foreach ($all as $existing) {
            if (
                $existing->getRessource()?->getId() === $personnageRessource->getRessource()?->getId()
                && $existing->getRessource()?->getLabel() === $personnageRessource->getRessource()?->getLabel()
            ) {
                return;
            }
        }

        $all->add($personnageRessource);
    }

    public function getAllRichesse(
        Personnage $personnage,
    ): int {
        $all = $personnage->getRichesse();

        foreach ($this->getAllBonus($personnage, BonusType::RICHESSE) as $bonus) {
            if (!$bonus->isRichesse()) {
                continue;
            }

            if (!$this->conditionsService->isValidConditions(
                $personnage,
                $bonus->getJsonData()['condition'] ?? [],
            )) {
                // on passe à l'item suivant
                continue;
            }

            $all += (int) $bonus->getValeur();
        }

        return $all ?? 0;
    }

    public function getAvailableCompetences(
        Personnage $personnage,
    ): ArrayCollection {
        $availableCompetences = new ArrayCollection();

        // les compétences de niveau supérieur sont disponibles
        $currentCompetences = $personnage->getCompetences();
        foreach ($currentCompetences as $competence) {
            $nextCompetence = $competence->getNext();
            if ($nextCompetence && !$currentCompetences->contains($nextCompetence)) {
                $availableCompetences->add($nextCompetence);
            }
        }

        // les compétences inconnues du personnage sont disponibles au niveau 1
        $unknownCompetences = $this->getUnknownCompetences($personnage);
        foreach ($unknownCompetences as $competence) {
            $availableCompetences->add($competence);
        }

        // trie des competences disponibles
        $iterator = $availableCompetences->getIterator();
        $iterator->uasort(static fn($a, $b) => $a->getLabel() <=> $b->getLabel());

        return new ArrayCollection(iterator_to_array($iterator));
    }

    public function getUnknownCompetences(
        Personnage $personnage,
    ): ArrayCollection {
        $unknownCompetences = new ArrayCollection();

        $competenceFamilies = $this->entityManager->getRepository(CompetenceFamily::class)->findAll();

        foreach ($competenceFamilies as $competenceFamily) {
            if (!$this->knownCompetenceFamily($personnage, $competenceFamily)) {
                $competence = $competenceFamily->getFirstCompetence();
                if ($competence) {
                    $unknownCompetences->add($competence);
                }
            }
        }

        return $unknownCompetences;
    }

    public function knownCompetenceFamily(
        Personnage $personnage,
        CompetenceFamily $competenceFamily,
    ): bool {
        foreach ($personnage->getCompetences() as $competence) {
            if ($competence->getCompetenceFamily() === $competenceFamily) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne la liste des toutes les religions inconnues d'un personnage.
     *
     * @return ArrayCollection $competenceNiveaux
     */
    public function getAvailableDescriptionReligion(
        Personnage $personnage,
    ): ArrayCollection {
        $availableDescriptionReligions = new ArrayCollection();

        $repo = $this->app['orm.em']->getRepository('\LarpManager\Entities\Religion');
        $religions = $repo->findAll();

        foreach ($religions as $religion) {
            if (!$personnage->getReligions()->contains($religion)) {
                $availableDescriptionReligions[] = $religion;
            }
        }

        return $availableDescriptionReligions;
    }

    /**
     * Trouve tous les domaines de magie non connus d'un personnage.
     *
     * @return ArrayCollection|Domaine[]
     */
    public function getAvailableDomaines(
        Personnage $personnage,
    ): ArrayCollection {
        $availableDomaines = new ArrayCollection();

        $repo = $this->entityManager->getRepository(Domaine::class);
        $domaines = $repo->findAll();

        foreach ($domaines as $domaine) {
            if (!$personnage->isKnownDomaine($domaine)) {
                $availableDomaines[] = $domaine;
            }
        }

        return $availableDomaines;
    }

    public function getAvailableLangues(
        Personnage $personnage,
        int $diffusion = 0,
    ): ArrayCollection {
        $availableLangues = new ArrayCollection();

        $repo = $this->entityManager->getRepository(Langue::class);
        $langues = $repo->findBy([], ['label' => 'ASC']);

        foreach ($langues as $langue) {
            if ($langue->getSecret()) {
                continue;
            }

            if ($langue->getDiffusion() === $diffusion
                && !$personnage->isKnownLanguage($langue)) {
                $availableLangues[] = $langue;
            }
        }

        return $availableLangues;
    }

    /**
     * Trouve tous les sorts non connus d'un personnage en fonction du niveau du sort.
     *
     * @return ArrayCollection|Sort[]
     */
    public function getAvailableSorts(
        Personnage $personnage,
        $niveau,
    ): ArrayCollection {
        $availableSorts = new ArrayCollection();

        $repo = $this->entityManager->getRepository(Sort::class);
        $sorts = $repo->findByNiveau($niveau);

        foreach ($sorts as $sort) {
            if (!$personnage->isKnownSort($sort)) {
                $availableSorts[] = $sort;
            }
        }

        return $availableSorts;
    }

    /**
     * Trouve toutes les technologies non connues d'un personnage.
     */
    public function getAvailableTechnologies(
        Personnage $personnage,
    ): ArrayCollection {
        $availableTechnologies = new ArrayCollection();

        $repo = $this->app['orm.em']->getRepository('\LarpManager\Entities\Technologie');
        $technologies = $repo->findPublicOrderedByLabel();

        foreach ($technologies as $technologie) {
            if (!$personnage->isKnownTechnologie($technologie)) {
                $availableTechnologies[] = $technologie;
            }
        }

        return $availableTechnologies;
    }

    /**
     * Available visibility: 'PRIVATE', 'PUBLIC', 'GROUPE_MEMBER', 'GROUPE_OWNER', 'AUTHOR'.
     */
    public function getGroupeBackgroundsVisibleForCurrentUser(
        Groupe $groupe,
    ): ArrayCollection {
        $backgrounds = new ArrayCollection();

        // Public require at least a logged user
        /** @var User $user */
        if (!$user = $this->security->getUser()) {
            return $backgrounds;
        }

        $personnagesIds = [];
        foreach ($user->getPersonnages() as $personnage) {
            $personnagesIds[$personnage->getid()] = $personnage;
        }

        $chefs = [];
        /** @var GroupeGn $groupeGn */
        foreach ($groupe->getGroupeGns() as $groupeGn) {
            $partPersoId = $groupeGn->getParticipant()?->getPersonnage()?->getId();
            if ($partPersoId && isset($personnagesIds[$partPersoId])) {
                $chefs[$groupeGn?->getGn()?->getId()] = true;
            }
        }

        $isGroupeMember = false;
        /** @var Personnage $personnage */
        foreach ($groupe->getPersonnages() as $personnage) {
            $uid = $personnage->getUser()?->getId();
            if ($uid && $uid === $user->getId()) {
                $isGroupeMember = true;
            }
        }

        /** @var Background $background */
        foreach ($groupe->getBackgrounds() as $background) {
            // Owner For a specific GN
            if ('GROUPE_OWNER' === $background->getVisibility() && !isset($chefs[$background->getGn()?->getId()])) {
                continue;
            }
            // For ALL GN
            if ($isGroupeMember && 'GROUPE_MEMBER' === $background->getVisibility()) {
                continue;
            }
            if ('AUTHOR' === $background->getVisibility() && $user->getId() !== $background->getUser()?->getId()) {
                continue;
            }
            // Scénariste
            if ('PRIVATE' === $background->getVisibility() && !$this->security->isGranted(Role::SCENARISTE->value)) {
                continue;
            }

            $backgrounds->add($background);
        }

        return $backgrounds;
    }

    public function getPersonnages(
        User $user,
    ): string {
        return $user->getPersonnages();
    }

    public function getGroupeDebriefingVisibleForCurrentUser(
        Groupe $groupe,
    ): ArrayCollection {
        $debriefings = new ArrayCollection();

        // Public require at least a logged user
        /** @var User $user */
        if (!$user = $this->security->getUser()) {
            return $debriefings;
        }

        $personnagesIds = [];
        foreach ($user->getPersonnages() as $personnage) {
            $personnagesIds[$personnage->getid()] = $personnage;
        }

        $chefs = [];
        /** @var GroupeGn $groupeGn */
        foreach ($groupe->getGroupeGns() as $groupeGn) {
            $partPersoId = $groupeGn->getParticipant()?->getPersonnage()?->getId();
            if ($partPersoId && isset($personnagesIds[$partPersoId])) {
                $chefs[$groupeGn?->getGn()?->getId()] = true;
            }
        }

        $isGroupeMember = false;
        /** @var Personnage $personnage */
        foreach ($groupe->getPersonnages() as $personnage) {
            $uid = $personnage->getUser()?->getId();
            if ($uid && $uid === $user->getId()) {
                $isGroupeMember = true;
            }
        }

        /** @var Debriefing $debriefing */
        foreach ($groupe->getDebriefings() as $debriefing) {
            // Owner For a specific GN
            if ('GROUPE_OWNER' === $debriefing->getVisibility() && !isset($chefs[$debriefing->getGn()?->getId()])) {
                continue;
            }
            // For ALL GN
            if ($isGroupeMember && 'GROUPE_MEMBER' === $debriefing->getVisibility()) {
                continue;
            }
            if ('AUTHOR' === $debriefing->getVisibility() && $user->getId() !== $debriefing->getUser()?->getId()) {
                continue;
            }
            // Scénariste
            if ('PRIVATE' === $debriefing->getVisibility() && !$this->security->isGranted(Role::SCENARISTE->value)) {
                continue;
            }

            $debriefings->add($debriefing);
        }

        return $debriefings;
    }

    public function getHumanEspece(): Espece
    {
        $especeRepository = $this->entityManager->getRepository(Espece::class);

        return $especeRepository->findOneBy(['nom' => 'Humain'])
            ?? $especeRepository->findOneBy(['id' => 1]);
    }

    /**
     * Fourni la dernière compétence acquise par un presonnage.
     */
    public function getLastCompetence(
        Personnage $personnage,
    ): ?Competence {
        $competence = null;
        $operationDate = null;

        foreach ($personnage->getExperienceUsages() as $experienceUsage) {
            if ($personnage->getCompetences()->contains($experienceUsage->getCompetence())) {
                if (!$operationDate || $operationDate < $experienceUsage->getOperationDate()) {
                    $operationDate = $experienceUsage->getOperationDate();
                    $competence = $experienceUsage->getCompetence();
                }
            }
        }

        return $competence;
    }

    /**
     * @return Collection<int, Loi>
     */
    public function getLois(
        Personnage $personnage,
    ): array|Collection {
        // Toutes les lois pour un expert
        if ($personnage->hasCompetenceLevel(CompetenceFamilyType::POLITICAL, LevelType::APPRENTICE)) {
            return $this->entityManager->getRepository(Loi::class)->findAll();
        }

        $lois = new ArrayCollection();

        /* old rules
        if ($personnage->hasCompetenceLevel(CompetenceFamilyType::POLITICAL, LevelType::INITIATED)) {
            // @var Loi $loi
            foreach ($this->groupeService->getLois($personnage->getLastParticipantGnGroupe()) as $loi) {
                $lois->add($loi);
            }
        }
        */

        return $lois;
    }

    /**
     * Retourne le tableau de paramètres à utiliser pour l'affichage de la recherche des personnages.
     */
    public function getSearchViewParameters(
        Request $request,
        string $routeName,
        array $routeParams = [],
        array $columnKeys = [],
        array $additionalViewParams = [],
        ?Collection $sourcePersonnages = null,
        ?QueryBuilder $query = null,
    ): array {
        // récupère les filtres et tris de recherche + pagination renseignés dans le formulaire
        $orderBy = $request->get('order_by') ?: 'id';
        $orderDir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $isAsc = 'ASC' === $orderDir;
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];
        $alias = $query->getRootAliases()[0] ?? 'p';

        $formData = $request->query->get('personnageFind');
        $religion = isset($formData['religion']) ? $this->entityManager->find(
            'LarpManager\Entities\Religion',
            $formData['religion'],
        ) : null;
        $competence = isset($formData['competence']) ? $this->entityManager->find(
            'LarpManager\Entities\Competence',
            $formData['competence'],
        ) : null;
        $classe = isset($formData['classe']) ? $this->entityManager->find(
            'LarpManager\Entities\Classe',
            $formData['classe'],
        ) : null;
        $groupe = isset($formData['groupe']) ? $this->entityManager->find(
            'LarpManager\Entities\Groupe',
            $formData['groupe'],
        ) : null;
        $optionalParameters = '';

        // construit le formulaire contenant les filtres de recherche

        $form = $this->formFactory->create(
            PersonnageFindForm::class,
            null,
            [
                'data' => [
                    'religion' => $religion,
                    'classe' => $classe,
                    'competence' => $competence,
                    'groupe' => $groupe,
                ],
                'method' => 'get',
                'csrf_protection' => false,
            ],
        );

        $form->handleRequest($request);

        // récupère les nouveaux filtres de recherche
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            $criteria[$type] = $value;
        }
        if ($religion) {
            $criteria['religion'] = $religion->getId();
            $optionalParameters .= "&personnageFind[religion]={$religion->getId()}";
        }
        if ($competence) {
            $criteria['competence'] = $competence->getId();
            $optionalParameters .= "&personnageFind[competence]={$competence->getId()}";
        }
        if ($classe) {
            $criteria['classe'] = $classe->getId();
            $optionalParameters .= "&personnageFind[classe]={$classe->getId()}";
        }
        if ($groupe) {
            $criteria['groupe'] = $groupe->getId();
            $optionalParameters .= "&personnageFind[groupe]={$groupe->getId()}";
        }

        // récupère la liste des personnages, filtrée, ordonnée et paginée
        // 2 choix possibles :
        // - on a déjà fourni la valeur de sourcePersonnages, dans ce cas, on effectue les filtre/tri dessus direct
        // - sourcePersonnages est vide, dans ce cas, on effectue la recherche en base, via le repository
        $repo = $this->entityManager->getRepository(Personnage::class);
        if (null === $sourcePersonnages) {
            // attention, il y a des propriétés sur lesquelles on ne peut pas appliquer le order by
            // car elles ne sont pas en base mais calculées, ça compliquerait trop le sql
            $orderByCalculatedFields = new ArrayCollection(['pugilat', 'heroisme', 'user', 'hasAnomalie', 'status']);
            if ($orderByCalculatedFields->contains($orderBy)) {
                // recherche basée uniquement sur les filtres
                $filteredPersonnages = $repo->findList($criteria);
                // pour le nombre de résultats, pas besoin de refaire de requête, on l'a déjà
                $numResults = count($filteredPersonnages);
                // on applique le tri
                // TODO PersonnageSorter::sort($filteredPersonnages, $orderBy, $isAsc); voir PersonnageManager::sort()
                $personnagesCollection = new ArrayCollection($filteredPersonnages);
                // on découpe suivant la pagination demandée
                $personnages = $personnagesCollection->slice($offset, $limit);
            } else {
                // recherche et applique directement en sql filtres + tri + pagination
                $personnages = $repo->findList(
                    $criteria,
                    ['by' => $orderBy, 'dir' => $orderDir],
                    $limit,
                    $offset,
                );
                // refait une requete pour récupérer le nombre de résultats suivant les critères
                $numResults = $repo->findCount($criteria);
            }
        } elseif ($query) {
            $personnages = $query->orderBy($alias.'.'.$orderBy, $orderDir)->getQuery();
        } else {
            // on effectue d'abord le filtre
            // TODO: pour le moment, finalement il n'y a plus besoin de filtre car le composant n'est plus affiché
            // il faudra cependant le mettre en place si un jour on souhaite l'activer hors de la page d'admin de liste perso
            // l'idée ce serait d'utiliser la méthode filter sur la collection et pour chaque champ, de réappliquer la bonne règle
            $filteredPersonnages = $sourcePersonnages;
            $numResults = $filteredPersonnages->count();
            $filteredPersonnagesArray = $filteredPersonnages->toArray();
            // puis le tri
            if ($orderBy && !empty($orderBy)) {
                // TODO PersonnageSorter::sort($filteredPersonnagesArray, $orderBy, $isAsc);
            }
            // puis la pagination
            $personnagesCollection = new ArrayCollection($filteredPersonnagesArray);
            // on découpe suivant la pagination demandée
            $personnages = ($offset && $limit)
                ? $personnagesCollection->slice($offset, $limit)
                : $personnagesCollection->toArray();
        }

        $paginator = $repo->findPaginatedQuery(
            $personnages,
            $limit,  // $this->getRequestLimit(),
            $page, // $this->getRequestPage()
        );

        // récupère les colonnes à afficher
        if (empty($columnKeys)) {
            // on prend l'ordre par défaut
            $columnDefinitions = $this->columnDefinitions;
        } else {
            // on reconstruit le tableau dans l'ordre demandé
            $columnDefinitions = [];
            foreach ($columnKeys as $columnKey) {
                if (array_key_exists($columnKey, $this->columnDefinitions)) {
                    $columnDefinitions[] = $this->columnDefinitions[$columnKey];
                }
            }
        }

        return array_merge(
            $additionalViewParams,
            [
                'personnages' => $personnages,
                'paginator' => $paginator,
                'form' => $form->createView(),
                'optionalParameters' => $optionalParameters,
                'columnDefinitions' => $columnDefinitions,
                'formPath' => $routeName,
                'formParams' => $routeParams,
            ],
        );
    }

    public function hasEspece(
        Personnage $personnage,
        Espece $espece,
    ): bool {
        foreach ($personnage->getEspeces() as $especePersonnage) {
            if ($especePersonnage->getId() === $espece->getId()) {
                return true;
            }
        }

        return false;
    }

    public function hasReligionSans(
        Personnage $personnage,
    ): bool {
        /** @var ReligionRepository $religionRepository */
        $religionRepository = $this->entityManager->getRepository(Religion::class);
        $qb = $religionRepository->createQueryBuilder('rl');
        $religion = $qb->where($qb->expr()->eq($qb->expr()->lower('rl.label'), ':lbl'))
            ->setParameter('lbl', 'sans')
            ->getQuery()
            ->getSingleResult();

        return $religion && $this->knownReligion($personnage, $religion);
    }

    public function isKnownLoi(Personnage $personnage, Loi $loi): bool
    {
        // We currently only work on the competence level
        return $personnage->hasCompetenceLevel(
            CompetenceFamilyType::POLITICAL,
            LevelType::APPRENTICE,
        );
    }

    public function isMemberOfGroup(
        Personnage $personnage,
        Groupe $groupe,
    ): bool {
        /** @var GroupeGn $groupeGn * */
        $groupeGn = $groupe->getGroupeGns()->last();

        return $groupeGn->getPersonnages()->contains($personnage);
    }

    public function removeCompetence(
        Personnage $personnage,
        Competence $competence,
        bool $gratuite = false,
    ): CompetenceService {
        return $this->getCompetenceHandler($personnage, $competence)
            ->removeCompetence(
                $gratuite
                    ? CompetenceService::COUT_GRATUIT
                    : $this->getCompetenceCout($personnage, $competence),
            );
    }
}
