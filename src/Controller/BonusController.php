<?php

namespace App\Controller;

use App\Entity\Bonus;
use App\Form\Bonus\BonusForm;
use App\Repository\BonusRepository;
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
        $pagerService->setRequest($request)->setRepository($bonusRepository);

        return $this->render('bonus/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $bonusRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Ajout d'une bonus.
     */
    #[Route('/add', name: 'add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Bonus(),
            BonusForm::class,
            routes: [
                'root' => 'bonus.',
                'entityAlias' => 'bonus',
            ],
        );
    }

    /**
     * Détail d'une bonus.
     */
    #[Route('/{bonus}/detail', name: 'detail', requirements: ['bonus' => Requirement::DIGITS])]
    public function detailAction(#[MapEntity] Bonus $bonus): Response
    {
        return $this->render('bonus\detail.twig', [
            'bonus' => $bonus,
        ]);
    }

    /**
     * Mise à jour d'une bonus.
     */
    #[Route('/{bonus}/udpate', name: 'update', requirements: ['bonus' => Requirement::DIGITS])]
    public function updateAction(Request $request, #[MapEntity] Bonus $bonus): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            $bonus,
            BonusForm::class
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
}
