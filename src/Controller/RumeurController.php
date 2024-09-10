<?php

namespace App\Controller;

use App\Entity\Rumeur;
use App\Form\Rumeur\RumeurDeleteForm;
use App\Form\Rumeur\RumeurForm;
use App\Repository\RumeurRepository;
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

#[IsGranted('ROLE_SCENARISTE')]
#[Route('/rumeur', name: 'rumeur.')]
class RumeurController extends AbstractController
{
    /**
     * Liste de toutes les rumeurs.
     */
    #[Route(name: 'list')]
    public function listAction(
        Request $request,
        PagerService $pagerService,
        RumeurRepository $rumeurRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($rumeurRepository);

        return $this->render('rumeur/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $rumeurRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Lire une rumeur.
     */
    #[Route('/{rumeur}/detail', name: 'detail', requirements: ['rumeur' => Requirement::DIGITS])]
    public function detailAction(#[MapEntity] Rumeur $rumeur)
    {
        return $this->render('rumeur/detail.twig', ['rumeur' => $rumeur]);
    }

    /**
     * Ajouter une rumeur.
     */
    #[Route('/add', name: 'add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Rumeur(),
            RumeurForm::class
        );
    }

    /**
     * Mettre à jour une rumeur.
     */
    #[Route('/{rumeur}/update', name: 'update', requirements: ['rumeur' => Requirement::DIGITS])]
    public function updateAction(Request $request, #[MapEntity] Rumeur $rumeur): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            $rumeur,
            RumeurForm::class
        );
    }

    /**
     * Supression d'une rumeur.
     */
    #[Route('/{rumeur}/delete', name: 'delete', requirements: ['technologie' => Requirement::DIGITS])]
    public function deleteAction(#[MapEntity] Rumeur $rumeur): RedirectResponse|Response
    {
        return $this->genericDelete(
            $rumeur,
            'Supprimer une rumeur',
            'La rumeur a été supprimée',
            'rumeur.list',
            [
                ['route' => $this->generateUrl('rumeur.list'), 'name' => 'Liste des rumeurs'],
                [
                    'route' => $this->generateUrl('rumeur.detail', ['rumeur' => $rumeur->getId()]),
                    'rumeur' => $rumeur->getId(),
                    'name' => $rumeur->getLabel(),
                ],
                ['name' => 'Supprimer une rumeur'],
            ]
        );
    }
}
