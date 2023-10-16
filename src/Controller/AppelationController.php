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

use App\Entity\Appelation;
use LarpManager\Form\AppelationForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\AppelationController.
 *
 * @author kevin
 */
class AppelationController
{
    /**
     * affiche le tableau de bord de gestion des appelations.
     */
    public function indexAction(Request $request, Application $app)
    {
        $appelations = $app['orm.em']->getRepository('\\'.\App\Entity\Appelation::class)->findAll();
        $appelations = $app['larp.manager']->sortAppelation($appelations);

        return $app['twig']->render('admin/appelation/index.twig', ['appelations' => $appelations]);
    }

    /**
     * Detail d'une appelation.
     */
    public function detailAction(Request $request, Application $app, Appelation $appelation)
    {
        return $app['twig']->render('admin/appelation/detail.twig', ['appelation' => $appelation]);
    }

    /**
     * Ajoute une appelation.
     */
    public function addAction(Request $request, Application $app)
    {
        $form = $app['form.factory']->createBuilder(new AppelationForm(), new Appelation())
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $appelation = $form->getData();
            $app['orm.em']->persist($appelation);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'L\'appelation a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('appelation'), 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('appelation.add'), 303);
            }
        }

        return $app['twig']->render('admin/appelation/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie une appelation.
     */
    public function updateAction(Request $request, Application $app, Appelation $appelation)
    {
        $form = $app['form.factory']->createBuilder(new AppelationForm(), $appelation)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $appelation = $form->getData();

            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($appelation);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'L\'appelation a été mise à jour.');

                return $app->redirect($app['url_generator']->generate('appelation.detail', ['appelation' => $id]), 303);
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($appelation);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'L\'appelation a été supprimée.');

                return $app->redirect($app['url_generator']->generate('appelation'), 303);
            }
        }

        return $app['twig']->render('admin/appelation/update.twig', [
            'appelation' => $appelation,
            'form' => $form->createView(),
        ]);
    }
}
