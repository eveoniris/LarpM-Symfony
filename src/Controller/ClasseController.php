<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Classe;
use App\Enum\TypeClasse;
use App\Enum\Role;
use App\Form\Classe\ClasseType;
use App\Repository\ClasseRepository;
use App\Repository\CompetenceFamilyRepository;
use App\Security\MultiRolesExpression;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/classe', name: 'classe.')]
class ClasseController extends AbstractController
{
    #[Route('/add', name: 'add')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::REGLE, Role::ORGA), message: 'You are not allowed to access to this.')]
    public function addAction(Request $request): Response
    {
        return $this->handleCreateOrUpdate($request, new Classe(), ClasseType::class);
    }

    /** @param array<int, array<string, string|null>> $breadcrumb @param array<string, string> $routes @param array<string, string> $msg */
    protected function handleCreateOrUpdate(
        Request $request,
        object $entity,
        string $formClass,
        array $breadcrumb = [],
        array $routes = [],
        array $msg = [],
        ?callable $entityCallback = null,
    ): RedirectResponse|Response {
        return parent::handleCreateOrUpdate(
            request: $request,
            entity: $entity,
            formClass: $formClass,
            breadcrumb: $breadcrumb,
            routes: $routes,
            msg: [
                ...$msg,
                'entity' => $this->translator->trans('classe'),
                'entity_added' => $this->translator->trans('La classe a été ajoutée'),
                'entity_updated' => $this->translator->trans('La classe a été mise à jour'),
                'entity_deleted' => $this->translator->trans('La classe a été supprimée'),
                'entity_list' => $this->translator->trans('Liste des classes'),
                'title_add' => $this->translator->trans('Ajouter une classe'),
                'title_update' => $this->translator->trans('Modifier une classe'),
            ],
            entityCallback: $entityCallback,
        );
    }

    #[Route('/competences/cout', name: 'competences.cout', requirements: ['classe' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::REGLE, Role::ORGA), message: 'You are not allowed to access to this.')]
    public function competenceAction(
        ClasseRepository $classeRepository,
        CompetenceFamilyRepository $competenceFamilyRepository,
    ): Response {
        $classes = $classeRepository->findAllCreation();
        $competences = $competenceFamilyRepository->findAllOrderedByLabel();

        return $this->render('classe/competences.twig', ['classes' => $classes, 'competences' => $competences]);
    }

    #[Route(
        '/{classe}/delete',
        name: 'delete',
        requirements: ['classe' => Requirement::DIGITS],
        methods: [
            'DELETE',
            'GET',
            'POST',
        ],
    )]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::REGLE, Role::ORGA), message: 'You are not allowed to access to this.')]
    public function deleteAction(#[MapEntity] Classe $classe): RedirectResponse|Response
    {
        return $this->genericDelete($classe, 'Supprimer une classe', 'La classe a été supprimée', 'classe.list', [
            ['route' => $this->generateUrl('classe.list'), 'name' => 'Liste des classes'],
            [
                'route' => $this->generateUrl('classe.detail', ['classe' => $classe->getId()]),
                'connaissance' => $classe->getId(),
                'name' => $classe->getLabel(),
            ],
            ['name' => 'Supprimer une classe'],
        ]);
    }

    #[Route('/{classe}', name: 'detail', requirements: ['classe' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::REGLE, Role::ORGA), message: 'You are not allowed to access to this.')]
    public function detailAction(#[MapEntity] Classe $classe): Response
    {
        return $this->render('classe/detail.twig', ['classe' => $classe]);
    }

    /**
     * Récupération de l'image d'une classe en fonction du sexe.
     */
    #[Route('/{classe}/image/{sexe}', name: 'image', methods: ['GET'])]
    public function imageAction(#[MapEntity] Classe $classe, string $sexe): Response
    {
        $image = $classe->getImageM();
        if ('F' === $sexe) {
            $image = $classe->getImageF();
        }

        $filename = __DIR__ . '/../../assets/img/' . $image;

        if (!is_file($filename)) {
            throw $this->createNotFoundException();
        }

        $response = new Response((string) file_get_contents($filename));
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }

    #[Route('', name: 'index')]
    #[Route('', name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        ClasseRepository $classeRepository,
        CompetenceFamilyRepository $competenceFamilyRepository,
    ): Response {
        $pagerService->setRequest($request)->setRepository($classeRepository)->setLimit(50);

        $pagerService->setDefaultOrdersBy(['creation' => 'DESC', 'classe.label_masculin' => 'ASC']);

        $alias = $classeRepository->getAlias();
        $queryBuilder = $classeRepository->createQueryBuilder($alias);

        // User can only see the classe allowed on creation
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_REGLE')) {
            $queryBuilder = $classeRepository->creation($queryBuilder, true);
        }

        $paginator = $classeRepository->searchPaginated($pagerService, $queryBuilder, true);
        $parameters = [
            'pagerService' => $pagerService,
            'paginator' => $paginator,
        ];

        // Interface bêta : filtre par type et matrice classes x familles de compétences
        if ($this->getUser()?->isUiBeta()) {
            $parameters += $this->betaParameters($request, $competenceFamilyRepository, $classeRepository, iterator_to_array($paginator, false));
        }

        return $this->render('classe/list.twig', $parameters);
    }

    /**
     * Données de la liste des classes en interface bêta.
     *
     * @param array<int, Classe> $classes
     *
     * @return array<string, mixed>
     */
    private function betaParameters(Request $request, CompetenceFamilyRepository $competenceFamilyRepository, ClasseRepository $classeRepository, array $classes): array
    {
        // Charge les trois listes d'affinités en une requête (évite un N+1 par classe)
        if ([] !== $classes) {
            $classeRepository
                ->createQueryBuilder('c')
                ->select('c', 'cr', 'cf', 'cn')
                ->leftJoin('c.competenceFamilyCreations', 'cr')
                ->leftJoin('c.competenceFamilyFavorites', 'cf')
                ->leftJoin('c.competenceFamilyNormales', 'cn')
                ->where('c.id IN (:ids)')
                ->setParameter('ids', array_map(static fn (Classe $c): ?int => $c->getId(), $classes))
                ->getQuery()
                ->getResult();
        }

        $typeFilter = TypeClasse::tryFrom((string) $request->query->get('type'));

        $typeCounts = [];
        foreach ($classes as $classe) {
            $type = $classe->getType()->value;
            $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
        }

        if (null !== $typeFilter) {
            $classes = array_values(array_filter($classes, static fn (Classe $classe): bool => $classe->getType() === $typeFilter));
        }

        // Les familles uniquement composées de compétences secrètes sont réservées aux gestionnaires
        $canSeeSecret = $this->isGranted(Role::REGLE->value) || $this->isGranted(Role::ORGA->value) || $this->isGranted(Role::SCENARISTE->value);
        $families = array_values(array_filter($competenceFamilyRepository->findAllOrderedByLabel(), static function ($family) use ($canSeeSecret): bool {
            if ($canSeeSecret) {
                return true;
            }
            foreach ($family->getCompetences() as $competence) {
                if (!$competence->isSecret()) {
                    return true;
                }
            }

            return false;
        }));

        $affinities = [];
        foreach ($classes as $classe) {
            $affinities[$classe->getId()] = [
                'creation' => $classe
                    ->getCompetenceFamilyCreations()
                    ->map(static fn ($f): ?int => $f->getId())
                    ->toArray(),
                'favorite' => $classe
                    ->getCompetenceFamilyFavorites()
                    ->map(static fn ($f): ?int => $f->getId())
                    ->toArray(),
                'normale' => $classe
                    ->getCompetenceFamilyNormales()
                    ->map(static fn ($f): ?int => $f->getId())
                    ->toArray(),
            ];
        }

        return [
            'classes' => $classes,
            'families' => $families,
            'affinities' => $affinities,
            'types' => array_filter(TypeClasse::cases(), static fn (TypeClasse $type): bool => ($typeCounts[$type->value] ?? 0) > 0),
            'typeCounts' => $typeCounts,
            'typeFilter' => $typeFilter,
            'maClasse' => $this->classeDuJoueur(),
        ];
    }

    /**
     * Classe du personnage du joueur connecté (null sans personnage ni classe).
     */
    private function classeDuJoueur(): ?Classe
    {
        try {
            return $this->getUser()?->getPersonnage()?->getClasse();
        } catch (\Error) {
            return null; // propriété classe non initialisée
        }
    }

    #[Route('/{classe}/personnages', name: 'personnages', requirements: ['classe' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::REGLE, Role::ORGA), message: 'You are not allowed to access to this.')]
    public function personnagesAction(
        Request $request,
        #[MapEntity]
        Classe $classe,
        PersonnageService $personnageService,
        ClasseRepository $classeRepository,
    ): Response {
        $routeName = 'classe.personnages';
        $routeParams = ['classe' => $classe->getId()];
        $twigFilePath = 'personnage/sub_personnages.twig';
        $columnKeys = [
            'colId',
            'colStatut',
            'colNom',
        ]; // check if it's better in PersonnageService
        $personnages = $classe->getPersonnages();
        $additionalViewParams = [
            'classe' => $classe,
            'title' => 'Classe',
            'breadcrumb' => [
                [
                    'name' => 'Liste des classes',
                    'route' => $this->generateUrl('classe.list'),
                ],
                [
                    'name' => $classe->getLabel(),
                    'route' => $this->generateUrl('classe.detail', ['classe' => $classe->getId()]),
                ],
                [
                    'name' => 'Personnages ayant cette classe',
                ],
            ],
        ];

        // handle the request and return an array containing the parameters for the view
        $viewParams = $personnageService->getSearchViewParameters($request, $routeName, $routeParams, $columnKeys, $additionalViewParams, $personnages, $classeRepository->getPersonnages($classe));

        return $this->render($twigFilePath, $viewParams);
    }

    #[Route('/{classe}/update', name: 'update')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::REGLE, Role::ORGA), message: 'You are not allowed to access to this.')]
    public function updateAction(Request $request, #[MapEntity] Classe $classe): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate($request, $classe, ClasseType::class);
    }
}
