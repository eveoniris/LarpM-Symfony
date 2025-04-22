<?php

namespace App\Controller;

use App\Entity\Age;
use App\Form\AgeForm;
use App\Repository\AgeRepository;
use App\Repository\PersonnageRepository;
use App\Service\PagerService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
#[Route('/age', name: 'age.')]
class AgeController extends AbstractController
{
    #[Route('/', name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        AgeRepository $ageRepository,
    ): Response {
        $pagerService->setRequest($request)->setRepository($ageRepository);

        return $this->render('age/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $ageRepository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/{age}/perso', name: 'perso', requirements: ['age' => Requirement::DIGITS], methods: ['GET'])]
    public function persoAction(
        #[MapEntity] Age $age,
        Request $request,
        PagerService $pagerService,
        PersonnageRepository $personnageRepository,
    ): Response {
        $pagerService->setRequest($request)->setRepository($personnageRepository);

        return $this->render('age/perso.twig', [
            'age' => $age,
            'pagerService' => $pagerService,
            'paginator' => $personnageRepository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/{age}/detail', name: 'detail', requirements: ['age' => Requirement::DIGITS], methods: ['GET'])]
    public function detailAction(#[MapEntity] Age $age): RedirectResponse|Response
    {
        return $this->render('age/detail.twig', ['age' => $age]);
    }

    #[Route('/add', name: 'add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        $age = new Age();

        return $this->handleCreateOrUpdate($request, $age, AgeForm::class);
    }

    #[Route('/{age}/update', name: 'update', requirements: ['age' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    public function updateAction(
        Request $request,
        #[MapEntity] Age $age,
    ): RedirectResponse|Response {
        return $this->handleCreateOrUpdate($request, $age, AgeForm::class);
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
                'entity' => $this->translator->trans('age'),
                'entity_added' => $this->translator->trans("L'age a été ajouté"),
                'entity_updated' => $this->translator->trans("L'age a été mis à jour"),
                'entity_deleted' => $this->translator->trans("L'age a été supprimé"),
                'entity_list' => $this->translator->trans('Liste des ages'),
                'title_add' => $this->translator->trans('Ajouter un age'),
                'title_update' => $this->translator->trans('Modifier un age'),
            ],
            entityCallback: $entityCallback
        );
    }

    #[Route('/{age}/delete', name: 'delete', requirements: ['age' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    public function deleteAction(#[MapEntity] Age $age): RedirectResponse|Response
    {
        return $this->genericDelete(
            $age,
            'Supprimer un age',
            'L\'age a été supprimée',
            'age.list',
            [
                ['route' => $this->generateUrl('age.list'), 'name' => 'Liste des ages'],
                ['route' => $this->generateUrl('age.detail', ['age' => $age->getId()]), 'name' => $age->getLabel()],
                ['name' => 'Supprimer un age'],
            ]
        );
    }
}
