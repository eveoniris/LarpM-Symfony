<?php


namespace App\Controller;

use App\Entity\Construction;
use App\Form\ConstructionDeleteForm;
use App\Form\ConstructionForm;
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
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Construction::class);
        $constructions = $repo->findAllOrderedByLabel();

        return $this->render('admin/construction/index.twig', [
            'constructions' => $constructions]);
    }

    /**
     * Ajoute une construction.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $construction = new \App\Entity\Construction();

        $form = $this->createForm(ConstructionForm::class, $construction)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $construction = $form->getData();

            $entityManager->persist($construction);
            $entityManager->flush();

           $this->addFlash('success', 'La construction a été ajoutée.');

            return $this->redirectToRoute('construction.detail', ['construction' => $construction->getId()], [], 303);
        }

        return $this->render('admin/construction/add.twig', [
            'construction' => $construction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie une construction.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $construction = $request->get('construction');

        $form = $this->createForm(ConstructionForm::class, $construction)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $construction = $form->getData();

            $entityManager->persist($construction);
            $entityManager->flush();

           $this->addFlash('success', 'La construction a été modifié.');

            return $this->redirectToRoute('construction.detail', ['construction' => $construction->getId()], [], 303);
        }

        return $this->render('admin/construction/update.twig', [
            'construction' => $construction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une construction.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $construction = $request->get('construction');

        $form = $this->createForm(ConstructionDeleteForm::class, $construction)
            ->add('delete', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $construction = $form->getData();

            $entityManager->remove($construction);
            $entityManager->flush();

           $this->addFlash('success', 'La construction a été supprimée.');

            return $this->redirectToRoute('construction', [], 303);
        }

        return $this->render('admin/construction/delete.twig', [
            'construction' => $construction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une construction.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $construction = $request->get('construction');

        return $this->render('admin/construction/detail.twig', [
            'construction' => $construction]);
    }

    /**
     * Liste des territoires ayant cette construction.
     */
    public function personnagesAction(Request $request,  EntityManagerInterface $entityManager, Construction $construction)
    {
        return $this->render('admin/construction/territoires.twig', [
            'construction' => $construction,
        ]);
    }
}
