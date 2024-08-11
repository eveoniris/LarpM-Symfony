<?php

namespace App\Controller;

use App\Entity\Level;
use App\Form\LevelForm;
use App\Repository\LevelRepository;
use App\Service\PagerService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
#[Route('/level', name: 'level.')]
class LevelController extends AbstractController
{
    #[Route('/', name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        LevelRepository $levelRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($levelRepository);

        return $this->render('level/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $levelRepository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/add', name: 'add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        $level = new Level();

        return $this->handleCreateOrUpdate($request, $level, LevelForm::class);
    }

    #[Route('/{level}/update', name: 'update', requirements: ['level' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    public function updateAction(
        Request $request,
        #[MapEntity] Level $level
    ): RedirectResponse|Response {
        return $this->handleCreateOrUpdate($request, $level, LevelForm::class);
    }

    #[Route('/{level}/detail', name: 'detail', requirements: ['level' => Requirement::DIGITS], methods: ['GET'])]
    public function detailAction(#[MapEntity] Level $level): RedirectResponse|Response
    {
        return $this->render('level/detail.twig', ['level' => $level]);
    }

    #[Route('/{level}/delete', name: 'delete', requirements: ['level' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    public function deleteAction(#[MapEntity] Level $level): RedirectResponse|Response
    {
        return $this->genericDelete(
            $level,
            title: 'Supprimer un niveau',
            successMsg: 'Le niveau a été supprimée',
            redirect: 'level.list',
            breadcrumb: [
                ['route' => $this->generateUrl('level.list'), 'name' => 'Liste des niveaux'],
                ['route' => 'level.detail', 'name' => $level->getIndexLabel()],
                ['name' => 'Supprimer un niveau'],
            ]
        );
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
                ...$msg,
                'entity' => $this->translator->trans('niveau'),
                'entity_added' => $this->translator->trans('Le niveau a été ajouté'),
                'entity_updated' => $this->translator->trans('Le niveau a été mis à jour'),
                'entity_deleted' => $this->translator->trans('Le niveau a été supprimé'),
                'entity_list' => $this->translator->trans('Liste des niveaux'),
                'title_add' => $this->translator->trans('Ajouter un niveau'),
                'title_update' => $this->translator->trans('Modifier un niveau'),
            ],
            entityCallback: $entityCallback
        );
    }
}
