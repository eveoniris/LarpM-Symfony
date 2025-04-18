<?php


namespace App\Controller;

use App\Entity\Ingredient;
use App\Form\IngredientDeleteForm;
use App\Form\IngredientForm;
use App\Repository\IngredientRepository;
use App\Service\PagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class IngredientController extends AbstractController
{
    /**
     * Liste des ingrédients.
     */
    #[Route('/ingredient', name: 'ingredient.list')]
    public function adminListAction(Request $request, PagerService $pagerService, IngredientRepository $ingredientRepository)
    {
        $pagerService->setRequest($request)->setRepository($ingredientRepository)->setLimit(50);

        return $this->render('ingredient/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $ingredientRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Detail d'un ingredient.
     */
    #[Route('/ingredient/{ingredient}/detail', name: 'ingredient.detail')]
    public function adminDetailAction(Request $request, Ingredient $ingredient)
    {
        return $this->render('ingredient/detail.twig', [
            'ingredient' => $ingredient,
        ]);
    }

    /**
     * Ajoute un ingredient.
     */
    #[Route('/ingredient/add', name: 'ingredient.add')]
    public function adminAddAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $ingredient = new \App\Entity\Ingredient();

        $form = $this->createForm(IngredientForm::class, $ingredient)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->getData();

            $entityManager->persist($titre);
            $entityManager->flush();

           $this->addFlash('success', 'L\'ingredient a été ajouté');

            return $this->redirectToRoute('ingredient.detail', ['ingredient' => $ingredient->getId()], 303);
        }

        return $this->render('ingredient/add.twig', [
            'ingredient' => $ingredient,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un ingredient.
     */
    #[Route('/ingredient/{ingredient}/update', name: 'ingredient.update')]
    public function adminUpdateAction(Request $request,  EntityManagerInterface $entityManager, Ingredient $ingredient)
    {
        $form = $this->createForm(IngredientForm::class, $ingredient)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ingredient = $form->getData();

            $entityManager->persist($ingredient);
            $entityManager->flush();

           $this->addFlash('success', 'L\'ingredient a été sauvegardé');

            return $this->redirectToRoute('ingredient.detail', ['ingredient' => $ingredient->getId()], 303);
        }

        return $this->render('ingredient/update.twig', [
            'ingredient' => $ingredient,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un ingredient.
     */
    #[Route('/ingredient/{ingredient}/delete', name: 'ingredient.delete')]
    public function adminDeleteAction(Request $request,  EntityManagerInterface $entityManager, Ingredient $ingredient)
    {
        $form = $this->createForm(IngredientDeleteForm::class, $ingredient)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->getData();

            $entityManager->remove($ingredient);
            $entityManager->flush();

           $this->addFlash('success', 'L\'ingredient a été suprimé');

            return $this->redirectToRoute('ingredient.list', [], 303);
        }

        return $this->render('ingredient/delete.twig', [
            'ingredient' => $ingredient,
            'form' => $form->createView(),
        ]);
    }
}
