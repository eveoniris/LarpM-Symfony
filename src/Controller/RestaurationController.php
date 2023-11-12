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

use App\Entity\Restauration;
use LarpManager\Form\RestaurationDeleteForm;
use LarpManager\Form\RestaurationForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LarpManager\Controllers\RestaurationsController.
 *
 * @author kevin
 */
class RestaurationController
{
    /**
     * Liste des restaurations.
     */
    #[Route('/restauration/list', name: 'restauration.list')]
    public function listAction(Request $request, Application $app)
    {
        $restaurations = $app['orm.em']->getRepository('\\'.\App\Entity\Restauration::class)->findAllOrderedByLabel();

        return $app['twig']->render('admin/restauration/list.twig', ['restaurations' => $restaurations]);
    }

    /**
     * Imprimer la liste des restaurations.
     */
    public function printAction(Request $request, Application $app)
    {
        $restaurations = $app['orm.em']->getRepository('\\'.\App\Entity\Restauration::class)->findAllOrderedByLabel();

        return $app['twig']->render('admin/restauration/print.twig', ['restaurations' => $restaurations]);
    }

    /**
     * Télécharger la liste des restaurations alimentaires.
     */
    public function downloadAction(Request $request, Application $app): void
    {
        $restaurations = $app['orm.em']->getRepository('\\'.\App\Entity\Restauration::class)->findAllOrderedByLabel();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_restaurations_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv($output,
            [
                'nom',
                'nombre'], ';');

        foreach ($restaurations as $restauration) {
            $line = [];
            $line[] = mb_convert_encoding((string) $restauration->getLabel(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $restauration->getUsers()->count(), 'ISO-8859-1');
            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Ajouter une restauration.
     */
    public function addAction(Request $request, Application $app)
    {
        $form = $app['form.factory']->createBuilder(new RestaurationForm(), new Restauration())
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $restauration = $form->getData();

            $app['orm.em']->persist($restauration);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La restauration a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('restauration.list'), 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('restauration.add'), 303);
            }
        }

        return $app['twig']->render('admin/restauration/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'un lieu de restauration.
     */
    public function detailAction(Request $request, Application $app, Restauration $restauration)
    {
        return $app['twig']->render('admin/restauration/detail.twig', ['restauration' => $restauration]);
    }

    /**
     * Liste des utilisateurs ayant ce lieu de restauration.
     */
    public function UsersAction(Request $request, Application $app, Restauration $restauration)
    {
        return $app['twig']->render('admin/restauration/Users.twig', ['restauration' => $restauration]);
    }

    /**
     * Liste des utilisateurs ayant ce lieu de restauration.
     */
    public function UsersExportAction(Request $request, Application $app, Restauration $restauration): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_restaurations_'.$restauration->getId().'_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv($output,
            [
                'nom',
                'prenom',
                'restriction'], ';');

        foreach ($restauration->getUserByGn() as $UserByGn) {
            foreach ($UserByGn['Users'] as $User) {
                $restriction = '';
                foreach ($User->getRestrictions() as $r) {
                    $restriction .= $r->getLabel().' - ';
                }

                $line = [];
                $line[] = mb_convert_encoding((string) $User->getEtatCivil()->getNom(), 'ISO-8859-1');
                $line[] = mb_convert_encoding((string) $User->getEtatCivil()->getPrenom(), 'ISO-8859-1');
                $line[] = mb_convert_encoding($restriction, 'ISO-8859-1');
                fputcsv($output, $line, ';');
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Liste des restrictions alimentaires.
     */
    public function restrictionsAction(Request $request, Application $app, Restauration $restauration)
    {
        return $app['twig']->render('admin/restauration/restrictions.twig', [
            'restauration' => $restauration,
        ]);
    }

    /**
     * Mise à jour d'un lieu de restauration.
     */
    public function updateAction(Request $request, Application $app, Restauration $restauration)
    {
        $form = $app['form.factory']->createBuilder(new RestaurationForm(), $restauration)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $restauration = $form->getData();
            $app['orm.em']->persist($restauration);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La restauration alimentaire a été modifié.');

            return $app->redirect($app['url_generator']->generate('restauration.list'), 303);
        }

        return $app['twig']->render('admin/restauration/update.twig', [
            'restauration' => $restauration,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un lieu de restauration.
     */
    public function deleteAction(Request $request, Application $app, Restauration $restauration)
    {
        $form = $app['form.factory']->createBuilder(new RestaurationDeleteForm(), $restauration)
            ->add('save', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $restauration = $form->getData();

            $app['orm.em']->remove($restauration);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le lieu de restauration a été supprimé.');

            return $app->redirect($app['url_generator']->generate('restauration.list'), 303);
        }

        return $app['twig']->render('admin/restauration/delete.twig', [
            'restauration' => $restauration,
            'form' => $form->createView(),
        ]);
    }
}
