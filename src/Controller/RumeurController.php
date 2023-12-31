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

use App\Entity\Rumeur;
use JasonGrimes\Paginator;
use LarpManager\Form\Rumeur\RumeurDeleteForm;
use LarpManager\Form\Rumeur\RumeurFindForm;
use LarpManager\Form\Rumeur\RumeurForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\RumeurController.
 *
 * @author kevin
 */
class RumeurController extends AbstractController
{
    /**
     * Liste de toutes les rumeurs.
     */
    public function listAction(Request $request, Application $app)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $type = null;
        $value = null;

        $form = $app['form.factory']->createBuilder(new RumeurFindForm())->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['search'];
        }

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Rumeur::class);

        $rumeurs = $repo->findList(
            $type,
            $value,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset);

        $numResults = $repo->findCount($type, $value);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('rumeur.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $app['twig']->render('admin/rumeur/list.twig', [
            'form' => $form->createView(),
            'rumeurs' => $rumeurs,
            'paginator' => $paginator,
        ]);
    }

    /**
     * Lire une rumeur.
     */
    public function detailAction(Request $request, Application $app, Rumeur $rumeur)
    {
        return $app['twig']->render('admin/rumeur/detail.twig', [
            'rumeur' => $rumeur,
        ]);
    }

    /**
     * Ajouter une rumeur.
     */
    public function addAction(Request $request, Application $app)
    {
        $rumeur = new Rumeur();
        $form = $app['form.factory']->createBuilder(new RumeurForm(), $rumeur)
            ->add('add', 'submit', ['label' => 'Ajouter la rumeur'])->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $rumeur = $form->getData();
            $rumeur->setUser($this->getUser());

            $app['orm.em']->persist($rumeur);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Votre rumeur a été ajoutée.');

            return $this->redirectToRoute('rumeur.list', [], 303);
        }

        return $app['twig']->render('admin/rumeur/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mettre à jour une rumeur.
     */
    public function updateAction(Request $request, Application $app, Rumeur $rumeur)
    {
        $form = $app['form.factory']->createBuilder(new RumeurForm(), $rumeur)
            ->add('enregistrer', 'submit', ['label' => 'Enregistrer'])->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $rumeur = $form->getData();
            $rumeur->setUpdateDate(new \DateTime('NOW'));

            $app['orm.em']->persist($rumeur);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Votre rumeur a été modifiée.');

            return $this->redirectToRoute('rumeur.detail', ['rumeur' => $rumeur->getId()], [], 303);
        }

        return $app['twig']->render('admin/rumeur/update.twig', [
            'form' => $form->createView(),
            'rumeur' => $rumeur,
        ]);
    }

    /**
     * Supression d'une rumeur.
     */
    public function deleteAction(Request $request, Application $app, Rumeur $rumeur)
    {
        $form = $app['form.factory']->createBuilder(new RumeurDeleteForm(), $rumeur)
            ->add('supprimer', 'submit', ['label' => 'Supprimer'])->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $rumeur = $form->getData();
            $app['orm.em']->remove($rumeur);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La rumeur a été supprimée.');

            return $this->redirectToRoute('rumeur.list', [], 303);
        }

        return $app['twig']->render('admin/rumeur/delete.twig', [
            'form' => $form->createView(),
            'rumeur' => $rumeur,
        ]);
    }
}
