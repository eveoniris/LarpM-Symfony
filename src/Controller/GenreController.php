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

use LarpManager\Form\GenreForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\GenreController.
 *
 * @author kevin
 */
class GenreController
{
    /**
     * Présentation des genres.
     */
    public function indexAction(Request $request, Application $app)
    {
        $genres = $app['orm.em']->getRepository('\\'.\App\Entity\Genre::class)->findAll();

        return $app['twig']->render('admin/genre/index.twig', ['genres' => $genres]);
    }

    /**
     * Ajout d'un genre.
     */
    public function addAction(Request $request, Application $app)
    {
        $genre = new \App\Entity\Genre();

        $form = $app['form.factory']->createBuilder(new GenreForm(), $genre)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $genre = $form->getData();

            $app['orm.em']->persist($genre);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le genre a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('genre'), 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('genre.add'), 303);
            }
        }

        return $app['twig']->render('admin/genre/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'un genre.
     */
    public function detailAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $genre = $app['orm.em']->find('\\'.\App\Entity\Genre::class, $id);

        if ($genre) {
            return $app['twig']->render('admin/genre/detail.twig', ['genre' => $genre]);
        } else {
            $app['session']->getFlashBag()->add('error', 'Le genre n\'a pas été trouvé.');

            return $app->redirect($app['url_generator']->generate('genre'));
        }
    }

    /**
     * Met à jour un genre.
     */
    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $genre = $app['orm.em']->find('\\'.\App\Entity\Genre::class, $id);

        $form = $app['form.factory']->createBuilder(new GenreForm(), $genre)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $genre = $form->getData();

            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($genre);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'Le genre a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($genre);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'Le genre a été supprimé.');
            }

            return $app->redirect($app['url_generator']->generate('genre'));
        }

        return $app['twig']->render('admin/genre/update.twig', [
            'genre' => $genre,
            'form' => $form->createView(),
        ]);
    }
}
