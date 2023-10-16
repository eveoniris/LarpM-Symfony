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

use App\Entity\Connaissance;
use LarpManager\Form\ConnaissanceDeleteForm;
use LarpManager\Form\ConnaissanceForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\ConnaissanceController.
 *
 * @author Kevin F.
 */
class ConnaissanceController
{
    // liste des colonnes à afficher par défaut sur les vues 'personnages' (l'ordre est pris en compte)
    private array $defaultPersonnageListColumnKeys = ['colId', 'colStatut', 'colNom', 'colClasse', 'colGroupe', 'colUser'];

    /**
     * Liste des connaissances.
     */
    public function listAction(Request $request, Application $app)
    {
        $connaissances = $app['orm.em']->getRepository('\\'.\App\Entity\Connaissance::class)->findAllOrderedByLabel();

        return $app['twig']->render('admin/connaissance/list.twig', [
            'connaissances' => $connaissances,
        ]);
    }

    /**
     * Detail d'une connaissance.
     */
    public function detailAction(Request $request, Application $app)
    {
        $connaissance = $request->get('connaissance');

        return $app['twig']->render('admin/connaissance/detail.twig', [
            'connaissance' => $connaissance,
        ]);
    }

    /**
     * Ajoute une connaissance.
     */
    public function addAction(Request $request, Application $app)
    {
        $connaissance = new Connaissance();

        $form = $app['form.factory']->createBuilder(new ConnaissanceForm(), $connaissance)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $connaissance = $form->getData();
            $connaissance->setNiveau(1);

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $app['session']->getFlashBag()->add('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $app->redirect($app['url_generator']->generate('connaissance.list'), 303);
                }

                $documentFilename = hash('md5', $connaissance->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $connaissance->setDocumentUrl($documentFilename);
            }

            $app['orm.em']->persist($connaissance);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La connaissance a été ajoutée');

            return $app->redirect($app['url_generator']->generate('connaissance.detail', ['connaissance' => $connaissance->getId()]), 303);
        }

        return $app['twig']->render('admin/connaissance/add.twig', [
            'connaissance' => $connaissance,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une connaissance.
     */
    public function updateAction(Request $request, Application $app)
    {
        $connaissance = $request->get('connaissance');

        $form = $app['form.factory']->createBuilder(new ConnaissanceForm(), $connaissance)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $connaissance = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $app['session']->getFlashBag()->add('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $app->redirect($app['url_generator']->generate('connaissance.list'), 303);
                }

                $documentFilename = hash('md5', $connaissance->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $connaissance->setDocumentUrl($documentFilename);
            }

            $app['orm.em']->persist($connaissance);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La connaissance a été sauvegardée');

            return $app->redirect($app['url_generator']->generate('connaissance.detail', ['connaissance' => $connaissance->getId()]), 303);
        }

        return $app['twig']->render('admin/connaissance/update.twig', [
            'connaissance' => $connaissance,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une connaissance.
     */
    public function deleteAction(Request $request, Application $app)
    {
        $connaissance = $request->get('connaissance');

        $form = $app['form.factory']->createBuilder(new ConnaissanceDeleteForm(), $connaissance)
            ->add('save', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $connaissance = $form->getData();

            $app['orm.em']->remove($connaissance);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La connaissance a été supprimée');

            return $app->redirect($app['url_generator']->generate('connaissance.list'), 303);
        }

        return $app['twig']->render('admin/connaissance/delete.twig', [
            'connaissance' => $connaissance,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une connaissance.
     */
    public function getDocumentAction(Request $request, Application $app)
    {
        $document = $request->get('document');
        $connaissance = $request->get('connaissance');

        $file = __DIR__.'/../../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$connaissance->getPrintLabel().'.pdf"',
        ]);
    }

    /**
     * Liste des personnages ayant cette connaissance.
     *
     * @param Connaissance
     */
    public function personnagesAction(Request $request, Application $app, Connaissance $connaissance)
    {
        $routeName = 'connaissance.personnages';
        $routeParams = ['connaissance' => $connaissance->getId()];
        $twigFilePath = 'admin/connaissance/personnages.twig';
        $columnKeys = $this->defaultPersonnageListColumnKeys;
        $personnages = $connaissance->getPersonnages();
        $additionalViewParams = [
            'connaissance' => $connaissance,
        ];

        // handle the request and return an array containing the parameters for the view
        $personnageSearchHandler = $app['personnage.manager']->getSearchHandler();

        $viewParams = $personnageSearchHandler->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages
        );

        return $app['twig']->render(
            $twigFilePath,
            $viewParams
        );
    }
}
