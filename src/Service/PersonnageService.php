<?php

namespace App\Service;

use App\Entity\Age;
use App\Entity\Bonus;
use App\Entity\Classe;
use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\Domaine;
use App\Entity\Espece;
use App\Entity\ExperienceGain;
use App\Entity\Genre;
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
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\PersonnageIngredient;
use App\Entity\PersonnageLangues;
use App\Entity\PersonnageRessource;
use App\Entity\PersonnageSecondaire;
use App\Entity\PersonnagesReligions;
use App\Entity\PersonnageTrigger;
use App\Entity\Potion;
use App\Entity\PugilatHistory;
use App\Entity\Rarete;
use App\Entity\Religion;
use App\Entity\ReligionLevel;
use App\Entity\RenommeHistory;
use App\Entity\Ressource;
use App\Entity\Sort;
use App\Entity\Technologie;
use App\Entity\Territoire;
use App\Entity\User;
use App\Enum\BonusApplication;
use App\Enum\BonusPeriode;
use App\Enum\BonusType;
use App\Enum\CompetenceFamilyType;
use App\Enum\LevelType;
use App\Enum\TriggerType;
use App\Form\Participant\ParticipantRemoveForm;
use App\Form\PersonnageFindForm;
use App\Repository\LangueRepository;
use App\Repository\ParticipantRepository;
use App\Repository\PersonnageRepository;
use App\Repository\PersonnageSecondaireRepository;
use App\Repository\ReligionRepository;
use App\Repository\RenommeHistoryRepository;
use App\Repository\TechnologieRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Psr\Log\LoggerInterface;
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
        protected readonly ValidatorInterface     $validator,
        protected readonly FormFactoryInterface   $formFactory,
        protected readonly UrlGeneratorInterface  $urlGenerator,
        protected readonly CompetenceService      $competenceService,
        protected readonly GroupeService          $groupeService,
        protected readonly Security               $security,
        protected readonly ConditionsService      $conditionsService,
        protected readonly DataFormatterService   $dataFormatterService,
        protected readonly LoggerInterface        $logger,
    )
    {
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
        bool       $gratuite = false,
    ): CompetenceService
    {
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
        bool       $withSecret = false,
    ): ?ArrayCollection
    {
        // Pour le moment aucune différence entre vue user et vue admin
        return $this->getAvailableReligions($personnage, $withSecret);
    }

    /**
     * Récupére la liste de toutes les religions non connues du personnage.
     *
     * @return ArrayCollection|Religion[]
     */
    public function getAvailableReligions(
        Personnage $personnage,
        bool       $withSecret = false,
    ): ArrayCollection
    {
        $availableReligions = new ArrayCollection();

        $repo = $this->entityManager->getRepository(Religion::class);
        $religions = $withSecret ? $repo->findAllOrderedByLabel() : $repo->findAllPublicOrderedByLabel();

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

    public function getPersonnagesCompetenceGn(Competence $competence, Gn $gn): Query
    {
        return $this->entityManager
            ->getRepository(Competence::class)
            ->getPersonnages($competence, $gn)
            ->getQuery();
    }

    public function getPersonnages(
        User $user,
    ): string
    {
        return $user->getPersonnages();
    }

    /**
     * @return Collection<int, Competence>
     */
    public function getAllCompetences(Personnage $personnage): Collection
    {
        $all = new ArrayCollection();
        try {
            /*$all = new ArrayCollection(
                $this->entityManager->getRepository(Personnage::class)
                    ->findCompetencesOrdered($personnage),
            );*/
            foreach ($personnage->getCompetences() as $competence) {
                // Handle Alchemy replace text
                if ($competence->isAlchemy()) {
                    $potions = $this->getPotionsDepart($personnage, $competence->getLevelType());

                    $potionsApprentie = $this->getPotionsDepart($personnage, LevelType::APPRENTICE);
                    $potionsInitie = $this->getPotionsDepart($personnage, LevelType::INITIATED);

                    $competence->setMateriel(
                        str_replace(
                            ['{POTION}', '{POTION_APPRENTI}', '{POTION_INITIE}', '{CARTE}'],
                            [
                                implode(', ', $potions),
                                implode(', ', $potionsApprentie),
                                implode(', ', $potionsInitie),
                                '3212' . $personnage->getId(),
                            ],
                            $competence->getMateriel(),
                        ),
                    );
                }
                $all->add($competence);
            }
        } catch (Exception $exception) {
            $all = new ArrayCollection();
            // dump($exception);
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

            // itself
            if (empty($competencesBonus)) {
                $competencesBonus = [$bonus];
            }

            // On résoud chaque possibilité
            foreach ($competencesBonus as $competenceBonus) {
                $isItSelf = $competenceBonus instanceof Bonus;
                $conditionsDataSet = $isItSelf ? $competenceBonus->getConditions() : $competenceBonus;

                if (!$this->conditionsService->isValidConditions(
                    $personnage,
                    $conditionsDataSet,
                    $this,
                    isDataSet: !$isItSelf, // looking a condition key in an array
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
                if ($description = $this->conditionsService->getKeyValue('description', $conditionsDataSet)) {
                    $competence->setDescription($description);
                }

                // cas spéciaux index/level
                $index = $bonus->getData('index', Level::NIVEAU_1);
                if ($level = $this->conditionsService->getKeyValue('level', $conditionsDataSet)) {
                    $index = $level;
                } elseif ($level = $this->conditionsService->getKeyValue('index', $conditionsDataSet)) {
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
                if ($mat = $this->conditionsService->getKeyValue('materiel', $conditionsDataSet)) {
                    $materiel = $mat;
                }
                if (!empty($materiel) && is_string($materiel)) {
                    // do not use description $materiel = $bonus->getDescription();
                    $competence->setMateriel($materiel);
                }

                $family = new CompetenceFamily();
                $family->setId($bonus->getId() * -1);

                $titre = $bonus->getTitre();
                if ($tit = $this->conditionsService->getKeyValue('titre', $conditionsDataSet)) {
                    $titre = $tit;
                }
                if ($bonus->getOrigine()) {
                    $titre = "Bonus d'origine : " . $personnage->getOrigine()?->getNom();
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

    protected function getPotionsDepart(Personnage $personnage, LevelType $level): array
    {
        $potions = [];

        // A des potions de départ
        /** @var Potion $potion */
        foreach ((array)$personnage->getLastParticipant()
            ?->getPotionsDepartByLevel($level?->getIndex()) as $potion) {
            if (null === $potion) {
                continue;
            }
            $potions[] = $potion->getLabel();
        }

        // N'a pas de potion de départ
        if (empty($potions)) {
            foreach ((array)$personnage->getPotionsNiveau(
                $level?->getIndex(),
            ) as $potion) {
                $potions[] = $potion->getLabel();
            }
        }

        return $potions;
    }

    /**
     * @return Collection<int, Bonus>
     */
    public function getAllBonus(
        Personnage    $personnage,
        ?BonusType    $type = null,
        bool          $withDisabled = false,
        ?BonusPeriode $periode = null,
    ): Collection
    {
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
        Personnage       $personnage,
        ?BonusType       $type = null,
        bool             $withDisabled = false,
        ?BonusPeriode    $periode = null,
        ?ArrayCollection &$all = null,
    ): ArrayCollection
    {
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
            // Seulement les bonus "NATIVE"
            if (BonusPeriode::NATIVE->value !== $bonus->getPeriode()?->value) {
                continue;
            }

            // Le bonus n'est actif que si le personnage est natif d'un territoire dont son 1er groupe est à l'origine.
            $firstGroupOrigin = $personnage->getFirstParticipantGnGroupe()?->getTerritoire()?->getId();
            if ($firstGroupOrigin && $firstGroupOrigin !== $personnage->getOrigine()?->getId()) {
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
        Personnage    $personnage,
        ?BonusType    $type = null,
        bool          $withDisabled = false,
        ?BonusPeriode $periode = null,
        ?Collection   &$all = null,
    ): ArrayCollection
    {
        if (!$groupe = $personnage->getLastParticipantGnGroupe()) {
            return new ArrayCollection();
        }

        return $this->groupeService->getGroupeBonus(
            $groupe,
            $type,
            $withDisabled,
            $periode,
            $all,
        );
    }

    public function getMerveilleBonus(
        Personnage    $personnage,
        ?BonusType    $type = null,
        bool          $withDisabled = false,
        ?BonusPeriode $periode = null,
        ?Collection   &$all = null,
        array         $application = [],
    ): ArrayCollection
    {
        $all ??= new ArrayCollection();

        if (!$groupe = $personnage->getLastParticipantGnGroupe()) {
            return $all;
        }

        return $this->groupeService->getMerveilleBonus(
            $groupe,
            $type,
            $withDisabled,
            $periode,
            $all,
            [BonusApplication::FICHE_PJ, BonusApplication::ENVELOPPE_PJ, BonusApplication::LARPMANAGER],
        );
    }

    public function getPersonnageBonus(
        Personnage    $personnage,
        ?BonusType    $type = null,
        bool          $withDisabled = false,
        ?BonusPeriode $periode = null,
        ?Collection   &$all = null,
    ): ArrayCollection
    {
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
        ?Personnage $personnage,
        ?Gn         $gn = null,
    ): ?string
    {
        if (!$personnage) {
            return null;
        }

        return $this->entityManager->getRepository(GroupeGn::class)->getTitres($personnage, $gn);
    }

    private function addCompetenceToAll(
        Competence $competence,
        Collection $all,
    ): void
    {
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
    ): int
    {
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

            $all += (int)$bonus->getValeur();
        }

        return $all;
    }

    public function getAllHeroismeDisplay(
        Personnage $personnage,
    ): array
    {
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
            $heroismeHistory->setHeroisme((int)$bonus->getValeur());
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
    ): Collection
    {
        $all = $personnage->getPersonnageIngredients();

        foreach ($this->getAllBonus($personnage, BonusType::INGREDIENT) as $bonus) {
            if (!$bonus->isIngredient()) {
                continue;
            }

            if ($bonus->getApplication() === BonusApplication::ENVELOPPE_GROUPE || $bonus->getApplication() === BonusApplication::FICHE_GROUPE) {
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
                    $ingredient->setNiveau((int)($data['niveau'] ?? 1));
                    $ingredient->setDose($data['dose'] ?? 'unité');
                }

                $personnageIngredient = new PersonnageIngredient();
                $personnageIngredient->setPersonnage($personnage);
                $personnageIngredient->setIngredient($ingredient);
                $personnageIngredient->setNombre($data['nombre'] ?? $bonus->getValeur());

                $this->addIngredientToAll($personnageIngredient, $all);

                continue 2;
            }

            $ingredient = new Ingredient();

            $label = $bonus->getData('label', $bonus->getTitre()) ?: 'BONUS';
            $ingredient->setLabel($label);
            $ingredient->setNiveau((int)($bonus->getData('niveau', 1)));
            $ingredient->setDose($bonus->getData('dose', 'unité'));

            $personnageIngredient = new PersonnageIngredient();
            $personnageIngredient->setPersonnage($personnage);
            $personnageIngredient->setIngredient($ingredient);
            $personnageIngredient->setNombre($bonus->getData('nombre', $bonus->getValeur()));


            $this->addIngredientToAll($personnageIngredient, $all);
        }

        return $all;
    }

    private function addIngredientToAll(
        PersonnageIngredient $personnageIngredient,
        Collection           $all,
    ): void
    {
        /** @var PersonnageIngredient $existing */
        foreach ($all as $existing) {
            if (
                $existing->getIngredient()?->getId() === $personnageIngredient->getIngredient()?->getId()
                && $existing->getIngredient()?->getLabel() === $personnageIngredient->getIngredient()?->getLabel()
            ) {
                return;
            }
        }

        $all->add($personnageIngredient);
    }

    /**
     * @return Collection<int, Item>
     */
    public function getAllItems(
        Personnage $personnage,
    ): Collection
    {
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
                    $item->setNumero($itemData['numero'] ?? (int)$bonus->getValeur());
                    $item->setIdentification($itemData['identification'] ?? 0);
                    $item->setCouleur($itemData['couleur'] ?? 'aucune');
                    $item->setDescription($itemData['description'] ?? $bonus->getDescription());
                    $item->setSpecial($itemData['special'] ?? null);
                    $item->setQuality(isset($itemData['quality']) ? (int)$itemData['quality'] : null);
                }

                $this->addItemToAll($personnage, $item, $all);
            }
        }

        return $all;
    }

    private function addItemToAll(
        Personnage $personnage,
        Item       $item,
        Collection $items,
    ): void
    {
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
    ): Collection
    {
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
        Langue     $langue,
        Collection $all,
        Bonus      $bonus,
    ): void
    {
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
    ): array
    {
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

            $description = $bonus->getData('materiel', $bonus->getDescription());
            $all[] = $bonus->getTitre() . ' - ' . $description;
        }

        return $all;
    }

    public function getAllPugilat(
        Personnage $personnage,
    ): int
    {
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

            $pugilat += (int)$bonus->getValeur();
        }

        return $pugilat;
    }

    public function getAllPugilatDisplay(
        Personnage $personnage,
    ): array
    {
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
            $pugilatHistory->setPugilat((int)$bonus->getValeur());
            $pugilatHistory->setExplication($bonus->getTitre());

            $histories[] = $pugilatHistory;
        }

        return $histories;
    }

    public function getAllRenomme(
        Personnage $personnage,
    ): int
    {
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

            $allRenomme += (int)$bonus->getValeur();
        }

        return $allRenomme;
    }

    public function getAllRenommeDisplay(
        Personnage $personnage,
    ): Collection
    {
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
            $renommeHistory->setRenomme((int)$bonus->getValeur());
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
    ): Collection
    {
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
                $ressourcePersonnage->setNombre(((int)$data['nombre']) ?: 1);
                $ressourcePersonnage->setRessource($ressource);

                $this->addRessourceToAll($ressourcePersonnage, $all);
                continue 2; // next bonus
            }

            // If we use bonus values instead (condition tested before)
            $ressourcePersonnage = new PersonnageRessource();
            $ressourcePersonnage->setNombre((int)$bonus->getValeur());
            $ressource = new Ressource();
            $titre = $bonus->getData('titre', $bonus->getTitre());
            $description = $bonus->getData('description');
            if ($description !== null) {
                $description = $bonus->getDescription() . ' - ';
            }
            $ressource->setLabel($titre . $description);
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
        Collection          $all,
    ): void
    {
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
    ): int
    {
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

            $all += (int)$bonus->getValeur();
        }

        return $all ?? 0;
    }

    public function getAvailableCompetences(
        Personnage $personnage,
    ): ArrayCollection
    {
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
    ): ArrayCollection
    {
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
        Personnage       $personnage,
        CompetenceFamily $competenceFamily,
    ): bool
    {
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
    public function getAvailableDescriptionReligion(Personnage $personnage): ArrayCollection
    {
        $availableDescriptionReligions = new ArrayCollection();

        $repo = $this->entityManager->getRepository(Religion::class);
        $religions = $repo->findAll();

        /** @var Religion $religion */
        foreach ($religions as $religion) {
            if (!$personnage->getReligions()->contains($religion) && !$religion->isSecret()) {
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
    ): ArrayCollection
    {
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
        int        $diffusion = 0,
    ): ArrayCollection
    {
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
    ): ArrayCollection
    {
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
    ): ArrayCollection
    {
        $availableTechnologies = new ArrayCollection();

        /** @var TechnologieRepository $repo */
        $repo = $this->entityManager->getRepository(Technologie::class);
        $technologies = $repo->findPublicOrderedByLabel();

        foreach ($technologies as $technologie) {
            if (!$personnage->isKnownTechnologie($technologie)) {
                $availableTechnologies[] = $technologie;
            }
        }

        return $availableTechnologies;
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
    ): ?Competence
    {
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
    ): array|Collection
    {
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

    /** Attribut 2 pts de renommé pour les nobles experts */
    public function lockGnGiveNoblityGnRenomme(?Gn $gn = null, int $pts = 2, bool $asGenerator = false)
    {
        $gnRepository = $this->entityManager->getRepository(Gn::class);
        /** @var ParticipantRepository $participantRepository */
        $participantRepository = $this->entityManager->getRepository(Participant::class);
        /** @var RenommeHistoryRepository $renommeHistoryRepository */
        $renommeHistoryRepository = $this->entityManager->getRepository(RenommeHistory::class);

        $gn ??= $gnRepository->findNext();

        /** @var Participant $participant */
        $i = 0;
        foreach ($participantRepository->findAllByCompentenceFamilyLevel($gn, CompetenceFamilyType::NOBILITY, LevelType::EXPERT) as $participant) {
            $i++;
            if (!$personnage = $participant->getPersonnage()) {
                $this->logger->warning(sprintf('Participant %d do not have a personnage', $participant->getId()));
            }

            $explication = sprintf('LH%d - Expert Noblesse', $gn->getId());

            if ($renommeHistory = $renommeHistoryRepository->findOneBy(['personnage' => $personnage->getId(), 'explication' => $explication])) {
                // skip as already done
                continue;
            }

            // it's only persist and do not flush data
            $this->competenceService->setPersonnage($personnage)->addRenomme($pts, $explication);
            $this->logger->info(sprintf('Add %d points to personnage %d', $pts, $personnage->getId()));
            $this->entityManager->flush();

            if ($asGenerator) {
                yield $i;
            }

            // Optimize batch
            if (0 === $i % 50) {
                // Actually cause a trouble in entity graph
                // $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
    }

    /** Don des effets compétences littérature par GN */
    public function lockGnGiveLiteratureGnBonus(?Gn $gn = null, bool $asGenerator = false)
    {
        $gnRepository = $this->entityManager->getRepository(Gn::class);
        /** @var ParticipantRepository $participantRepository */
        $participantRepository = $this->entityManager->getRepository(Participant::class);

        $gn ??= $gnRepository->findNext();

        // Later optimise get ALL with at least one level and then do bonus per level

        // Initie : Vous connaissez un Sort et une Recette Alchimique de niveau Apprenti par GN.
        // Expert : Vous connaissez un Sort et une Recette Alchimique de niveau Initié par GN.
        // Maitre : Vous connaissez un Sort et une Recette Alchimique de niveau Expert par GN.
        // Grand Maître: Vous connaissez un Sort et une Recette Alchimique de niveau Maître par GN.

        /** @var Participant $participant */
        $i = 0;
        $levels = [LevelType::INITIATED, LevelType::INITIATED, LevelType::MASTER, LevelType::GRAND_MASTER];
        foreach ($levels as $level) {
            foreach ($participantRepository->findAllByCompentenceFamilyLevel($gn, CompetenceFamilyType::LITERATURE, $level) as $participant) {
                $i++;
                if (!$personnage = $participant->getPersonnage()) {
                    $this->logger->warning(sprintf('Participant %d do not have a personnage', $participant->getId()));
                }

                // it's only persist and do not flush data
                $this->addPersonnageTrigger($personnage, TriggerType::SORT_APPRENTI, false);
                $this->addPersonnageTrigger($personnage, TriggerType::ALCHIMIE_APPRENTI, true);
                $this->logger->info(sprintf('Add Initiated Sort and Potion choice to personnage %d', $personnage->getId()));
                $this->entityManager->flush();

                if ($asGenerator) {
                    yield $i;
                }

                // Optimize batch
                if (0 === $i % 50) {
                    // Actually cause a trouble in entity graph
                    // $this->entityManager->clear();
                }
            }
        }
        $this->entityManager->flush();

        return;
    }

    public function addPersonnageTrigger(Personnage $personnage, TriggerType $triggerType, bool $flush = false): PersonnageTrigger
    {
        $trigger = new PersonnageTrigger();
        $trigger->setPersonnage($personnage);
        $trigger->setTag($triggerType);
        $trigger->setDone(false);
        $this->entityManager->persist($trigger);

        if ($flush) {
            $this->entityManager->flush();
        }

        return $trigger;
    }

    public function lockGnGiveSanctuaireGnEffect(bool $asGenerator = false)
    {
        // All player in groupe get the first level of religion if his not FANATICAL or DEISTE

        $deisteId = 36;
        $deiste = $this->entityManager->getRepository(Religion::class)->find($deisteId);
        $config = [
            // Zath
            // aucun
            // Anu
            3 => [
                23,
                38,
                56,
                105,
            ],
            // Bel
            5 => [66],
            // Bori
            6 => [
                17,
                27,
                28,
                99,
                108,
                137,
                139,
            ],

            // Crom
            7 => [
                17,
                26,
                27,
                28,
            ],
            // Culte ancêtre
            8 => [
                23,
                26,
                29,
                30,
                52,
                116,
                124,
                145,
            ],
            // Erlik
            10 => [
                1,
                40,
                45,
                72,
                80,
                82,
                126,
                133,
            ],
            // Isthar
            12 => [
                21,
                48,
                49,
                52,
                61,
                62,
                125,
            ],
            // Mitra
            14 => [
                4,
                5,
                9,
                11,
                13,
                14,
                21,
                55,
                56,
                57,
                58,
                59,
                107,
                137,
                146,
            ],

            // Set
            15 => [52, 61, 62],

            // Yun
            19 => [40, 45, 82, 126],


            // Ymir
            18 => [89, 132],
            // Ereshkigal
            28 => [40, 130],


        ];

        $religionLevel = $this->entityManager->getRepository(ReligionLevel::class)->findOneBy(
            ['index' => 1],
        );

        $i = 0;
        foreach ($config as $religionId => $groupeNumbers) {
            $religion = $this->entityManager->getRepository(Religion::class)->find($religionId);

            if (!$religion) {
                $this->logger->warning(sprintf('Religion %d not found', $religionId));
                continue;
            }

            $this->logger->info(sprintf('Adding first level of %s religion to personnage', $religion->getLabel()));

            foreach ($groupeNumbers as $groupeNumber) {
                $groupe = $this->entityManager->getRepository(Groupe::class)->findOneBy(['numero' => $groupeNumber]);

                if (!$groupe) {
                    $this->logger->warning(sprintf('Groupe with number %d not found', $groupeNumber));
                    continue;
                }

                /** @var Personnage $personnage */
                foreach ($groupe->getPersonnages() as $personnage) {
                    if (!$personnage->getVivant()) {
                        $this->logger->info(sprintf('Personnage %d is dead, dead is not affacted', $personnage->getId()));
                        continue;
                    }
                    if ($this->knownReligion($personnage, $religion)) {
                        $this->logger->info(sprintf('Personnage %d already know this religion', $personnage->getId()));
                        continue;
                    }
                    if ($deiste && $this->knownReligion($personnage, $deiste)) {
                        $this->logger->info(sprintf('Personnage %d is deiste and not affected', $personnage->getId()));
                        continue;
                    }
                    if ($personnage->isFanatique()) {
                        $this->logger->info(sprintf('Personnage %d is a fanatic and not affected', $personnage->getId()));
                        continue;
                    }

                    $personnageReligion = new PersonnagesReligions();
                    $personnageReligion->setPersonnage($personnage);
                    $personnageReligion->setReligion($religion);
                    $personnageReligion->setReligionLevel($religionLevel);
                    $this->entityManager->persist($personnageReligion);
                    $this->entityManager->flush();

                    ++$i;
                    if ($asGenerator) {
                        yield ++$i;
                    }
                }
            }
        }
    }

    public function lockGnSetDefaultCharacter(?Gn $gn = null): void
    {
        $gnRepository = $this->entityManager->getRepository(Gn::class);
        $classeRepository = $this->entityManager->getRepository(Classe::class);
        $personnageSecondaireRepository = $this->entityManager->getRepository(PersonnageSecondaire::class);
        $participantRepository = $this->entityManager->getRepository(Participant::class);
        $gn ??= $gnRepository->findNext();

        // Attribution par défaut
        /** @var PersonnageSecondaire $personnageSecondaire */
        $personnageSecondaire = $personnageSecondaireRepository->findOneBy(['id' => 1]);

        // On force les joueurs sans personnage à être Soldat
        $classe = $classeRepository->findOneBy(['id' => 14]); // Soldat
        $cache = 0;
        $age = $this->entityManager->getRepository(Age::class)->findOneBy(['id' => 1]);
        $territoire = $this->entityManager->getRepository(Territoire::class)->findOneBy(['id' => 1]);
        $genreM = $this->entityManager->getRepository(Genre::class)->findOneBy(['id' => 1]);
        $genreF = $this->entityManager->getRepository(Genre::class)->findOneBy(['id' => 2]);
        /** @var Participant $participant */
        foreach ($participantRepository->findAllWithoutPersonnage($gn) as $participant) {
            if (!$personnage = $participant->getPersonnage()) {


                $personnage = new Personnage();
                $personnage->setClasse($classe)
                    ->setAge($age)
                    ->setNom($participant->getUser()?->getDisplayName() ?? 'Nanoc')
                    ->setGroupe($participant->getGroupe())
                    ->setGenre(random_int(1, 2) === 1 ? $genreM : $genreF)
                    ->setUser($participant->getUser())
                    ->setXp($participant->getGn()->getXpCreation())
                    ->setTerritoire($territoire);

                // historique
                $historique = new ExperienceGain();
                $historique->setExplanation('Création de votre personnage');
                $historique->setOperationDate(new DateTime('NOW'));
                $historique->setPersonnage($personnage);
                $historique->setXpGain($participant->getGn()->getXpCreation());
                $this->entityManager->persist($historique);

                // Ajout des points d'expérience gagné grace à l'age
                $xpAgeBonus = $personnage->getAge()->getBonus();
                if ($xpAgeBonus) {
                    $personnage->addXp($xpAgeBonus);
                    $historique = new ExperienceGain();
                    $historique->setExplanation("Bonus lié à l'age");
                    $historique->setOperationDate(new DateTime('NOW'));
                    $historique->setPersonnage($personnage);
                    $historique->setXpGain($xpAgeBonus);
                    $this->entityManager->persist($historique);
                }

                $participant->setPersonnage($personnage);
            }


            /** @var Competence $competence */
            foreach ($personnageSecondaire->getCompetences() as $competence) {
                if (!$personnage->hasCompetence($competence)) {
                    $this->addCompetence($personnage, $competence);
                }
            }

            $this->entityManager->persist($personnage);
            $this->entityManager->persist($participant);
            $this->entityManager->flush();
            /*if (++$cache > 50) {
                $cache = 0;
                $this->entityManager->flush();
            }*/
        }
        $this->entityManager->flush();
    }

    public function lockGnSetDefaultLangue(?Gn $gn = null): void
    {
        $gnRepository = $this->entityManager->getRepository(Gn::class);
        $langueRepository = $this->entityManager->getRepository(Langue::class);
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);
        $gn ??= $gnRepository->findNext();

        // On force les personnages sans langue à avoir Aquillonien
        $langue = $langueRepository->findOneBy(['id' => 2]); // Aquillonien
        $cache = 0;
        foreach ($personnageRepository->findAllWithoutLangue($gn) as $personnage) {
            $personnageLangue = new PersonnageLangues();
            $personnageLangue->setPersonnage($personnage);
            $personnageLangue->setLangue($langue);
            $personnageLangue->setSource('ADMIN');
            $this->entityManager->persist($personnageLangue);

            if (++$cache > 50) {
                $cache = 0;
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();
    }

    public function lockGnSetDefaultSecondCharacter(?Gn $gn = null): void
    {
        $gnRepository = $this->entityManager->getRepository(Gn::class);
        $personnageSecondaireRepository = $this->entityManager->getRepository(PersonnageSecondaire::class);
        $participantRepository = $this->entityManager->getRepository(Participant::class);
        $gn ??= $gnRepository->findNext();
        /** @var PersonnageSecondaire $personnageSecondaire */
        $personnageSecondaire = $personnageSecondaireRepository->findOneBy(['id' => 1]);


        // On force les personnages sans personnage secondaire à être soldat
        /** @var Participant $participant */
        $cache = 0;
        foreach ($participantRepository->findAllWithoutPersonnageSecondaire($gn) as $participant) {
            $participant->setPersonnageSecondaire($personnageSecondaire);
            $this->entityManager->persist($participant);

            if (++$cache > 50) {
                $cache = 0;
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();
    }

    /**
     * Retourne le tableau de paramètres à utiliser pour l'affichage de la recherche des personnages.
     */
    public function getSearchViewParameters(
        Request       $request,
        string        $routeName,
        array         $routeParams = [],
        array         $columnKeys = [],
        array         $additionalViewParams = [],
        ?Collection   $sourcePersonnages = null,
        ?QueryBuilder $query = null,
    ): array
    {
        // récupère les filtres et tris de recherche + pagination renseignés dans le formulaire
        $orderBy = $request->get('order_by') ?: 'id';
        $orderDir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $isAsc = 'ASC' === $orderDir;
        $limit = (int)($request->get('limit') ?: 50);
        $page = (int)($request->get('page') ?: 1);
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
            $personnages = $query->orderBy($alias . '.' . $orderBy, $orderDir)->getQuery();
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
        Espece     $espece,
    ): bool
    {
        foreach ($personnage->getEspeces() as $especePersonnage) {
            if ($especePersonnage->getId() === $espece->getId()) {
                return true;
            }
        }

        return false;
    }

    public function hasReligionSans(
        Personnage $personnage,
    ): bool
    {
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
        Groupe     $groupe,
    ): bool
    {
        /** @var GroupeGn $groupeGn * */
        $groupeGn = $groupe->getGroupeGns()->last();

        return $groupeGn->getPersonnages()->contains($personnage);
    }

    public function prettifyData(?array $data): string
    {
        if (null === $data) {
            return '';
        }

        // need decorator ?

        return $this->dataFormatterService->format($data);
    }

    public function removeCompetence(
        Personnage $personnage,
        Competence $competence,
        bool       $gratuite = false,
    ): CompetenceService
    {
        return $this->getCompetenceHandler($personnage, $competence)
            ->removeCompetence(
                $gratuite
                    ? CompetenceService::COUT_GRATUIT
                    : $this->getCompetenceCout($personnage, $competence),
            );
    }
}
