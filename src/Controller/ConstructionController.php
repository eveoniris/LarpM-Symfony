<?php


namespace App\Controller;

use App\Entity\Construction;
use App\Form\ConstructionDeleteForm;
use App\Form\ConstructionForm;
use App\Repository\ConstructionRepository;
use App\Service\PagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_REGLE')]
class ConstructionController extends AbstractController
{
    /**
     * Présentation des constructions.
     */
    #[Route('/construction', name: 'construction.index')]
    public function indexAction(Request $request, PagerService $pagerService, ConstructionRepository $constructionRepository): Response
    {
        $pagerService->setRequest($request)->setRepository($constructionRepository)->setLimit(25);

        return $this->render('construction/index.twig', [
            'pagerService' => $pagerService,
            'paginator' => $constructionRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Ajoute une construction.
     */
    #[Route('/construction/add', name: 'construction.add')]
    public function addAction(Request $request,  EntityManagerInterface $entityManager): Response|RedirectResponse
    {
        $construction = new \App\Entity\Construction();

        $form = $this->createForm(ConstructionForm::class, $construction)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $construction = $form->getData();

            $entityManager->persist($construction);
            $entityManager->flush();

           $this->addFlash('success', 'La construction a été ajoutée.');

            return $this->redirectToRoute('construction.detail', ['construction' => $construction->getId()], 303);
        }

        return $this->render('construction/add.twig', [
            'construction' => $construction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie une construction.
     */
    #[Route('/construction/{construction}/update', name: 'construction.update')]
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Construction $construction): Response|RedirectResponse
    {
        $form = $this->createForm(ConstructionForm::class, $construction)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $construction = $form->getData();

            $entityManager->persist($construction);
            $entityManager->flush();

           $this->addFlash('success', 'La construction a été modifié.');

            return $this->redirectToRoute('construction.detail', ['construction' => $construction->getId()], 303);
        }

        return $this->render('construction/update.twig', [
            'construction' => $construction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une construction.
     */
    #[Route('/construction/{construction}/delete', name: 'construction.delete')]
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Construction $construction): Response|RedirectResponse
    {
        $form = $this->createForm(ConstructionDeleteForm::class, $construction)
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $construction = $form->getData();

            $entityManager->remove($construction);
            $entityManager->flush();

           $this->addFlash('success', 'La construction a été supprimée.');

            return $this->redirectToRoute('construction.index', [], 303);
        }

        return $this->render('construction/delete.twig', [
            'construction' => $construction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une construction.
     */
    #[Route('/construction/{construction}/detail', name: 'construction.detail')]
    public function detailAction(Construction $construction): Response
    {
        return $this->render('construction/detail.twig', [
            'construction' => $construction]);
    }

    /**
     * Liste des territoires ayant cette construction.
     */
    #[Route('/construction/{construction}/territoires', name: 'construction.territoires')]
    public function territoiresAction(Construction $construction): Response
    {
        return $this->render('construction/territoires.twig', [
            'construction' => $construction,
        ]);
    }
}
