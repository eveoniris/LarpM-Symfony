<?php

namespace App\Controller;

use App\Entity\Bonus;
use App\Form\Bonus\BonusForm;
use App\Repository\BonusRepository;
use App\Repository\PersonnageBonusRepository;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
#[Route('/bonus', name: 'bonus.')]
class BonusController extends AbstractController
{
    /**
     * Liste des bonus.
     */
    #[Route(name: 'index')]
    #[Route(name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        BonusRepository $bonusRepository,
    ): Response {
        $pagerService->setRequest($request)->setRepository($bonusRepository)->setLimit(50);

        return $this->render('bonus/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $bonusRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Ajout d'un bonus.
     */
    #[Route('/add', name: 'add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Bonus(),
            BonusForm::class,
            breadcrumb: [
                ['route' => $this->generateUrl('bonus.list'), 'name' => 'Liste des bonus'],
                ['name' => 'Ajouter un bonus'],
            ],
        );
    }

    /**
     * Détail d'un bonus.
     */
    #[Route('/{bonus}/detail', name: 'detail', requirements: ['bonus' => Requirement::DIGITS])]
    #[Route('/{bonus}', name: 'detail', requirements: ['bonus' => Requirement::DIGITS])]
    public function detailAction(#[MapEntity] Bonus $bonus): Response
    {
        return $this->render('bonus\detail.twig', [
            'bonus' => $bonus,
        ]);
    }

    /**
     * Mise à jour d'un bonus.
     */
    #[Route('/{bonus}/udpate', name: 'update', requirements: ['bonus' => Requirement::DIGITS])]
    public function updateAction(Request $request, #[MapEntity] Bonus $bonus): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            $bonus,
            BonusForm::class,
            breadcrumb: [
                ['route' => $this->generateUrl('bonus.list'), 'name' => 'Liste des bonus'],
                [
                    'route' => $this->generateUrl('bonus.detail', ['bonus' => $bonus->getId()]),
                    'name' => $bonus->getLabel(),
                ],
                ['name' => 'Modifier un bonus'],
            ],
        );
    }

    /**
     * Suppression d'une bonus.
     */
    #[Route('/{bonus}/delete', name: 'delete', requirements: ['bonus' => Requirement::DIGITS])]
    public function deleteAction(
        #[MapEntity] Bonus $bonus,
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $bonus,
            'Supprimer un bonus',
            'Le bonus a été supprimée',
            'bonus.list',
            [
                ['route' => $this->generateUrl('bonus.list'), 'name' => 'Liste des bonuss'],
                [
                    'route' => $this->generateUrl('bonus.detail', ['bonus' => $bonus->getId()]),
                    'bonus' => $bonus->getId(),
                    'name' => $bonus->getTitre(),
                ],
                ['name' => 'Supprimer un bonus'],
            ]
        );
    }

    #[Route('/{bonus}/personnages', name: 'personnages', requirements: ['bonus' => Requirement::DIGITS])]
    public function personnagesAction(
        Request $request,
        #[MapEntity] Bonus $bonus,
        PersonnageService $personnageService,
        BonusRepository $bonusRepository,
        PersonnageBonusRepository $personnageBonusRepository,
    ): Response {
        $routeName = 'bonus.personnages';
        $routeParams = ['bonus' => $bonus->getId()];
        $twigFilePath = 'bonus/personnages.twig';
        $columnKeys = ['colId', 'colStatut', 'colNom', 'colClasse', 'colGroupe', 'colUser'];
        $personnages = new ArrayCollection(); // todo $bonus->getPersonnages();
        $additionalViewParams = [
            'bonus' => $bonus,
        ];

        $viewParams = $personnageService->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages,
            $bonusRepository->getPersonnages($bonus)
        );

        return $this->render(
            $twigFilePath,
            $viewParams
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
                'entity' => $this->translator->trans('bonus'),
                'entity_added' => $this->translator->trans('Le bonus a été ajoutée'),
                'entity_updated' => $this->translator->trans('Le bonus a été mise à jour'),
                'entity_deleted' => $this->translator->trans('Le bonus a été supprimé'),
                'title_add' => $this->translator->trans('Ajouter un bonus'),
                'title_update' => $this->translator->trans('Modifier un bonus'),
            ],
        );
    }
}
