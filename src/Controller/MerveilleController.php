<?php

namespace App\Controller;

use App\Entity\Merveille;
use App\Form\Merveille\MerveilleForm;
use App\Repository\MerveilleRepository;
use App\Service\PagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
#[Route('/merveille', name: 'merveille.')]
class MerveilleController extends AbstractController
{
    #[Route('/add', name: 'add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Merveille(),
            MerveilleForm::class,
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
                'entity' => $this->translator->trans('merveille'),
                'entity_added' => $this->translator->trans('La merveille a été ajoutée'),
                'entity_updated' => $this->translator->trans('La merveille a été mise à jour'),
                'entity_deleted' => $this->translator->trans('La merveille a été supprimée'),
                'entity_list' => $this->translator->trans('Liste des merveilles'),
                'title_add' => $this->translator->trans('Ajouter une merveille'),
                'title_update' => $this->translator->trans('Modifier une merveille'),
                ...$msg,
            ],
            entityCallback: $entityCallback,
        );
    }

    #[Route('/{merveille}/delete', name: 'delete', requirements: ['merveille' => Requirement::DIGITS])]
    public function deleteAction(
        #[MapEntity] Merveille $merveille,
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $merveille,
            'Supprimer une merveille',
            'La merveille a été supprimée',
            'merveille.list',
            [
                ['route' => $this->generateUrl('merveille.list'), 'name' => 'Liste des merveilles'],
                [
                    'route' => $this->generateUrl('merveille.detail', ['merveille' => $merveille->getId()]),
                    'merveille' => $merveille->getId(),
                    'name' => $merveille->getLabel(),
                ],
                ['name' => 'Supprimer une merveille'],
            ],
        );
    }

    #[Route('/{merveille}/detail', name: 'detail', requirements: ['merveille' => Requirement::DIGITS])]
    public function detailAction(#[MapEntity] Merveille $merveille): Response
    {
        return $this->render('merveille\detail.twig', [
            'merveille' => $merveille,
        ]);
    }

    #[Route(name: 'index')]
    #[Route(name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        MerveilleRepository $merveilleRepository,
    ): Response {
        $pagerService->setRequest($request)->setRepository($merveilleRepository)->setLimit(50);

        return $this->render('merveille/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $merveilleRepository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/{merveille}/udpate', name: 'update', requirements: ['merveille' => Requirement::DIGITS])]
    public function updateAction(Request $request, #[MapEntity] Merveille $merveille): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            $merveille,
            MerveilleForm::class,
        );
    }
}
