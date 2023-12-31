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

use LarpManager\Form\Type\ProprietaireType;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\StockProprietaireController.
 *
 * @author kevin
 */
class StockProprietaireController extends AbstractController
{
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Proprietaire::class);
        $proprietaires = $repo->findAll();

        return $app['twig']->render('stock/proprietaire/index.twig', ['proprietaires' => $proprietaires]);
    }

    public function addAction(Request $request, Application $app)
    {
        $proprietaire = new \App\Entity\Proprietaire();

        $form = $app['form.factory']->createBuilder(new ProprietaireType(), $proprietaire)
            ->add('save', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $proprietaire = $form->getData();
            $app['orm.em']->persist($proprietaire);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le propriétaire a été ajouté');

            return $this->redirectToRoute('stock_proprietaire_index');
        }

        return $app['twig']->render('stock/proprietaire/add.twig', ['form' => $form->createView()]);
    }

    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Proprietaire::class);
        $proprietaire = $repo->find($id);

        $form = $app['form.factory']->createBuilder(new ProprietaireType(), $proprietaire)
            ->add('update', 'submit')
            ->add('delete', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // on récupére les data de l'utilisateur
            $proprietaire = $form->getData();

            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($proprietaire);
                $app['orm.em']->flush();
               $this->addFlash('success', 'Le propriétaire a été mis à jour');
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($proprietaire);
                $app['orm.em']->flush();
               $this->addFlash('success', 'Le proprietaire a été supprimé');
            }

            return $this->redirectToRoute('stock_proprietaire_index');
        }

        return $app['twig']->render('stock/proprietaire/update.twig', ['proprietaire' => $proprietaire, 'form' => $form->createView()]);
    }
}
