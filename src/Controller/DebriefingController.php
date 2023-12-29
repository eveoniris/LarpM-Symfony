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

use App\Entity\Debriefing;
use App\Entity\Groupe;
use JasonGrimes\Paginator;
use LarpManager\Form\Debriefing\DebriefingDeleteForm;
use LarpManager\Form\Debriefing\DebriefingFindForm;
use LarpManager\Form\Debriefing\DebriefingForm;
use Silex\Application;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\DebriefingController.
 *
 * @author kevin
 */
class DebriefingController
{
    final public const DOC_PATH = __DIR__.'/../../../private/doc/';

    /**
     * Présentation des debriefings.
     */
    public function listAction(Request $request, Application $app)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' === $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $form = $app['form.factory']->createBuilder(new DebriefingFindForm())
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // TODO
            /*$data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            switch ($type){
                case 'Auteur':
                    $criteria[] = "g.nom LIKE '%$value%'";
                    break;
                case 'Groupe':
                    $criteria[] = "u.name LIKE '%$value%'";
                    break;
            }*/
        }

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Debriefing::class);
        $debriefings = $repo->findBy(
            $criteria,
            [$order_by => $order_dir],
            $limit,
            $offset);

        $numResults = $repo->findCount($criteria);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('debriefing.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $app['twig']->render('admin/debriefing/list.twig', [
            'debriefings' => $debriefings,
            'paginator' => $paginator,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajout d'un debriefing.
     */
    public function addAction(Request $request, Application $app)
    {
        $debriefing = new Debriefing();
        $groupeId = $request->get('groupe');

        if ($groupeId) {
            $groupe = $app['orm.em']->find(Groupe::class, $groupeId);
            if ($groupe) {
                $debriefing->setGroupe($groupe);
            }
        }

        $form = $app['form.factory']->createBuilder(new DebriefingForm(), $debriefing)
            ->add('visibility', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getVisibility(),
            ])
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $debriefing = $form->getData();
            $debriefing->setUser($this->getUser());

            if ($this->handleDocument($request, $app, $form, $debriefing)) {
                $app['orm.em']->persist($debriefing);
                $app['orm.em']->flush();

                $app['session']->getFlashBag()->add('success', 'Le debriefing a été ajouté.');
            }

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $debriefing->getGroupe()->getId()]), 303);
        }

        return $app['twig']->render('admin/debriefing/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un debriefing.
     */
    public function deleteAction(Request $request, Application $app, Debriefing $debriefing)
    {
        $form = $app['form.factory']->createBuilder(new DebriefingDeleteForm(), $debriefing)
            ->add('save', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $debriefing = $form->getData();
            $app['orm.em']->remove($debriefing);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le debriefing a été supprimé.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $debriefing->getGroupe()->getId()]), 303);
        }

        return $app['twig']->render('admin/debriefing/delete.twig', [
            'form' => $form->createView(),
            'debriefing' => $debriefing,
        ]);
    }

    /**
     * Mise à jour d'un debriefing.
     */
    public function updateAction(Request $request, Application $app, Debriefing $debriefing)
    {
        $form = $app['form.factory']->createBuilder(new DebriefingForm(), $debriefing)
            ->add('visibility', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getVisibility(),
            ])
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $debriefing = $form->getData();

            if ($this->handleDocument($request, $app, $form, $debriefing)) {
                $app['orm.em']->persist($debriefing);
                $app['orm.em']->flush();

                $app['session']->getFlashBag()->add('success', 'Le debriefing a été modifié.');

                return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $debriefing->getGroupe()->getId()]), 303);
            }
        }

        return $app['twig']->render('admin/debriefing/update.twig', [
            'form' => $form->createView(),
            'debriefing' => $debriefing,
        ]);
    }

    /**
     * Détail d'un debriefing.
     */
    public function detailAction(Request $request, Application $app, Debriefing $debriefing)
    {
        return $app['twig']->render('admin/debriefing/detail.twig', [
            'debriefing' => $debriefing,
        ]);
    }

    /**
     * Gère le document uploadé et renvoie true si il est valide, false sinon.
     */
    private function handleDocument(Request $request, Application $app, Form $form, Debriefing $debriefing): bool
    {
        $files = $request->files->get($form->getName());
        $documentFile = $files['document'];
        // Si un document est fourni, l'enregistrer
        if (null !== $documentFile) {
            $filename = $documentFile->getClientOriginalName();
            $extension = pathinfo((string) $filename, PATHINFO_EXTENSION);

            if ('pdf' !== $extension) {
                $app['session']->getFlashBag()->add('error', 'Désolé, votre document n\'est pas valide. Vérifiez le format de votre document ('.$extension.'), seuls les .pdf sont acceptés.');

                return false;
            }

            $documentFilename = hash('md5', $debriefing->getTitre().$filename.time()).'.'.$extension;

            $documentFile->move(self::DOC_PATH, $documentFilename);

            // delete previous language document if it exists
            $this->tryDeleteDocument($debriefing);

            $debriefing->setDocumentUrl($documentFilename);
        }

        return true;
    }

    /**
     * Supprime le document spécifié, en cas d'erreur, ne fait rien pour le moment.
     */
    private function tryDeleteDocument(Debriefing $debriefing): void
    {
        try {
            if (!empty($debriefing->getDocumentUrl())) {
                $docFilePath = self::DOC_PATH.$debriefing->getDocumentUrl();
                unlink($docFilePath);
            }
        } catch (FileException) {
            // for now, simply ignore
        }
    }

    /**
     * Afficher le document lié a un debriefing.
     */
    public function documentAction(Request $request, Application $app)
    {
        $debriefing = $request->get('debriefing');
        $document = $debriefing->getDocumentUrl();
        $file = self::DOC_PATH.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$debriefing->getPrintTitre().'.pdf"',
        ]);
    }
}
