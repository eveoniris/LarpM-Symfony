<?php

namespace App\Controller;

use LarpManager\Form\RegionForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class RegionController extends AbstractController
{
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\App\Entity\Region');
        $regions = $repo->findAll();

        return $app['twig']->render('region/index.twig', ['regions' => $regions]);
    }

    public function addAction(Request $request, Application $app)
    {
        $region = new \App\Entity\Region();

        $form = $app['form.factory']->createBuilder(new RegionForm(), $region)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $region = $form->getData();
            $region->setCreator($this->getUser());

            $app['orm.em']->persist($region);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La région a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('region', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('region.add', [], 303);
            }
        }

        return $app['twig']->render('region/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $region = $app['orm.em']->find('\App\Entity\Region', $id);

        $form = $app['form.factory']->createBuilder(new RegionForm(), $region)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $region = $form->getData();

            if ($form->get('update')->isClicked()) {
                $region->setUpdateDate(new \DateTime('NOW'));
                $app['orm.em']->persist($region);
                $app['orm.em']->flush();
               $this->addFlash('success', 'La région a été mise à jour.');

                return $this->redirectToRoute('region.detail', ['index' => $id]);
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($region);
                $app['orm.em']->flush();
               $this->addFlash('success', 'La région a été supprimée.');

                return $this->redirectToRoute('region');
            }
        }

        return $app['twig']->render('region/update.twig', [
            'region' => $region,
            'form' => $form->createView(),
        ]);
    }

    public function detailAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $region = $app['orm.em']->find('\App\Entity\Region', $id);

        if ($region) {
            return $app['twig']->render('region/detail.twig', ['region' => $region]);
        } else {
           $this->addFlash('error', 'La région n\'a pas été trouvée.');

            return $this->redirectToRoute('region');
        }
    }

    public function detailExportAction(Request $request, Application $app): void
    {
        $id = $request->get('index');
    }

    public function exportAction(Request $request, Application $app): void
    {
    }
}
