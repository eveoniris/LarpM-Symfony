<?php


namespace App\Controller;

use App\Entity\Culture;
use App\Form\Culture\CultureDeleteForm;
use App\Form\Culture\CultureForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_CARTOGRAPHE')]
class CultureController extends AbstractController
{
    /**
     * Liste des culture.
     */
    #[Route('/culture', name: 'culture.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $cultures = $entityManager->getRepository(\App\Entity\Culture::class)->findAll();

        return $this->render('admin\culture\index.twig', [
            'cultures' => $cultures,
        ]);
    }

    /**
     * Ajout d'une culture.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(CultureForm::class, new Culture())->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $culture = $form->getData();
            $entityManager->persist($culture);
            $entityManager->flush();

           $this->addFlash('success', 'La culture a été ajoutée.');

            return $this->redirectToRoute('culture', [], 303);
        }

        return $this->render('admin\culture\add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une culture.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Culture $culture)
    {
        return $this->render('admin\culture\detail.twig', [
            'culture' => $culture,
        ]);
    }

    /**
     * Mise à jour d'une culture.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Culture $culture)
    {
        $form = $this->createForm(CultureForm::class, $culture);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $culture = $form->getData();
            $entityManager->persist($culture);
            $entityManager->flush();

           $this->addFlash('success', 'La culture a été mise à jour.');

            return $this->redirectToRoute('culture', [], 303);
        }

        return $this->render('admin\culture\update.twig', [
            'form' => $form->createView(),
            'culture' => $culture,
        ]);
    }

    /**
     * Suppression d'une culture.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Culture $culture)
    {
        $form = $this->createForm(CultureDeleteForm::class, $culture)
            ->add('submit', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $culture = $form->getData();
            $entityManager->remove($culture);
            $entityManager->flush();

           $this->addFlash('success', 'La culture a été supprimée.');

            return $this->redirectToRoute('culture', [], 303);
        }

        return $this->render('admin\culture\delete.twig', [
            'form' => $form->createView(),
            'culture' => $culture,
        ]);
    }
}
