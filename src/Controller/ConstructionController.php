<?php

namespace App\Controller;

use App\Entity\Construction;
use App\Form\ConstructionDeleteForm;
use App\Form\ConstructionForm;
use App\Repository\ConstructionRepository;
use App\Repository\TerritoireRepository;
use App\Service\PagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
#[Route('/construction', name: 'construction.')]
class ConstructionController extends AbstractController
{
    /**
     * Présentation des constructions.
     */
    #[Route('/', name: 'index')]
    #[Route('/', name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        ConstructionRepository $constructionRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($constructionRepository)->setLimit(25);

        return $this->render('construction/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $constructionRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Ajoute une construction.
     */
    #[Route('/add', name: 'add')]
    public function addAction(Request $request): Response|RedirectResponse
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Construction(),
            ConstructionForm::class
        );
    }

    /**
     * Modifie une construction.
     */
    #[Route('/{construction}/update', name: 'update', requirements: ['construction' => Requirement::DIGITS])]
    public function updateAction(Request $request, #[MapEntity] Construction $construction): Response|RedirectResponse
    {
        return $this->handleCreateOrUpdate(
            $request,
            $construction,
            ConstructionForm::class
        );
    }

    /**
     * Supprime une construction.
     */
    #[Route('/{construction}/delete', name: 'delete', requirements: ['construction' => Requirement::DIGITS])]
    public function deleteAction(
        #[MapEntity] Construction $construction
    ): Response|RedirectResponse {
        return $this->genericDelete(
            $construction,
            'Supprimer une construction',
            'La construction a été supprimée',
            'construction.list',
            [
                ['route' => $this->generateUrl('construction.list'), 'name' => 'Liste des constructions'],
                [
                    'route' => $this->generateUrl('construction.detail', ['construction' => $construction->getId()]),
                    'technologie' => $construction->getId(),
                    'name' => $construction->getLabel(),
                ],
                ['name' => 'Supprimer une construction'],
            ]
        );
    }

    /**
     * Détail d'une construction.
     */
    #[Route('/{construction}/detail', name: 'detail')]
    public function detailAction(Construction $construction): Response
    {
        return $this->render('construction/detail.twig', [
            'construction' => $construction,
        ]);
    }

    #[Route('/{construction}/territoires', name: 'territoires')]
    public function territoiresAction(
        Request $request,
        PagerService $pagerService,
        TerritoireRepository $territoireRepository,
        #[MapEntity] Construction $construction
    ): Response {
        $pagerService
            ->setRequest($request)
            ->setRepository($territoireRepository)
            ->setLimit(25);


        $alias = $territoireRepository->getAlias();
        $queryBuilder = $territoireRepository->createQueryBuilder($alias);

        return $this->render('construction/territoires.twig', [
            'pagerService' => $pagerService,
            'construction' => $construction,
            'paginator' => $territoireRepository->searchPaginated(
                $pagerService,
                $territoireRepository->construction($queryBuilder, $construction),
            ),
        ]);
    }

    protected function handleCreateOrUpdate(
        Request $request,
        $entity,
        string $formClass,
        array $breadcrumb = [],
        array $routes = [],
        array $msg = [],
        ?callable $entityCallback = null
    ): RedirectResponse|Response {
        return parent::handleCreateOrUpdate(
            request: $request,
            entity: $entity,
            formClass: $formClass,
            breadcrumb: $breadcrumb,
            routes: $routes,
            msg: [
                'entity' => $this->translator->trans('construction'),
                'entity_added' => $this->translator->trans('La construction a été ajoutée'),
                'entity_updated' => $this->translator->trans('La construction a été mise à jour'),
                'entity_deleted' => $this->translator->trans('La construction a été supprimée'),
                'entity_list' => $this->translator->trans('Liste des constructions'),
                'title_add' => $this->translator->trans('Ajouter une construction'),
                'title_update' => $this->translator->trans('Modifier une construction'),
                ...$msg,
            ],
            entityCallback: $entityCallback
        );
    }
}
