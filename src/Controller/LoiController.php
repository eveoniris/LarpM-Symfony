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

use App\Entity\Loi;
use LarpManager\Form\Loi\LoiDeleteForm;
use LarpManager\Form\Loi\LoiForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class LoiController extends AbstractController
{
    /**
     * Liste des loi.
     */
    public function indexAction(Request $request, Application $app)
    {
        $lois = $app['orm.em']->getRepository(\App\Entity\Loi::class)->findAll();

        return $app['twig']->render('admin\loi\index.twig', [
            'lois' => $lois,
        ]);
    }

    /**
     * Ajout d'une loi.
     */
    public function addAction(Request $request, Application $app)
    {
        $form = $app['form.factory']->createBuilder(new LoiForm(), new Loi())->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $loi = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('loi', [], 303);
                }

                $documentFilename = hash('md5', $loi->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $loi->setDocumentUrl($documentFilename);
            }

            $app['orm.em']->persist($loi);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La loi a été ajoutée.');

            return $this->redirectToRoute('loi', [], 303);
        }

        return $app['twig']->render('admin\loi\add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une loi.
     */
    public function detailAction(Request $request, Application $app, Loi $loi)
    {
        return $app['twig']->render('admin\loi\detail.twig', [
            'loi' => $loi,
        ]);
    }

    /**
     * Mise à jour d'une loi.
     */
    public function updateAction(Request $request, Application $app, Loi $loi)
    {
        $form = $app['form.factory']->createBuilder(new LoiForm(), $loi)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $loi = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('loi', [], 303);
                }

                $documentFilename = hash('md5', $loi->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $loi->setDocumentUrl($documentFilename);
            }

            $app['orm.em']->persist($loi);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La loi a été mise à jour.');

            return $this->redirectToRoute('loi', [], 303);
        }

        return $app['twig']->render('admin\loi\update.twig', [
            'form' => $form->createView(),
            'loi' => $loi,
        ]);
    }

    /**
     * Suppression d'une loi.
     */
    public function deleteAction(Request $request, Application $app, Loi $loi)
    {
        $form = $app['form.factory']->createBuilder(new LoiDeleteForm(), $loi)
            ->add('submit', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $loi = $form->getData();

            $app['orm.em']->remove($loi);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La loi a été supprimée.');

            return $this->redirectToRoute('loi', [], 303);
        }

        return $app['twig']->render('admin\loi\delete.twig', [
            'form' => $form->createView(),
            'loi' => $loi,
        ]);
    }

    /**
     * Retire le document d'une competence.
     */
    public function removeDocumentAction(Request $request, Application $app, Loi $loi)
    {
        $loi->setDocumentUrl(null);

        $app['orm.em']->persist($loi);
        $app['orm.em']->flush();
       $this->addFlash('success', 'La loi a été mise à jour.');

        return $this->redirectToRoute('loi');
    }

    /**
     * Téléchargement du document lié à une compétence.
     */
    public function getDocumentAction(Request $request, Application $app)
    {
        $loi = $request->get('loi');
        $document = $loi->getDocumentUrl();

        $file = __DIR__.'/../../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$loi->getLabel().'.pdf"',
        ]);
    }
}
