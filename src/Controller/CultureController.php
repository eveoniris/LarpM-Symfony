<?php


namespace App\Controller;

use App\Entity\Culture;
use App\Form\Culture\CultureDeleteForm;
use App\Form\Culture\CultureForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
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

        return $this->render('culture\index.twig', [
            'cultures' => $cultures,
        ]);
    }

    /**
     * Ajout d'une culture.
     */
    #[Route('/culture/add', name: 'culture.add')]
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(CultureForm::class, new Culture());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $culture = $form->getData();
            $entityManager->persist($culture);
            $entityManager->flush();

           $this->addFlash('success', 'La culture a été ajoutée.');

            return $this->redirectToRoute('culture.index', [], 303);
        }

        return $this->render('culture\add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une culture.
     */
    #[Route('/culture/{culture}/detail', name: 'culture.detail')]
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, #[MapEntity] Culture $culture)
    {
        return $this->render('culture\detail.twig', [
            'culture' => $culture,
        ]);
    }

    /**
     * Mise à jour d'une culture.
     */
    #[Route('/culture/{culture}/update', name: 'culture.update')]
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, #[MapEntity] Culture $culture)
    {
        $form = $this->createForm(CultureForm::class, $culture);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $culture = $form->getData();
            $entityManager->persist($culture);
            $entityManager->flush();

           $this->addFlash('success', 'La culture a été mise à jour.');

            return $this->redirectToRoute('culture.index', [], 303);
        }

        return $this->render('culture\update.twig', [
            'form' => $form->createView(),
            'culture' => $culture,
        ]);
    }

    /**
     * Suppression d'une culture.
     */
    #[Route('/culture/{culture}/delete', name: 'culture.delete')]
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, #[MapEntity] Culture $culture)
    {
        $form = $this->createForm(CultureDeleteForm::class, $culture)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $culture = $form->getData();
            $entityManager->remove($culture);
            $entityManager->flush();

           $this->addFlash('success', 'La culture a été supprimée.');

            return $this->redirectToRoute('culture.index', [], 303);
        }

        return $this->render('culture\delete.twig', [
            'form' => $form->createView(),
            'culture' => $culture,
        ]);
    }
}
