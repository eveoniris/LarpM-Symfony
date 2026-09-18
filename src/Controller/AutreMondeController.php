<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AutreMonde;
use App\Enum\Role;
use App\Form\AutreMonde\AutreMondeType;
use App\Repository\AutreMondeRepository;
use App\Security\MultiRolesExpression;
use App\Service\PagerService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/autre-monde', name: 'autreMonde.')]
class AutreMondeController extends AbstractController
{
    #[Route(name: 'index')]
    #[Route(name: 'list')]
    #[IsGranted(new MultiRolesExpression(Role::CARTOGRAPHE, Role::SCENARISTE))]
    public function indexAction(Request $request, PagerService $pagerService, AutreMondeRepository $repository): Response
    {
        $pagerService->setRequest($request)->setRepository($repository);

        return $this->render('autreMonde/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $repository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/add', name: 'add')]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function addAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate($request, new AutreMonde(), AutreMondeType::class);
    }

    #[Route('/{autreMonde}/detail', name: 'detail', requirements: ['autreMonde' => Requirement::DIGITS])]
    #[IsGranted('ROLE_USER')]
    public function detailAction(#[MapEntity] AutreMonde $autreMonde): Response
    {
        $isAdmin = $this->isGranted(Role::CARTOGRAPHE->value) || $this->isGranted(Role::SCENARISTE->value);

        return $this->render('autreMonde/detail.twig', [
            'autreMonde' => $autreMonde,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/{autreMonde}/update', name: 'update', requirements: ['autreMonde' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function updateAction(Request $request, #[MapEntity] AutreMonde $autreMonde): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate($request, $autreMonde, AutreMondeType::class);
    }

    #[Route('/{autreMonde}/delete', name: 'delete', requirements: ['autreMonde' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function deleteAction(#[MapEntity] AutreMonde $autreMonde): RedirectResponse|Response
    {
        return $this->genericDelete($autreMonde, 'Supprimer un autre monde', "L'autre monde a été supprimé", 'autreMonde.list', [
            ['route' => $this->generateUrl('autreMonde.list'), 'name' => 'Liste des autres mondes'],
            [
                'route' => $this->generateUrl('autreMonde.detail', ['autreMonde' => $autreMonde->getId()]),
                'autreMonde' => (string) $autreMonde->getId(),
                'name' => $autreMonde->getLabel(),
            ],
            ['name' => 'Supprimer un autre monde'],
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
                'entity' => $this->translator->trans('autre monde'),
                'entity_added' => $this->translator->trans("L'autre monde a été ajouté"),
                'entity_updated' => $this->translator->trans("L'autre monde a été mis à jour"),
                'entity_deleted' => $this->translator->trans("L'autre monde a été supprimé"),
                'entity_list' => $this->translator->trans('Liste des autres mondes'),
                'title_add' => $this->translator->trans('Ajouter un autre monde'),
                'title_update' => $this->translator->trans('Modifier un autre monde'),
                ...$msg,
            ],
            entityCallback: $entityCallback,
        );
    }
}
