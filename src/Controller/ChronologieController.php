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

use LarpManager\Form\ChronologieForm;
use LarpManager\Form\ChronologieRemoveForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\ChronologieController.
 */
class ChronologieController extends AbstractController
{
    /**
     * API : mettre à jour un événement
     * POST /api/chronologies/{event}.
     */
    public function apiUpdateAction(Request $request, Application $app): JsonResponse
    {
        $event = $request->get('event');

        $payload = json_decode($request->getContent());

        $territoire = $app['orm.em']->find('\\'.\App\Entity\Territoire::class, $payload->territoire_id);

        $event->setTerritoire($territoire);
        $event->JsonUnserialize($payload);

        $app['orm.em']->persist($event);
        $app['orm.em']->flush();

        return new JsonResponse($payload);
    }

    /**
     * API : supprimer un événement
     * DELETE /api/chronologies/{event}.
     */
    public function apiDeleteAction(Request $request, Application $app): JsonResponse
    {
        $event = $request->get('event');
        $app['orm.em']->remove($event);
        $app['orm.em']->flush();

        return new JsonResponse();
    }

    /**
     * API : ajouter un événement
     * POST /api/chronologies.
     */
    public function apiAddAction(Request $request, Application $app): JsonResponse
    {
        $payload = json_decode($request->getContent());

        $territoire = $app['orm.em']->find('\\'.\App\Entity\Territoire::class, $payload->territoire_id);

        $event = new \App\Entity\Chronologie();

        $event->setTerritoire($territoire);
        $event->JsonUnserialize($payload);

        $app['orm.em']->persist($event);
        $app['orm.em']->flush();

        return new JsonResponse($payload);
    }

    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Chronologie::class);
        $chronologies = $repo->findAll();

        return $app['twig']->render('admin/chronologie/index.twig', ['chronologies' => $chronologies]);
    }

    public function addAction(Request $request, Application $app)
    {
        $chronologie = new \App\Entity\Chronologie();

        // Un territoire peut avoir été passé en paramètre
        $territoireId = $request->get('territoire');

        if ($territoireId) {
            $territoire = $app['orm.em']->find('\\'.\App\Entity\Territoire::class, $territoireId);
            if ($territoire) {
                $chronologie->setTerritoire($territoire);
            }
        }

        $form = $app['form.factory']->createBuilder(new ChronologieForm(), $chronologie)
            ->add('visibilite', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getChronologieVisibility(),
            ])
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $chronologie = $form->getData();

            $app['orm.em']->persist($chronologie);
            $app['orm.em']->flush();

           $this->addFlash('success', 'L\'événement a été ajouté.');

            return $this->redirectToRoute('chronologie');
        }

        return $app['twig']->render('admin/chronologie/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $chronologie = $app['orm.em']->find('\\'.\App\Entity\Chronologie::class, $id);
        if (!$chronologie) {
            return $this->redirectToRoute('chronologie');
        }

        $form = $app['form.factory']->createBuilder(new ChronologieForm(), $chronologie)
            ->add('visibilite', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getChronologieVisibility(),
            ])
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $chronologie = $form->getData();

            $app['orm.em']->persist($chronologie);
            $app['orm.em']->flush();

           $this->addFlash('success', 'L\'événement a été mis à jour.');

            return $this->redirectToRoute('chronologie');
        }

        return $app['twig']->render('admin/chronologie/update.twig', [
            'form' => $form->createView(),
            'chronologie' => $chronologie,
        ]);
    }

    public function removeAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $chronologie = $app['orm.em']->find('\\'.\App\Entity\Chronologie::class, $id);
        if (!$chronologie) {
            return $this->redirectToRoute('chronologie');
        }

        $form = $app['form.factory']->createBuilder(new ChronologieRemoveForm(), $chronologie)
            ->add('save', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $chronologie = $form->getData();

            $app['orm.em']->remove($chronologie);
            $app['orm.em']->flush();

           $this->addFlash('success', 'L\'événement a été supprimé.');

            return $this->redirectToRoute('chronologie');
        }

        return $app['twig']->render('admin/chronologie/remove.twig', [
            'chronologie' => $chronologie,
            'form' => $form->createView(),
        ]);
    }
}
