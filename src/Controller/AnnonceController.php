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

use App\Entity\Annonce;
use JasonGrimes\Paginator;
use LarpManager\Form\AnnonceDeleteForm;
use LarpManager\Form\AnnonceForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\AnnonceController.
 *
 * @author kevin
 */
class AnnonceController extends AbstractController
{
    /**
     * Présentation des annonces.
     */
    public function listAction(Request $request, Application $app)
    {
        $order_by = $request->get('order_by') ?: 'creation_date';
        $order_dir = 'DESC' === $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;

        $criteria = [];

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Annonce::class);
        $annonces = $repo->findBy(
            $criteria,
            [$order_by => $order_dir],
            $limit,
            $offset);

        $numResults = $repo->findCount($criteria);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('annonce.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $app['twig']->render('admin/annonce/list.twig', [
            'annonces' => $annonces,
            'paginator' => $paginator,
        ]);
    }

    /**
     * Ajout d'une annonce.
     */
    public function addAction(Request $request, Application $app)
    {
        $form = $app['form.factory']->createBuilder(new AnnonceForm(), new Annonce())
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $annonce = $form->getData();

            $app['orm.em']->persist($annonce);
            $app['orm.em']->flush();

           $this->addFlash('success', 'L\'annonce a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('annonce.list', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('annonce.add', [], 303);
            }
        }

        return $app['twig']->render('admin/annonce/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour d'une annnonce.
     */
    public function updateAction(Request $request, Application $app, Annonce $annonce)
    {
        $form = $app['form.factory']->createBuilder(new AnnonceForm(), $annonce)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $annonce = $form->getData();

            $annonce->setUpdateDate(new \DateTime('NOW'));

            $app['orm.em']->persist($annonce);
            $app['orm.em']->flush();
           $this->addFlash('success', 'L\'annonce a été mise à jour.');

            return $this->redirectToRoute('annonce.list');
        }

        return $app['twig']->render('admin/annonce/update.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une annonce.
     */
    public function detailAction(Request $request, Application $app, Annonce $annonce)
    {
        return $app['twig']->render('admin/annonce/detail.twig', ['annonce' => $annonce]);
    }

    /**
     * Suppression d'une annonce.
     */
    public function deleteAction(Request $request, Application $app, Annonce $annonce)
    {
        $form = $app['form.factory']->createBuilder(new AnnonceDeleteForm(), $annonce)
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $annonce = $form->getData();

            $app['orm.em']->remove($annonce);
            $app['orm.em']->flush();

           $this->addFlash('success', 'L\'annonce a été supprimée.');

            return $this->redirectToRoute('annonce.list');
        }

        return $app['twig']->render('admin/annonce/delete.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }
}
