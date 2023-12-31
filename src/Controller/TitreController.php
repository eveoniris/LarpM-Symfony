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

use LarpManager\Form\TitreDeleteForm;
use LarpManager\Form\TitreForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\TitreController.
 *
 * @author kevin
 */
class TitreController extends AbstractController
{
    /**
     * Liste des titres.
     */
    public function adminListAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Titre::class);
        $titres = $repo->findAll();

        return $app['twig']->render('admin/titre/list.twig', ['titres' => $titres]);
    }

    /**
     * Detail d'un titre.
     */
    public function adminDetailAction(Request $request, Application $app)
    {
        $titre = $request->get('titre');

        return $app['twig']->render('admin/titre/detail.twig', [
            'titre' => $titre,
        ]);
    }

    /**
     * Ajoute un titre.
     */
    public function adminAddAction(Request $request, Application $app)
    {
        $titre = new \App\Entity\Titre();

        $form = $app['form.factory']->createBuilder(new TitreForm(), $titre)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $titre = $form->getData();

            $app['orm.em']->persist($titre);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le titre a été ajouté');

            return $this->redirectToRoute('titre.admin.detail', ['titre' => $titre->getId()], [], 303);
        }

        return $app['twig']->render('admin/titre/add.twig', [
            'titre' => $titre,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un titre.
     */
    public function adminUpdateAction(Request $request, Application $app)
    {
        $titre = $request->get('titre');

        $form = $app['form.factory']->createBuilder(new TitreForm(), $titre)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $titre = $form->getData();

            $app['orm.em']->persist($titre);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le titre a été sauvegardé');

            return $this->redirectToRoute('titre.admin.detail', ['titre' => $titre->getId()], [], 303);
        }

        return $app['twig']->render('admin/titre/update.twig', [
            'titre' => $titre,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un titre.
     */
    public function adminDeleteAction(Request $request, Application $app)
    {
        $titre = $request->get('titre');

        $form = $app['form.factory']->createBuilder(new TitreDeleteForm(), $titre)
            ->add('save', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $titre = $form->getData();

            $app['orm.em']->remove($titre);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le titre a été suprimé');

            return $this->redirectToRoute('titre.admin.list', [], 303);
        }

        return $app['twig']->render('admin/titre/delete.twig', [
            'titre' => $titre,
            'form' => $form->createView(),
        ]);
    }
}
