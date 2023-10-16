<?php

namespace App\Controller;

use LarpManager\Form\NiveauForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class NiveauController
{
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\App\Entity\Niveau');
        $niveaux = $repo->findAll();

        return $app['twig']->render('niveau/index.twig', ['niveaux' => $niveaux]);
    }

    public function addAction(Request $request, Application $app)
    {
        $niveau = new \App\Entity\Niveau();

        $form = $app['form.factory']->createBuilder(new NiveauForm(), $niveau)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $niveau = $form->getData();

            $app['orm.em']->persist($niveau);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le niveau a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('niveau'), 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('niveau.add'), 303);
            }
        }

        return $app['twig']->render('niveau/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $niveau = $app['orm.em']->find('\App\Entity\Niveau', $id);

        $form = $app['form.factory']->createBuilder(new NiveauForm(), $niveau)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $niveau = $form->getData();

            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($niveau);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'Le niveau a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($niveau);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'Le niveau a été supprimé.');
            }

            return $app->redirect($app['url_generator']->generate('niveau'));
        }

        return $app['twig']->render('niveau/update.twig', [
            'niveau' => $niveau,
            'form' => $form->createView(),
        ]);
    }

    public function detailAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $niveau = $app['orm.em']->find('\App\Entity\Niveau', $id);

        if ($niveau) {
            return $app['twig']->render('niveau/detail.twig', ['niveau' => $niveau]);
        } else {
            $app['session']->getFlashBag()->add('error', 'La niveau n\'a pas été trouvé.');

            return $app->redirect($app['url_generator']->generate('niveau'));
        }
    }

    public function detailExportAction(Request $request, Application $app): void
    {
    }

    public function exportAction(Request $request, Application $app): void
    {
    }
}
