<?php

/**
 * LarpManager - A Live Action Role Playing Manager
 * Copyright (C) 2016 Kevin Polez.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Controller;

use App\Entity\Age;
use LarpManager\Form\AgeForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\AgeController.
 *
 * @author kevin
 */
class AgeController
{
    /**
     * Liste des ages.
     *
     * @return View $view
     */
    public function indexAction(Request $request, Application $app)
    {
        $ages = $app['orm.em']->getRepository('\\'.\App\Entity\Age::class)
            ->findAllOrderedByLabel();

        return $app['twig']->render('admin/age/index.twig', ['ages' => $ages]);
    }

    /**
     * Liste des perso ayant cet age.
     */
    public function persoAction(Request $request, Application $app, Age $age)
    {
        return $app['twig']->render('admin/age/perso.twig', ['age' => $age]);
    }

    /**
     * Detail d'un age.
     *
     * @return View $view
     *
     * @throws LarpManager\Exception\ObjectNotFoundException
     */
    public function detailAction(Request $request, Application $app, Age $age)
    {
        return $app['twig']->render('admin/age/detail.twig', ['age' => $age]);
    }

    /**
     * Affiche le formulaire d'ajout d'un age.
     *
     * @return View $view
     */
    public function addViewAction(Request $request, Application $app)
    {
        $form = $app['form.factory']->createBuilder(new AgeForm(), new Age())
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        return $app['twig']->render('admin/age/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajout d'un age.
     *
     * @return View $view
     *
     * @throws LarpManager\Exception\RequestInvalid
     */
    public function addPostAction(Request $request, Application $app)
    {
        $form = $app['form.factory']->createBuilder(new AgeForm(), new Age())
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        /*
         * Si la requête est invalide, renvoyer vers une page d'erreur
         */
        if (!$form->isValid()) {
            throw new LarpManager\Exception\RequestInvalidException();
        }

        $age = $form->getData();

        $app['orm.em']->persist($age);
        $app['orm.em']->flush();

        $app['session']->getFlashBag()->add('success', 'L\'age a été ajouté.');

        /*
         * Si l'utilisateur a cliquer sur "Sauvegarder", on le redirige vers la liste des age
         */
        if ($form->get('save')->isClicked()) {
            return $app->redirect($app['url_generator']->generate('age'), 303);
        }

        // renvoi vers le formulaire d'ajout d'un age
        return $app->redirect($app['url_generator']->generate('age.add.view'), 303);
    }

    /**
     * Afiche le formulaire de mise à jour d'un age.
     *
     * @return View $view
     *
     * @throws LarpManager\Exception\ObjectNotFoundException
     */
    public function updateViewAction(Request $request, Application $app, Age $age)
    {
        $form = $app['form.factory']->createBuilder(new AgeForm(), $age)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'button', [
                'label' => 'Supprimer',
                'attr' => [
                    'value' => 'Submit',
                    'data-toggle' => 'modal',
                    'data-target' => '#confirm-submit',
                    'class' => 'btn btn-default',
                ],
            ])
            ->getForm();

        return $app['twig']->render('admin/age/update.twig', [
            'age' => $age,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un age.
     *
     * @return View $view
     *
     * @throws LarpManager\Exception\RequestInvalid
     * @throws LarpManager\Exception\ObjectNotFoundException
     */
    public function updatePostAction(Request $request, Application $app, Age $age)
    {
        $form = $app['form.factory']->createBuilder(new AgeForm(), $age)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'button', [
                'label' => 'Supprimer',
                'attr' => [
                    'value' => 'Submit',
                    'data-toggle' => 'modal',
                    'data-target' => '#confirm-submit',
                    'class' => 'btn btn-default',
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        /*
         * Si la requête est invalide, renvoyer vers une page d'erreur
         */
        if (!$form->isValid()) {
            throw new LarpManager\Exception\RequestInvalid();
        }

        $age = $form->getData();

        /*
         * Si l'utilisateur a cliqué sur "Sauvegarder", l'age sera sauvegarder dans la base de données
         * Sinon si l'utilisateur a cliqué sur "Supprimer", l'age sera supprimer dans la base de données.
         */
        if ($form->get('update')->isClicked()) {
            $app['orm.em']->persist($age);
            $app['orm.em']->flush();
            $app['session']->getFlashBag()->add('success', 'L\'age a été mis à jour.');
        } else {
            $app['orm.em']->remove($age);
            $app['orm.em']->flush();
            $app['session']->getFlashBag()->add('success', 'L\'age a été supprimé.');
        }

        return $app->redirect($app['url_generator']->generate('age'));
    }
}
