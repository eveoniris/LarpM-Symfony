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

use LarpManager\Form\CompetenceFamilyForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\CompetenceFamilyController.
 *
 * @author kevin
 */
class CompetenceFamilyController
{
    /**
     * Liste les famille de competence.
     */
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\CompetenceFamily::class);
        $competenceFamilies = $repo->findAllOrderedByLabel();

        return $app['twig']->render('admin/competenceFamily/index.twig', ['competenceFamilies' => $competenceFamilies]);
    }

    /**
     * Ajoute une famille de competence.
     */
    public function addAction(Request $request, Application $app)
    {
        $competenceFamily = new \App\Entity\CompetenceFamily();

        $form = $app['form.factory']->createBuilder(new CompetenceFamilyForm(), $competenceFamily)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $competenceFamily = $form->getData();

            $app['orm.em']->persist($competenceFamily);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La famille de compétence a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('competence.family'), 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('competence.family.add'), 303);
            }
        }

        return $app['twig']->render('admin/competenceFamily/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une famille de compétence.
     */
    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $competenceFamily = $app['orm.em']->find('\\'.\App\Entity\CompetenceFamily::class, $id);

        $form = $app['form.factory']->createBuilder(new CompetenceFamilyForm(), $competenceFamily)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $competenceFamily = $form->getData();

            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($competenceFamily);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'La famille de compétence a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($competenceFamily);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'La famille de compétence a été supprimé.');
            }

            return $app->redirect($app['url_generator']->generate('competence.family'));
        }

        return $app['twig']->render('admin/competenceFamily/update.twig', [
            'competenceFamily' => $competenceFamily,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'une famille de compétence.
     */
    public function detailAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $competenceFamily = $app['orm.em']->find('\\'.\App\Entity\CompetenceFamily::class, $id);

        if ($competenceFamily) {
            return $app['twig']->render('admin/competenceFamily/detail.twig', ['competenceFamily' => $competenceFamily]);
        } else {
            $app['session']->getFlashBag()->add('error', 'La famille de compétence n\'a pas été trouvé.');

            return $app->redirect($app['url_generator']->generate('competence.family'));
        }
    }
}
