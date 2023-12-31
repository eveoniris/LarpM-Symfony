<?php

namespace App\Controller;

use LarpManager\Form\PaysForm;
use LarpManager\Form\PaysMinimalForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class PaysController extends AbstractController
{
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\App\Entity\Pays');
        $pays = $repo->findAll();

        return $app['twig']->render('pays/index.twig', ['listPays' => $pays]);
    }

    public function addAction(Request $request, Application $app)
    {
        $pays = new \App\Entity\Pays();

        $form = $app['form.factory']->createBuilder(new PaysMinimalForm(), $pays)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $pays = $form->getData();
            $pays->setCreator($this->getUser());

            $app['orm.em']->persist($pays);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le pays a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('pays', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('pays.add', [], 303);
            }
        }

        return $app['twig']->render('pays/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $pays = $app['orm.em']->find('\App\Entity\Pays', $id);

        $form = $app['form.factory']->createBuilder(new PaysForm(), $pays)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $pays = $form->getData();

            if ($form->get('update')->isClicked()) {
                $pays->setUpdateDate(new \DateTime('NOW'));
                $app['orm.em']->persist($pays);
                $app['orm.em']->flush();
               $this->addFlash('success', 'Le pays a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($pays);
                $app['orm.em']->flush();
               $this->addFlash('success', 'Le pays a été supprimé.');
            }

            return $this->redirectToRoute('pays');
        }

        return $app['twig']->render('pays/update.twig', [
            'pays' => $pays,
            'form' => $form->createView(),
        ]);
    }

    public function detailAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $pays = $app['orm.em']->find('\App\Entity\Pays', $id);

        if ($pays) {
            return $app['twig']->render('pays/detail.twig', ['pays' => $pays]);
        } else {
           $this->addFlash('error', 'Le pays n\'a pas été trouvé.');

            return $this->redirectToRoute('pays');
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
