<?php

namespace App\Service;

use App\Entity\Bonus;
use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\Domaine;
use App\Entity\GroupeLangue;
use App\Entity\Langue;
use App\Entity\Level;
use App\Entity\Personnage;
use App\Entity\PersonnageLangues;
use App\Entity\Religion;
use App\Enum\BonusType;
use App\Form\PersonnageFindForm;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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
    ) {
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
            $formData['religion']
        ) : null;
        $competence = isset($formData['competence']) ? $this->entityManager->find(
            'LarpManager\Entities\Competence',
            $formData['competence']
        ) : null;
        $classe = isset($formData['classe']) ? $this->entityManager->find(
            'LarpManager\Entities\Classe',
            $formData['classe']
        ) : null;
        $groupe = isset($formData['groupe']) ? $this->entityManager->find(
            'LarpManager\Entities\Groupe',
            $formData['groupe']
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
            ]
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
                    $offset
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
            $page // $this->getRequestPage()
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

        return array_merge($additionalViewParams, [
            'personnages' => $personnages,
            'paginator' => $paginator,
            'form' => $form->createView(),
            'optionalParameters' => $optionalParameters,
            'columnDefinitions' => $columnDefinitions,
            'formPath' => $routeName,
            'formParams' => $routeParams,
        ]
        );
    }

    public function getAvailableCompetences(Personnage $personnage): ArrayCollection
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
        $iterator->uasort(static fn ($a, $b) => $a->getLabel() <=> $b->getLabel());

        return new ArrayCollection(iterator_to_array($iterator));
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

    public function getUnknownCompetences(Personnage $personnage): ArrayCollection
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

    public function knownCompetenceFamily(Personnage $personnage, CompetenceFamily $competenceFamily): bool
    {
        foreach ($personnage->getCompetences() as $competence) {
            if ($competence->getCompetenceFamily() === $competenceFamily) {
                return true;
            }
        }

        return false;
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
     * Calcul le cout d'une compétence en fonction de la classe du personnage.
     */
    public function getCompetenceCout(Personnage $personnage, Competence $competence): int
    {
        return $this->getCompetenceHandler($personnage, $competence)->getCompetenceCout();
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
                    : $this->getCompetenceCout($personnage, $competence)
            );
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
                    : $this->getCompetenceCout($personnage, $competence)
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
     * @return Collection<int, Competence>
     */
    public function getAllCompetences(Personnage $personnage): Collection
    {
        $allCompetences = $personnage->getCompetences();
        foreach ($this->getAllBonus($personnage, BonusType::COMPETENCE) as $bonus) {
            if ($bonus->isCompetence()) {
                if ($bonus->getCompetence()) {
                    $allCompetences->add($bonus->getCompetence());
                } else {
                    // On utilise les données brutes du bonus en attendant leur existence en réel "Competence"
                    $competence = new Competence();
                    $competence->setDescription($bonus->getDescription());
                    $competence->setLevel(
                        $this->entityManager->getRepository(Level::class)->findOneBy(['index' => Level::NIVEAU_1])
                    );
                    $family = new CompetenceFamily();
                    $family->setId(-1);
                    $family->setLabel($bonus->getTitre() ?: "Bonus d'origine : ".$personnage->getOrigine()->getNom());
                    $competence->setCompetenceFamily($family);
                    if (!$allCompetences->contains($competence)) {
                        $allCompetences->add($competence);
                    }
                }
            }
        }

        return $allCompetences;
    }

    public function getAllLangues(Personnage $personnage): Collection
    {
        /** @var Collection<int, PersonnageLangues> $allLanguages **/
        $allLanguages = $personnage->getPersonnageLangues();

        /** @var Bonus $bonus */
        foreach ($this->getAllBonus($personnage, BonusType::LANGUE) as $bonus) {
            if (!$bonus->isLanguage()) {
                continue;
            }

            $langue = new Langue();
            $langue->setDescription($bonus->getDescription());
            $langue->setLabel($bonus->getTitre());
            $langue->setSecret(true);
            $groupeLangue = new GroupeLangue();
            $groupeLangue->setCouleur('Aucune');
            $langue->setGroupeLangue($groupeLangue);
            $personnageLangue = new PersonnageLangues();
            $personnageLangue->setLangue($langue);
            $personnageLangue->setPersonnage($personnage);
            $personnageLangue->setSource($bonus->getSourceTmp() ?: 'BONUS');

            /**
             * Possibilité de donnée $bonus->getJsonData()['langue']
             * 1: un id simple : on aura la langue directement
             * 2: un tableau d'une dimension : la langue à une condition
             * 3: un tableau de liste : des langues avec possiblement des conditions.
             */
            $langueJson = $bonus->getJsonData()['langue'] ?? null;

            if (!$langueJson) {
                continue;
            }

            // Mode 1 convertir en mode 3
            if (is_numeric($langueJson)) {
                $langue = $this->entityManager
                    ->getRepository(Langue::class)
                    ->findOneBy(['id' => $langueJson]);
                if ($langue) {
                    $personnageLangueTmp = clone $personnageLangue;
                    $personnageLangueTmp->setLangue($langue);
                    // $allLanguages->contains() will not work here
                    foreach ($allLanguages as $languages) {
                        if ($languages->getLangue()?->getId() === (int) $langue->getId()) {
                            // skip to next langue
                            continue 2;
                        }
                    }
                    $allLanguages->add($personnageLangueTmp);
                }
                continue;
            }

            // Bad data => bye
            if (!is_array($langueJson)) {
                continue;
            }

            foreach ($langueJson as $langueData) {
                // Mode 2 => convertir en mode 3
                if (is_numeric($langueData)) {
                    $langueData = [['id' => $langueData]];
                }

                // full mode 3
                if (isset($langueData['id'])) {
                    $langueData = [$langueData];
                }

                // Mode 3
                foreach ($langueData as $langue) {
                    if (!isset($langue['id']) || !is_numeric($langue['id'])) {
                        continue;
                    }

                    if (!$this->isValidConditions($personnage, $langue['condition'] ?? [])) {
                        // on passe à la langue suivante
                        continue;
                    }

                    // Attribution
                    $langue = $this->entityManager
                        ->getRepository(Langue::class)
                        ->findOneBy(['id' => $langue['id']]);

                    if ($langue) {
                        // $allLanguages->contains() will not work here
                        foreach ($allLanguages as $languages) {
                            if ($languages->getLangue()?->getId() === (int) $langue->getId()) {
                                // skip to next langue
                                continue 2;
                            }
                        }
                        $personnageLangueTmp = clone $personnageLangue;
                        $personnageLangueTmp->setLangue($langue);
                        $allLanguages->add($personnageLangueTmp);
                    }
                }
            }
        }

        return $allLanguages;
    }

    protected function isValidConditions(Personnage $personnage, array $conditions): bool
    {
        if (empty($conditions)) {
            return true;
        }

        // Tout en liste
        if (isset($conditions['type'])) {
            $conditions = [$conditions];
        }

        $mode = 'AND';
        foreach ($conditions as $condition) {
            // Par défaut les conditions sont des AND
            if ('OR' === $condition) {
                $mode = 'OR';
                continue;
            }

            if ($this->isValidCondition($personnage, $condition)) {
                // First OR mean TRUE
                if ('OR' === $mode) {
                    return true;
                }
            // In AND mode it's mean we only have a valid one, and we need to test others
            } elseif ('AND' === $mode) {
                // Any false condition in AND mode mean FALSE
                return false;
            }
        }

        return true;
    }

    protected function isValidCondition(Personnage $personnage, array $condition): bool
    {
        if (!$condition['type'] || !$condition['value']) {
            return true; // condition non testable
        }

        if (
            'ORIGINE' === $condition['type']
            && $personnage->getOrigine()->getId() === (int) $condition['value']
        ) {
            return true;
        }

        // Parmis les langues "basique" du personnage (sinon boucle infinie)
        if ('LANGUE' === $condition['type']) {
            /** @var PersonnageLangues $languePersonnage */
            $hasRequired = false;
            foreach ($personnage->getPersonnageLangues() as $languePersonnage) {
                if ($languePersonnage->getLangue()?->getId() === (int) $condition['value']) {
                    // Do not return yet : if personnage had already the bonus langue
                    $hasRequired = true;
                }
            }
            if ($hasRequired) {
                return true;
            }
        }

        // other type ?

        return false;
    }

    public function getAllBonus(Personnage $personnage, ?BonusType $type = null, bool $withDisabled = false): Collection
    {
        $all = new ArrayCollection();

        // Ajout des bonus lié à l'origine / territoire
        $origineBonus = $withDisabled
            ? $personnage->getOrigine()->getOriginesBonus()
            : $personnage->getOrigine()->getValideOrigineBonus();

        foreach ($origineBonus as $bonus) {
            // On évite les types non désirés
            if ($type && $bonus->getType() !== $type->value) {
                continue;
            }

            $bonus->setSourceTmp('BONUS ORIGINE');
            $all->add($bonus);
        }

        // TODO Ajout des bonus lié au groupe

        // TODO Ajout des bonus lié aux merveilles

        // TODO Ajout des bonus lié aux apprentissage

        // TODO Ajout des bonus lié au personnage

        return $all;
    }

    /**
     * Retourne la liste des toutes les religions inconnues d'un personnage.
     *
     * @return ArrayCollection $competenceNiveaux
     */
    public function getAvailableDescriptionReligion(Personnage $personnage): ArrayCollection
    {
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
     * Trouve tous les sorts non connus d'un personnage en fonction du niveau du sort.
     *
     * @return ArrayCollection|Sort[]
     */
    public function getAvailableSorts(Personnage $personnage, $niveau): ArrayCollection
    {
        $availableSorts = new ArrayCollection();

        $repo = $this->app['orm.em']->getRepository(Sort::class);
        $sorts = $repo->findByNiveau($niveau);

        foreach ($sorts as $sort) {
            if (!$personnage->isKnownSort($sort)) {
                $availableSorts[] = $sort;
            }
        }

        return $availableSorts;
    }

    /**
     * Trouve tous les domaines de magie non connus d'un personnage.
     *
     * @return ArrayCollection|Domaine[]
     */
    public function getAvailableDomaines(Personnage $personnage): ArrayCollection
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

    /**
     * Récupére la liste de toutes les religions non connues du personnage.
     *
     * @return ArrayCollection|Religion[]
     */
    public function getAvailableReligions(Personnage $personnage): ArrayCollection
    {
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

    /**
     * Récupére la liste de toutes les religions non connue du personnage, vue admin.
     *
     * @return ArrayCollection|Religion[]
     */
    public function getAdminAvailableReligions(Personnage $personnage): ?ArrayCollection
    {
        // Pour le moment aucune différence entre vue user et vue admin
        return $this->getAvailableReligions($personnage);
    }

    /**
     * Fourni la dernière compétence acquise par un presonnage.
     */
    public function getLastCompetence(Personnage $personnage): ?Competence
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
     * Trouve toutes les technologies non connues d'un personnage.
     */
    public function getAvailableTechnologies(Personnage $personnage): ArrayCollection
    {
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
}
