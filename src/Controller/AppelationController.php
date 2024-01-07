<?php


namespace App\Controller;

use App\Entity\Appelation;
use App\Form\AppelationForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class AppelationController extends AbstractController
{
    /**
     * affiche le tableau de bord de gestion des appelations.
     */
    #[Route('/appelation', name: 'appelation.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $appelations = $entityManager->getRepository('\\'.\App\Entity\Appelation::class)->findAll();
        $appelations = $app['larp.manager']->sortAppelation($appelations);

        return $this->render('admin/appelation/index.twig', ['appelations' => $appelations]);
    }

    /**
     * Detail d'une appelation.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Appelation $appelation)
    {
        return $this->render('admin/appelation/detail.twig', ['appelation' => $appelation]);
    }

    /**
     * Ajoute une appelation.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(AppelationForm::class, new Appelation())
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appelation = $form->getData();
            $entityManager->persist($appelation);
            $entityManager->flush();

           $this->addFlash('success', 'L\'appelation a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('appelation', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('appelation.add', [], 303);
            }
        }

        return $this->render('admin/appelation/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie une appelation.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Appelation $appelation)
    {
        $form = $this->createForm(AppelationForm::class, $appelation)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appelation = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($appelation);
                $entityManager->flush();
               $this->addFlash('success', 'L\'appelation a été mise à jour.');

                return $this->redirectToRoute('appelation.detail', ['appelation' => $id], [], 303);
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($appelation);
                $entityManager->flush();
               $this->addFlash('success', 'L\'appelation a été supprimée.');

                return $this->redirectToRoute('appelation', [], 303);
            }
        }

        return $this->render('admin/appelation/update.twig', [
            'appelation' => $appelation,
            'form' => $form->createView(),
        ]);
    }
}
