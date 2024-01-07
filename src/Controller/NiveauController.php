<?php

namespace App\Controller;

use App\Form\NiveauForm;
use Symfony\Component\HttpFoundation\Request;

class NiveauController extends AbstractController
{
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\App\Entity\Niveau');
        $niveaux = $repo->findAll();

        return $this->render('niveau/index.twig', ['niveaux' => $niveaux]);
    }

    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $niveau = new \App\Entity\Niveau();

        $form = $this->createForm(NiveauForm::class, $niveau)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $niveau = $form->getData();

            $entityManager->persist($niveau);
            $entityManager->flush();

           $this->addFlash('success', 'Le niveau a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('niveau', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('niveau.add', [], 303);
            }
        }

        return $this->render('niveau/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $niveau = $entityManager->find('\App\Entity\Niveau', $id);

        $form = $this->createForm(NiveauForm::class, $niveau)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $niveau = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($niveau);
                $entityManager->flush();
               $this->addFlash('success', 'Le niveau a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($niveau);
                $entityManager->flush();
               $this->addFlash('success', 'Le niveau a été supprimé.');
            }

            return $this->redirectToRoute('niveau');
        }

        return $this->render('niveau/update.twig', [
            'niveau' => $niveau,
            'form' => $form->createView(),
        ]);
    }

    public function detailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $niveau = $entityManager->find('\App\Entity\Niveau', $id);

        if ($niveau) {
            return $this->render('niveau/detail.twig', ['niveau' => $niveau]);
        } else {
           $this->addFlash('error', 'La niveau n\'a pas été trouvé.');

            return $this->redirectToRoute('niveau');
        }
    }

    public function detailExportAction(Request $request,  EntityManagerInterface $entityManager): void
    {
    }

    public function exportAction(Request $request,  EntityManagerInterface $entityManager): void
    {
    }
}
