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

use App\Entity\Monnaie;
use LarpManager\Form\Monnaie\MonnaieDeleteForm;
use LarpManager\Form\Monnaie\MonnaieForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\MonnaieController.
 *
 * @author kevin
 */
class MonnaieController extends AbstractController
{
    /**
     * Liste les monnaies.
     */
    public function listAction(Application $app, Request $request)
    {
        $monnaies = $app['orm.em']->getRepository('\\'.\App\Entity\Monnaie::class)->findAll();

        return $app['twig']->render('admin/monnaie/list.twig', [
            'monnaies' => $monnaies,
        ]);
    }

    /**
     * Ajoute une monnaie.
     */
    public function addAction(Application $app, Request $request)
    {
        $form = $app['form.factory']->createBuilder(new MonnaieForm(), new Monnaie())
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $monnaie = $form->getData();
            $app['orm.em']->persist($monnaie);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La monnaie a été enregistrée.');

            return $this->redirectToRoute('monnaie', [], 303);
        }

        return $app['twig']->render('admin/monnaie/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une monnaie.
     */
    public function updateAction(Application $app, Request $request, Monnaie $monnaie)
    {
        $form = $app['form.factory']->createBuilder(new MonnaieForm(), $monnaie)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $monnaie = $form->getData();
            $app['orm.em']->persist($monnaie);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La monnaie a été enregistrée.');

            return $this->redirectToRoute('monnaie', [], 303);
        }

        return $app['twig']->render('admin/monnaie/update.twig', [
            'monnaie' => $monnaie,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une monnaie.
     */
    public function deleteAction(Application $app, Request $request, Monnaie $monnaie)
    {
        $form = $app['form.factory']->createBuilder(new MonnaieDeleteForm(), $monnaie)
            ->add('submit', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $monnaie = $form->getData();
            $app['orm.em']->remove($monnaie);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La monnaie a été supprimée.');

            return $this->redirectToRoute('monnaie', [], 303);
        }

        return $app['twig']->render('admin/monnaie/delete.twig', [
            'monnaie' => $monnaie,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Fourni le détail d'une monnaie.
     */
    public function detailAction(Application $app, Request $request, Monnaie $monnaie)
    {
        return $app['twig']->render('admin/monnaie/detail.twig', [
            'monnaie' => $monnaie,
        ]);
    }
}
