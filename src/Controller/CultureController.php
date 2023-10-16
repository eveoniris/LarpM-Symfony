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

use App\Entity\Culture;
use LarpManager\Form\Culture\CultureDeleteForm;
use LarpManager\Form\Culture\CultureForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class CultureController
{
    /**
     * Liste des culture.
     */
    public function indexAction(Request $request, Application $app)
    {
        $cultures = $app['orm.em']->getRepository(\App\Entity\Culture::class)->findAll();

        return $app['twig']->render('admin\culture\index.twig', [
            'cultures' => $cultures,
        ]);
    }

    /**
     * Ajout d'une culture.
     */
    public function addAction(Request $request, Application $app)
    {
        $form = $app['form.factory']->createBuilder(new CultureForm(), new Culture())->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $culture = $form->getData();
            $app['orm.em']->persist($culture);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La culture a été ajoutée.');

            return $app->redirect($app['url_generator']->generate('culture'), 303);
        }

        return $app['twig']->render('admin\culture\add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une culture.
     */
    public function detailAction(Request $request, Application $app, Culture $culture)
    {
        return $app['twig']->render('admin\culture\detail.twig', [
            'culture' => $culture,
        ]);
    }

    /**
     * Mise à jour d'une culture.
     */
    public function updateAction(Request $request, Application $app, Culture $culture)
    {
        $form = $app['form.factory']->createBuilder(new CultureForm(), $culture)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $culture = $form->getData();
            $app['orm.em']->persist($culture);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La culture a été mise à jour.');

            return $app->redirect($app['url_generator']->generate('culture'), 303);
        }

        return $app['twig']->render('admin\culture\update.twig', [
            'form' => $form->createView(),
            'culture' => $culture,
        ]);
    }

    /**
     * Suppression d'une culture.
     */
    public function deleteAction(Request $request, Application $app, Culture $culture)
    {
        $form = $app['form.factory']->createBuilder(new CultureDeleteForm(), $culture)
            ->add('submit', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $culture = $form->getData();
            $app['orm.em']->remove($culture);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La culture a été supprimée.');

            return $app->redirect($app['url_generator']->generate('culture'), 303);
        }

        return $app['twig']->render('admin\culture\delete.twig', [
            'form' => $form->createView(),
            'culture' => $culture,
        ]);
    }
}
