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

use Doctrine\ORM\Query;
use LarpManager\Form\RessourceForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\RessourceController.
 *
 * @author kevin
 */
class RessourceController extends AbstractController
{
    /**
     * API: fourni la liste des ressources
     * GET /api/ressource.
     */
    public function apiListAction(Request $request, Application $app): JsonResponse
    {
        $qb = $app['orm.em']->createQueryBuilder();
        $qb->select('Ressource')
            ->from('\\'.\App\Entity\Ressource::class, 'Ressource');

        $query = $qb->getQuery();

        $ressources = $query->getResult(Query::HYDRATE_ARRAY);

        return new JsonResponse($ressources);
    }

    /**
     * Liste des ressources.
     */
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Ressource::class);
        $ressources = $repo->findAll();

        return $app['twig']->render('ressource/index.twig', ['ressources' => $ressources]);
    }

    /**
     * Ajout d'une ressource.
     */
    public function addAction(Request $request, Application $app)
    {
        $ressource = new \App\Entity\Ressource();

        $form = $app['form.factory']->createBuilder(new RessourceForm(), $ressource)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $ressource = $form->getData();

            $app['orm.em']->persist($ressource);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La ressource a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('ressource', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('ressource.add', [], 303);
            }
        }

        return $app['twig']->render('ressource/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour d'une ressource.
     */
    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $ressource = $app['orm.em']->find('\\'.\App\Entity\Ressource::class, $id);

        $form = $app['form.factory']->createBuilder(new RessourceForm(), $ressource)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $ressource = $form->getData();

            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($ressource);
                $app['orm.em']->flush();
               $this->addFlash('success', 'La ressource a été mise à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($ressource);
                $app['orm.em']->flush();
               $this->addFlash('success', 'La ressource a été supprimée.');
            }

            return $this->redirectToRoute('ressource');
        }

        return $app['twig']->render('ressource/update.twig', [
            'ressource' => $ressource,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche de détail d'une ressource.
     */
    public function detailAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $ressource = $app['orm.em']->find('\\'.\App\Entity\Ressource::class, $id);

        if ($ressource) {
            return $app['twig']->render('ressource/detail.twig', ['ressource' => $ressource]);
        } else {
           $this->addFlash('error', 'La ressource n\'a pas été trouvée.');

            return $this->redirectToRoute('ressource');
        }
    }

    public function detailExportAction(Request $request, Application $app): void
    {
    }

    public function exportAction(Request $request, Application $app): void
    {
    }
}
