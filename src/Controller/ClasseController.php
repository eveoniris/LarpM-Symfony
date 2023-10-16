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

use App\Entity\Classe;
use LarpManager\Form\Classe\ClasseForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\ClasseController.
 *
 * @author kevin
 */
class ClasseController
{
    /**
     * Présentation des classes.
     */
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Classe::class);
        $classes = $repo->findAllOrderedByLabel();

        return $app['twig']->render('classe/list.twig', ['classes' => $classes]);
    }

    /**
     * Ajout d'une classe.
     */
    public function addAction(Request $request, Application $app)
    {
        $classe = new \App\Entity\Classe();

        $form = $app['form.factory']->createBuilder(new ClasseForm(), $classe)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $classe = $form->getData();

            $app['orm.em']->persist($classe);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La classe a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('classe'), 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('classe.add'), 303);
            }
        }

        return $app['twig']->render('classe/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour d'une classe.
     */
    public function updateAction(Request $request, Application $app, Classe $classe)
    {
        $form = $app['form.factory']->createBuilder(new ClasseForm(), $classe)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $classe = $form->getData();

            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($classe);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'La classe a été mise à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($classe);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'La classe a été supprimée.');
            }

            return $app->redirect($app['url_generator']->generate('classe'));
        }

        return $app['twig']->render('classe/update.twig', [
            'classe' => $classe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une classe.
     */
    public function detailAction(Request $request, Application $app, Classe $classe)
    {
        return $app['twig']->render('admin/classe/detail.twig', ['classe' => $classe]);
    }
}
