<?php

namespace App\Controller;

use App\Form\RegionForm;
use Symfony\Component\HttpFoundation\Request;

class RegionController extends AbstractController
{
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\App\Entity\Region');
        $regions = $repo->findAll();

        return $this->render('region/index.twig', ['regions' => $regions]);
    }

    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $region = new \App\Entity\Region();

        $form = $this->createForm(RegionForm::class, $region)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $region = $form->getData();
            $region->setCreator($this->getUser());

            $entityManager->persist($region);
            $entityManager->flush();

           $this->addFlash('success', 'La région a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('region', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('region.add', [], 303);
            }
        }

        return $this->render('region/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $region = $entityManager->find('\App\Entity\Region', $id);

        $form = $this->createForm(RegionForm::class, $region)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $region = $form->getData();

            if ($form->get('update')->isClicked()) {
                $region->setUpdateDate(new \DateTime('NOW'));
                $entityManager->persist($region);
                $entityManager->flush();
               $this->addFlash('success', 'La région a été mise à jour.');

                return $this->redirectToRoute('region.detail', ['index' => $id]);
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($region);
                $entityManager->flush();
               $this->addFlash('success', 'La région a été supprimée.');

                return $this->redirectToRoute('region');
            }
        }

        return $this->render('region/update.twig', [
            'region' => $region,
            'form' => $form->createView(),
        ]);
    }

    public function detailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $region = $entityManager->find('\App\Entity\Region', $id);

        if ($region) {
            return $this->render('region/detail.twig', ['region' => $region]);
        } else {
           $this->addFlash('error', 'La région n\'a pas été trouvée.');

            return $this->redirectToRoute('region');
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
