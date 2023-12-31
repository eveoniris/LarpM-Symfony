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

use App\Entity\Construction;
use LarpManager\Form\ConstructionDeleteForm;
use LarpManager\Form\ConstructionForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\ConstructionController.
 *
 * @author kevin
 */
class ConstructionController extends AbstractController
{
    /**
     * Présentation des constructions.
     */
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Construction::class);
        $constructions = $repo->findAllOrderedByLabel();

        return $app['twig']->render('admin/construction/index.twig', [
            'constructions' => $constructions]);
    }

    /**
     * Ajoute une construction.
     */
    public function addAction(Request $request, Application $app)
    {
        $construction = new \App\Entity\Construction();

        $form = $app['form.factory']->createBuilder(new ConstructionForm(), $construction)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $construction = $form->getData();

            $app['orm.em']->persist($construction);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La construction a été ajoutée.');

            return $this->redirectToRoute('construction.detail', ['construction' => $construction->getId()], [], 303);
        }

        return $app['twig']->render('admin/construction/add.twig', [
            'construction' => $construction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie une construction.
     */
    public function updateAction(Request $request, Application $app)
    {
        $construction = $request->get('construction');

        $form = $app['form.factory']->createBuilder(new ConstructionForm(), $construction)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $construction = $form->getData();

            $app['orm.em']->persist($construction);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La construction a été modifié.');

            return $this->redirectToRoute('construction.detail', ['construction' => $construction->getId()], [], 303);
        }

        return $app['twig']->render('admin/construction/update.twig', [
            'construction' => $construction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une construction.
     */
    public function deleteAction(Request $request, Application $app)
    {
        $construction = $request->get('construction');

        $form = $app['form.factory']->createBuilder(new ConstructionDeleteForm(), $construction)
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $construction = $form->getData();

            $app['orm.em']->remove($construction);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La construction a été supprimée.');

            return $this->redirectToRoute('construction', [], 303);
        }

        return $app['twig']->render('admin/construction/delete.twig', [
            'construction' => $construction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une construction.
     */
    public function detailAction(Request $request, Application $app)
    {
        $construction = $request->get('construction');

        return $app['twig']->render('admin/construction/detail.twig', [
            'construction' => $construction]);
    }

    /**
     * Liste des territoires ayant cette construction.
     */
    public function personnagesAction(Request $request, Application $app, Construction $construction)
    {
        return $app['twig']->render('admin/construction/territoires.twig', [
            'construction' => $construction,
        ]);
    }
}
