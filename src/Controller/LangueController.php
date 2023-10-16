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

use App\Entity\Langue;
use App\Form\Groupe\GroupeLangueForm;
use LarpManager\Form\LangueForm;
use Silex\Application;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\LangueController.
 *
 * @author kevin
 */
class LangueController
{
    final public const DOC_PATH = __DIR__.'/../../../private/doc/';

    /**
     * affiche la liste des langues.
     */
    public function indexAction(Request $request, Application $app)
    {
        $langues = $app['orm.em']->getRepository('\\'.\App\Entity\Langue::class)->findAllOrderedByLabel();
        $groupeLangues = $app['orm.em']->getRepository('\\'.\App\Entity\GroupeLangue::class)->findAllOrderedByLabel();

        return $app['twig']->render('langue/index.twig', ['langues' => $langues, 'groupeLangues' => $groupeLangues]);
    }

    /**
     * Detail d'une langue.
     */
    public function detailAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $langue = $app['orm.em']->find('\\'.\App\Entity\Langue::class, $id);

        return $app['twig']->render('langue/detail.twig', ['langue' => $langue]);
    }

    /**
     * Ajoute une langue.
     */
    public function addAction(Request $request, Application $app)
    {
        $langue = new \App\Entity\Langue();

        $form = $app['form.factory']->createBuilder(new LangueForm(), $langue)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        // si l'utilisateur soumet une nouvelle langue
        if ($form->isValid()) {
            $langue = $form->getData();
            if (self::handleDocument($request, $app, $form, $langue)) {
                $app['orm.em']->persist($langue);
                $app['orm.em']->flush();

                $app['session']->getFlashBag()->add('success', 'La langue a été ajoutée.');

                // l'utilisateur est redirigé soit vers la liste des langues, soit vers de nouveau
                // vers le formulaire d'ajout d'une langue
                if ($form->get('save')->isClicked()) {
                    return $app->redirect($app['url_generator']->generate('langue'), 303);
                } elseif ($form->get('save_continue')->isClicked()) {
                    return $app->redirect($app['url_generator']->generate('langue.add'), 303);
                }
            }
        }

        return $app['twig']->render('langue/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie une langue. Si l'utilisateur clique sur "sauvegarder", la langue est sauvegardée et
     * l'utilisateur est redirigé vers la liste des langues.
     * Si l'utilisateur clique sur "supprimer", la langue est supprimée et l'utilisateur est
     * redirigé vers la liste des langues.
     */
    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $langue = $app['orm.em']->find('\\'.\App\Entity\Langue::class, $id);
        $hasDocumentUrl = !empty($langue->getDocumentUrl());
        $canBeDeleted = $langue->getPersonnageLangues()->isEmpty()
            && $langue->getTerritoires()->isEmpty()
            && $langue->getDocuments()->isEmpty();

        $deleteTooltip = $canBeDeleted ? '' : 'Cette langue est référencée par '.$langue->getPersonnageLangues()->count().' personnages, '.$langue->getTerritoires()->count().' territoires et '.$langue->getDocuments()->count().' documents et ne peut pas être supprimée';

        $formBuilder = $app['form.factory']->createBuilder(new LangueForm(), $langue, ['hasDocumentUrl' => $hasDocumentUrl])
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer', 'disabled' => !$canBeDeleted, 'attr' => ['title' => $deleteTooltip]]);

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $langue = $form->getData();

            if ($form->get('update')->isClicked()) {
                if (self::handleDocument($request, $app, $form, $langue)) {
                    $app['orm.em']->persist($langue);
                    $app['orm.em']->flush();
                    $app['session']->getFlashBag()->add('success', 'La langue a été mise à jour.');

                    return $app->redirect($app['url_generator']->generate('langue.detail', ['index' => $id]), 303);
                }
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($langue);
                $app['orm.em']->flush();
                // delete language document if it exists
                self::tryDeleteDocument($langue);
                $app['session']->getFlashBag()->add('success', 'La langue a été supprimée.');

                return $app->redirect($app['url_generator']->generate('langue'), 303);
            }
        }

        return $app['twig']->render('langue/update.twig', [
            'langue' => $langue,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gère le document uploadé et renvoie true si il est valide, false sinon.
     */
    private function handleDocument(Request $request, Application $app, Form $form, Langue $langue): bool
    {
        $files = $request->files->get($form->getName());
        $documentFile = $files['document'];
        // Si un document est fourni, l'enregistrer
        if (null != $documentFile) {
            $filename = $documentFile->getClientOriginalName();
            $extension = pathinfo((string) $filename, PATHINFO_EXTENSION);

            if (!$extension || 'pdf' != $extension) {
                $app['session']->getFlashBag()->add('error', 'Désolé, votre document n\'est pas valide. Vérifiez le format de votre document ('.$extension.'), seuls les .pdf sont acceptés.');

                return false;
            }

            $documentFilename = hash('md5', $langue->getLabel().$filename.time()).'.'.$extension;

            $documentFile->move(self::DOC_PATH, $documentFilename);

            // delete previous language document if it exists
            self::tryDeleteDocument($langue);

            $langue->setDocumentUrl($documentFilename);
        }

        return true;
    }

    /**
     * Supprime le document spécifié, en cas d'erreur, ne fait rien pour le moment.
     *
     * @param Langue $langue
     */
    private function tryDeleteDocument($langue): void
    {
        try {
            if (!empty($langue->getDocumentUrl())) {
                $docFilePath = self::DOC_PATH.$langue->getDocumentUrl();
                unlink($docFilePath);
            }
        } catch (FileException) {
            // for now, simply ignore
        }
    }

    /**
     * Detail d'un groupe de langue.
     */
    public function detailGroupAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $groupeLangue = $app['orm.em']->find('\\'.\App\Entity\GroupeLangue::class, $id);

        return $app['twig']->render('langue/detailGroup.twig', ['groupeLangue' => $groupeLangue]);
    }

    /**
     * Ajoute un groupe de langue.
     */
    public function addGroupAction(Request $request, Application $app)
    {
        $groupeLangue = new \App\Entity\GroupeLangue();

        $form = $app['form.factory']->createBuilder(new GroupeLangueForm(), $groupeLangue)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        // si l'utilisateur soumet une nouvelle langue
        if ($form->isValid()) {
            $groupeLangue = $form->getData();
            $app['orm.em']->persist($groupeLangue);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le groupe de langue a été ajouté.');

            // l'utilisateur est redirigé soit vers la liste des langues, soit vers de nouveau
            // vers le formulaire d'ajout d'une langue
            if ($form->get('save')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('langue'), 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('langue.addGroup'), 303);
            }
        }

        return $app['twig']->render('langue/addGroup.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie un groupe de langue. Si l'utilisateur clique sur "sauvegarder", le groupe de langue est sauvegardé.
     * Si l'utilisateur clique sur "supprimer", le groupe de langue est supprimé et l'utilisateur est
     * redirigé vers la liste des langues.
     */
    public function updateGroupAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $groupeLangue = $app['orm.em']->find('\\'.\App\Entity\GroupeLangue::class, $id);

        $canBeDeleted = $groupeLangue->getLangues()->isEmpty();
        $deleteTooltip = $canBeDeleted ? '' : 'Ce groupe est référencé par '.$groupeLangue->getLangues()->count().' langues et ne peut pas être supprimé';

        $formBuilder = $app['form.factory']->createBuilder(new GroupeLangueForm(), $groupeLangue)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer', 'disabled' => !$canBeDeleted, 'attr' => ['title' => $deleteTooltip]]);

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupeLangue = $form->getData();

            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($groupeLangue);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'Le groupe de langue a été mis à jour.');

                return $app->redirect($app['url_generator']->generate('langue.detailGroup', ['index' => $id]), 303);
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($groupeLangue);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'Le groupe de langue a été supprimé.');

                return $app->redirect($app['url_generator']->generate('langue'), 303);
            }
        }

        return $app['twig']->render('langue/updateGroup.twig', [
            'groupeLangue' => $groupeLangue,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une langue.
     */
    public function adminDocumentAction(Request $request, Application $app)
    {
        $langue = $request->get('langue');
        $document = $langue->getDocumentUrl();
        $file = self::DOC_PATH.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$langue->getPrintLabel().'.pdf"',
        ]);
    }
}
