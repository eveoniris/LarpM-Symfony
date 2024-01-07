<?php

namespace App\Controller;

use App\Form\PaysForm;
use App\Form\PaysMinimalForm;
use Symfony\Component\HttpFoundation\Request;

class PaysController extends AbstractController
{
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\App\Entity\Pays');
        $pays = $repo->findAll();

        return $this->render('pays/index.twig', ['listPays' => $pays]);
    }

    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $pays = new \App\Entity\Pays();

        $form = $this->createForm(PaysMinimalForm::class, $pays)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pays = $form->getData();
            $pays->setCreator($this->getUser());

            $entityManager->persist($pays);
            $entityManager->flush();

           $this->addFlash('success', 'Le pays a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('pays', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('pays.add', [], 303);
            }
        }

        return $this->render('pays/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $pays = $entityManager->find('\App\Entity\Pays', $id);

        $form = $this->createForm(PaysForm::class, $pays)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pays = $form->getData();

            if ($form->get('update')->isClicked()) {
                $pays->setUpdateDate(new \DateTime('NOW'));
                $entityManager->persist($pays);
                $entityManager->flush();
               $this->addFlash('success', 'Le pays a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($pays);
                $entityManager->flush();
               $this->addFlash('success', 'Le pays a été supprimé.');
            }

            return $this->redirectToRoute('pays');
        }

        return $this->render('pays/update.twig', [
            'pays' => $pays,
            'form' => $form->createView(),
        ]);
    }

    public function detailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $pays = $entityManager->find('\App\Entity\Pays', $id);

        if ($pays) {
            return $this->render('pays/detail.twig', ['pays' => $pays]);
        } else {
           $this->addFlash('error', 'Le pays n\'a pas été trouvé.');

            return $this->redirectToRoute('pays');
        }
    }

    public function detailExportAction(Request $request,  EntityManagerInterface $entityManager): void
    {
        $id = $request->get('index');
    }

    public function exportAction(Request $request,  EntityManagerInterface $entityManager): void
    {
    }
}
