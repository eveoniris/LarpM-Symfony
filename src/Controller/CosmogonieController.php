<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cosmogonie;
use App\Enum\Role;
use App\Form\Cosmogonie\CosmogonieType;
use App\Repository\CosmogonieRepository;
use App\Security\MultiRolesExpression;
use App\Service\PagerService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cosmogonie', name: 'cosmogonie.')]
class CosmogonieController extends AbstractController
{
    #[Route(name: 'index')]
    #[Route(name: 'list')]
    #[IsGranted(new MultiRolesExpression(Role::CARTOGRAPHE, Role::SCENARISTE))]
    public function indexAction(Request $request, PagerService $pagerService, CosmogonieRepository $repository): Response
    {
        $pagerService->setRequest($request)->setRepository($repository);

        return $this->render('cosmogonie/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $repository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/add', name: 'add')]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function addAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate($request, new Cosmogonie(), CosmogonieType::class);
    }

    #[Route('/{cosmogonie}/detail', name: 'detail', requirements: ['cosmogonie' => Requirement::DIGITS])]
    #[IsGranted('ROLE_USER')]
    public function detailAction(#[MapEntity] Cosmogonie $cosmogonie): Response
    {
        $isAdmin = $this->isGranted(Role::CARTOGRAPHE->value) || $this->isGranted(Role::SCENARISTE->value);

        return $this->render('cosmogonie/detail.twig', [
            'cosmogonie' => $cosmogonie,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/{cosmogonie}/update', name: 'update', requirements: ['cosmogonie' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function updateAction(Request $request, #[MapEntity] Cosmogonie $cosmogonie): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate($request, $cosmogonie, CosmogonieType::class);
    }

    #[Route('/{cosmogonie}/delete', name: 'delete', requirements: ['cosmogonie' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function deleteAction(#[MapEntity] Cosmogonie $cosmogonie): RedirectResponse|Response
    {
        return $this->genericDelete($cosmogonie, 'Supprimer une entrée de cosmogonie', "L'entrée a été supprimée", 'cosmogonie.list', [
            ['route' => $this->generateUrl('cosmogonie.list'), 'name' => 'Liste de la cosmogonie'],
            [
                'route' => $this->generateUrl('cosmogonie.detail', ['cosmogonie' => $cosmogonie->getId()]),
                'cosmogonie' => (string) $cosmogonie->getId(),
                'name' => $cosmogonie->getLabel(),
            ],
            ['name' => 'Supprimer une entrée de cosmogonie'],
        ]);
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
                'entity' => $this->translator->trans('cosmogonie'),
                'entity_added' => $this->translator->trans("L'entrée de cosmogonie a été ajoutée"),
                'entity_updated' => $this->translator->trans("L'entrée de cosmogonie a été mise à jour"),
                'entity_deleted' => $this->translator->trans("L'entrée de cosmogonie a été supprimée"),
                'entity_list' => $this->translator->trans('Liste de la cosmogonie'),
                'title_add' => $this->translator->trans('Ajouter une entrée de cosmogonie'),
                'title_update' => $this->translator->trans('Modifier une entrée de cosmogonie'),
                ...$msg,
            ],
            entityCallback: $entityCallback,
        );
    }
}
