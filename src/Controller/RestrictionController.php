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

use App\Entity\Restriction;
use LarpManager\Form\RestrictionDeleteForm;
use LarpManager\Form\RestrictionForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\RestrictionsController.
 *
 * @author kevin
 */
class RestrictionController
{
    /**
     * Liste des restrictions.
     */
    public function listAction(Request $request, Application $app)
    {
        $restrictions = $app['orm.em']->getRepository('\\'.\App\Entity\Restriction::class)->findAllOrderedByLabel();

        return $app['twig']->render('admin/restriction/list.twig', ['restrictions' => $restrictions]);
    }

    /**
     * Imprimer la liste des restrictions.
     */
    public function printAction(Request $request, Application $app)
    {
        $restrictions = $app['orm.em']->getRepository('\\'.\App\Entity\Restriction::class)->findAllOrderedByLabel();

        return $app['twig']->render('admin/restriction/print.twig', ['restrictions' => $restrictions]);
    }

    /**
     * Télécharger la liste des restrictions alimentaires.
     */
    public function downloadAction(Request $request, Application $app): void
    {
        $restrictions = $app['orm.em']->getRepository('\\'.\App\Entity\Restriction::class)->findAllOrderedByLabel();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_restrictions_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv($output,
            [
                'nom',
                'nombre'], ';');

        foreach ($restrictions as $restriction) {
            $line = [];
            $line[] = mb_convert_encoding((string) $restriction->getLabel(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $restriction->getUsers()->count(), 'ISO-8859-1');
            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Ajouter une restriction alimentaire.
     */
    public function addAction(Request $request, Application $app)
    {
        $form = $app['form.factory']->createBuilder(new RestrictionForm(), new Restriction())
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $restriction = $form->getData();
            $restriction->setAuteur($app['User']);

            $app['orm.em']->persist($restriction);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La restriction a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('restriction.list'), 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('restriction.add'), 303);
            }
        }

        return $app['twig']->render('admin/restriction/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une restriction alimentaire.
     */
    public function detailAction(Request $request, Application $app, Restriction $restriction)
    {
        return $app['twig']->render('admin/restriction/detail.twig', ['restriction' => $restriction]);
    }

    /**
     * Mise à jour d'un lieu.
     */
    public function updateAction(Request $request, Application $app, Restriction $restriction)
    {
        $form = $app['form.factory']->createBuilder(new RestrictionForm(), $restriction)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $restriction = $form->getData();
            $app['orm.em']->persist($restriction);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La restriction alimentaire a été modifié.');

            return $app->redirect($app['url_generator']->generate('restriction.list'), 303);
        }

        return $app['twig']->render('admin/restriction/update.twig', [
            'restriction' => $restriction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'une restriction alimentaire.
     */
    public function deleteAction(Request $request, Application $app, Restriction $restriction)
    {
        $form = $app['form.factory']->createBuilder(new RestrictionDeleteForm(), $restriction)
            ->add('save', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $restriction = $form->getData();

            $app['orm.em']->remove($restriction);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La restriction alimentaire a été supprimé.');

            return $app->redirect($app['url_generator']->generate('restriction.list'), 303);
        }

        return $app['twig']->render('admin/restriction/delete.twig', [
            'restriction' => $restriction,
            'form' => $form->createView(),
        ]);
    }
}
