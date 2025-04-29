<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Enum\Role;
use App\Form\Classe\ClasseForm;
use App\Repository\ClasseRepository;
use App\Repository\CompetenceFamilyRepository;
use App\Security\MultiRolesExpression;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/classe', name: 'classe.')]
class ClasseController extends AbstractController
{
    #[Route('/add', name: 'add')]
    #[IsGranted(new MultiRolesExpression(
        Role::SCENARISTE, Role::REGLE, Role::ORGA,
    ), message: 'You are not allowed to access to this.')]
    public function addAction(
        Request $request,
    ): Response {
        return $this->handleCreateOrUpdate(
            $request,
            new Classe(),
            ClasseForm::class,
        );
    }

    protected function handleCreateOrUpdate(
        Request $request,
        $entity,
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
    #[IsGranted(new MultiRolesExpression(
        Role::SCENARISTE, Role::REGLE, Role::ORGA,
    ), message: 'You are not allowed to access to this.')]
    public function competenceAction(
        ClasseRepository $classeRepository,
        CompetenceFamilyRepository $competenceFamilyRepository,
    ): Response {
        $classes = $classeRepository->findAllCreation();
        $competences = $competenceFamilyRepository->findAllOrderedByLabel();

        return $this->render('classe/competences.twig', ['classes' => $classes, 'competences' => $competences]);
    }

    #[Route('/{classe}/delete', name: 'delete', requirements: ['classe' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    #[IsGranted(new MultiRolesExpression(
        Role::SCENARISTE, Role::REGLE, Role::ORGA,
    ), message: 'You are not allowed to access to this.')]
    public function deleteAction(
        #[MapEntity] Classe $classe,
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $classe,
            'Supprimer une classe',
            'La classe a été supprimée',
            'classe.list',
            [
                ['route' => $this->generateUrl('classe.list'), 'name' => 'Liste des classes'],
                [
                    'route' => $this->generateUrl('classe.detail', ['classe' => $classe->getId()]),
                    'connaissance' => $classe->getId(),
                    'name' => $classe->getLabel(),
                ],
                ['name' => 'Supprimer une classe'],
            ],
        );
    }

    #[Route('/{classe}', name: 'detail', requirements: ['classe' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(
        Role::SCENARISTE, Role::REGLE, Role::ORGA,
    ), message: 'You are not allowed to access to this.')]
    public function detailAction(#[MapEntity] Classe $classe): Response
    {
        return $this->render('classe/detail.twig', ['classe' => $classe]);
    }

    /**
     * Récupération de l'image d'une classe en fonction du sexe.
     */
    #[Route('/{classe}/image/{sexe}', name: 'image', methods: ['GET'])]
    public function imageAction(
        #[MapEntity] Classe $classe,
        string $sexe,
    ): Response {
        $image = $classe->getImageM();
        if ('F' === $sexe) {
            $image = $classe->getImageF();
        }

        $filename = __DIR__.'/../../assets/img/'.$image;

        $response = new Response(file_get_contents($filename));
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }

    #[Route('', name: 'index')]
    #[Route('', name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        ClasseRepository $classeRepository,
    ): Response {
        $pagerService->setRequest($request)->setRepository($classeRepository)->setLimit(50);

        $alias = $classeRepository->getAlias();
        $queryBuilder = $classeRepository->createQueryBuilder($alias);

        // User can only see the classe allowed on creation
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_REGLE')) {
            $queryBuilder = $classeRepository->creation($queryBuilder, true);
        }

        return $this->render('classe/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $classeRepository->searchPaginated($pagerService, $queryBuilder),
        ]);
    }

    #[Route('/{classe}/personnages', name: 'personnages', requirements: ['classe' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(
        Role::SCENARISTE, Role::REGLE, Role::ORGA,
    ), message: 'You are not allowed to access to this.')]
    public function personnagesAction(
        Request $request,
        #[MapEntity] Classe $classe,
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
        $viewParams = $personnageService->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages,
            $classeRepository->getPersonnages($classe),
        );

        return $this->render(
            $twigFilePath,
            $viewParams,
        );
    }

    #[Route('/{classe}/update', name: 'update')]
    #[IsGranted(new MultiRolesExpression(
        Role::SCENARISTE, Role::REGLE, Role::ORGA,
    ), message: 'You are not allowed to access to this.')]
    public function updateAction(
        Request $request,
        #[MapEntity] Classe $classe,
    ): RedirectResponse|Response {
        return $this->handleCreateOrUpdate(
            $request,
            $classe,
            ClasseForm::class,
        );
    }
}
