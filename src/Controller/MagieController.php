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

use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Sort;
use LarpManager\Form\DomaineDeleteForm;
use LarpManager\Form\DomaineForm;
use LarpManager\Form\Potion\PotionDeleteForm;
use LarpManager\Form\Potion\PotionForm;
use LarpManager\Form\PriereDeleteForm;
use LarpManager\Form\PriereForm;
use LarpManager\Form\SortDeleteForm;
use LarpManager\Form\SortForm;
use LarpManager\Form\SphereDeleteForm;
use LarpManager\Form\SphereForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\MagieController.
 *
 * @author kevin
 */
class MagieController
{
    // liste des colonnes à afficher par défaut sur les vues 'personnages' (l'ordre est pris en compte)
    private array $defaultPersonnageListColumnKeys = ['colId', 'colStatut', 'colNom', 'colClasse', 'colGroupe', 'colUser'];

    /**
     * Liste des sphere.
     */
    public function sphereListAction(Request $request, Application $app)
    {
        $spheres = $app['orm.em']->getRepository('\\'.\App\Entity\Sphere::class)->findAll();

        return $app['twig']->render('admin/sphere/list.twig', [
            'spheres' => $spheres,
        ]);
    }

    /**
     * Detail d'une sphere.
     */
    public function sphereDetailAction(Request $request, Application $app)
    {
        $sphere = $request->get('sphere');

        return $app['twig']->render('admin/sphere/detail.twig', [
            'sphere' => $sphere,
        ]);
    }

    /**
     * Ajoute une sphere.
     */
    public function sphereAddAction(Request $request, Application $app)
    {
        $sphere = new \App\Entity\Sphere();

        $form = $app['form.factory']->createBuilder(new SphereForm(), $sphere)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $sphere = $form->getData();

            $app['orm.em']->persist($sphere);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La sphere a été ajouté');

            return $app->redirect($app['url_generator']->generate('magie.sphere.detail', ['sphere' => $sphere->getId()]), 303);
        }

        return $app['twig']->render('admin/sphere/add.twig', [
            'sphere' => $sphere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une sphere.
     */
    public function sphereUpdateAction(Request $request, Application $app)
    {
        $sphere = $request->get('sphere');

        $form = $app['form.factory']->createBuilder(new SphereForm(), $sphere)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $sphere = $form->getData();

            $app['orm.em']->persist($sphere);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La sphere a été sauvegardé');

            return $app->redirect($app['url_generator']->generate('magie.sphere.detail', ['sphere' => $sphere->getId()]), 303);
        }

        return $app['twig']->render('admin/sphere/update.twig', [
            'sphere' => $sphere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une sphère.
     */
    public function sphereDeleteAction(Request $request, Application $app)
    {
        $sphere = $request->get('sphere');

        $form = $app['form.factory']->createBuilder(new SphereDeleteForm(), $sphere)
            ->add('save', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $sphere = $form->getData();

            $app['orm.em']->remove($sphere);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La sphere a été suprimé');

            return $app->redirect($app['url_generator']->generate('magie.sphere.list'), 303);
        }

        return $app['twig']->render('admin/sphere/delete.twig', [
            'sphere' => $sphere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des prieres.
     */
    public function priereListAction(Request $request, Application $app)
    {
        $prieres = $app['orm.em']->getRepository('\\'.\App\Entity\Priere::class)->findAll();

        return $app['twig']->render('admin/priere/list.twig', [
            'prieres' => $prieres,
        ]);
    }

    /**
     * Detail d'une priere.
     */
    public function priereDetailAction(Request $request, Application $app)
    {
        $priere = $request->get('priere');

        return $app['twig']->render('admin/priere/detail.twig', [
            'priere' => $priere,
        ]);
    }

    /**
     * Ajoute une priere.
     */
    public function priereAddAction(Request $request, Application $app)
    {
        $priere = new \App\Entity\Priere();

        $form = $app['form.factory']->createBuilder(new PriereForm(), $priere)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $priere = $form->getData();

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $app['session']->getFlashBag()->add('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $app->redirect($app['url_generator']->generate('magie.priere.list'), 303);
                }

                $documentFilename = hash('md5', $priere->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $priere->setDocumentUrl($documentFilename);
            }

            $app['orm.em']->persist($priere);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La priere a été ajouté');

            return $app->redirect($app['url_generator']->generate('magie.priere.detail', ['priere' => $priere->getId()]), 303);
        }

        return $app['twig']->render('admin/priere/add.twig', [
            'priere' => $priere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une priere.
     */
    public function priereUpdateAction(Request $request, Application $app)
    {
        $priere = $request->get('priere');

        $form = $app['form.factory']->createBuilder(new PriereForm(), $priere)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $priere = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $app['session']->getFlashBag()->add('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $app->redirect($app['url_generator']->generate('magie.priere.list'), 303);
                }

                $documentFilename = hash('md5', $priere->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $priere->setDocumentUrl($documentFilename);
            }

            $app['orm.em']->persist($priere);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La priere a été sauvegardé');

            return $app->redirect($app['url_generator']->generate('magie.priere.detail', ['priere' => $priere->getId()]), 303);
        }

        return $app['twig']->render('admin/priere/update.twig', [
            'priere' => $priere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une priere.
     */
    public function priereDeleteAction(Request $request, Application $app)
    {
        $priere = $request->get('priere');

        $form = $app['form.factory']->createBuilder(new PriereDeleteForm(), $priere)
            ->add('save', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $priere = $form->getData();

            $app['orm.em']->remove($priere);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La priere a été suprimé');

            return $app->redirect($app['url_generator']->generate('magie.priere.list'), 303);
        }

        return $app['twig']->render('admin/priere/delete.twig', [
            'priere' => $priere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une priere.
     */
    public function getPriereDocumentAction(Request $request, Application $app)
    {
        $document = $request->get('document');
        $priere = $request->get('priere');

        // on ne peux télécharger que les documents des compétences que l'on connait
        /*if  ( ! $app['security.authorization_checker']->isGranted('ROLE_REGLE') )
        {
        if ( $app['User']->getPersonnage() )
        {
        if ( ! $app['User']->getPersonnage()->getCompetences()->contains($competence) )
        {
        $app['session']->getFlashBag()->add('error', 'Vous n\'avez pas les droits necessaires');
        }
        }
        }*/

        $file = __DIR__.'/../../../private/doc/'.$document;

        /*$stream = function () use ($file) {
            readfile($file);
        };

        return $app->stream($stream, 200, array(
                'Content-Type' => 'text/pdf',
                'Content-length' => filesize($file),
                'Content-Disposition' => 'attachment; filename="'.$priere->getLabel().'.pdf"'
        ));*/

        return $app->sendFile($file);
    }

    /**
     * Liste des personnages ayant cette prière.
     */
    public function prierePersonnagesAction(Request $request, Application $app, Priere $priere)
    {
        $routeName = 'magie.priere.personnages';
        $routeParams = ['priere' => $priere->getId()];
        $twigFilePath = 'admin/priere/personnages.twig';
        $columnKeys = $this->defaultPersonnageListColumnKeys;
        $personnages = $priere->getPersonnages();
        $additionalViewParams = [
            'priere' => $priere,
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

    /**
     * Liste des potions.
     */
    public function potionListAction(Request $request, Application $app)
    {
        $potions = $app['orm.em']->getRepository('\\'.\App\Entity\Potion::class)->findAll();

        return $app['twig']->render('admin/potion/list.twig', [
            'potions' => $potions,
        ]);
    }

    /**
     * Detail d'une potion.
     */
    public function potionDetailAction(Request $request, Application $app)
    {
        $potion = $request->get('potion');

        return $app['twig']->render('admin/potion/detail.twig', [
            'potion' => $potion,
        ]);
    }

    /**
     * Liste des personnages ayant cette potion.
     *
     * @param Potion
     */
    public function potionPersonnagesAction(Request $request, Application $app, Potion $potion)
    {
        $routeName = 'magie.potion.personnages';
        $routeParams = ['potion' => $potion->getId()];
        $twigFilePath = 'admin/potion/personnages.twig';
        $columnKeys = $this->defaultPersonnageListColumnKeys;
        $personnages = $potion->getPersonnages();
        $additionalViewParams = [
            'potion' => $potion,
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

    /**
     * Ajoute une potion.
     */
    public function potionAddAction(Request $request, Application $app)
    {
        $potion = new \App\Entity\Potion();

        $form = $app['form.factory']->createBuilder(new PotionForm(), $potion)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $potion = $form->getData();

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $app['session']->getFlashBag()->add('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $app->redirect($app['url_generator']->generate('magie.potion.list'), 303);
                }

                $documentFilename = hash('md5', $potion->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $potion->setDocumentUrl($documentFilename);
            }

            $app['orm.em']->persist($potion);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La potion a été ajouté');

            return $app->redirect($app['url_generator']->generate('magie.potion.detail', ['potion' => $potion->getId()]), 303);
        }

        return $app['twig']->render('admin/potion/add.twig', [
            'potion' => $potion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une potion.
     */
    public function potionUpdateAction(Request $request, Application $app)
    {
        $potion = $request->get('potion');

        $form = $app['form.factory']->createBuilder(new PotionForm(), $potion)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $potion = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $app['session']->getFlashBag()->add('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $app->redirect($app['url_generator']->generate('magie.potion.list'), 303);
                }

                $documentFilename = hash('md5', $potion->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $potion->setDocumentUrl($documentFilename);
            }

            $app['orm.em']->persist($potion);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La potion a été sauvegardé');

            return $app->redirect($app['url_generator']->generate('magie.potion.detail', ['potion' => $potion->getId()]), 303);
        }

        return $app['twig']->render('admin/potion/update.twig', [
            'potion' => $potion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une potion.
     */
    public function potionDeleteAction(Request $request, Application $app)
    {
        $potion = $request->get('potion');

        $form = $app['form.factory']->createBuilder(new PotionDeleteForm(), $potion)
            ->add('save', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $potion = $form->getData();

            $app['orm.em']->remove($potion);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La potion a été suprimé');

            return $app->redirect($app['url_generator']->generate('magie.potion.list'), 303);
        }

        return $app['twig']->render('admin/potion/delete.twig', [
            'potion' => $potion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une potion.
     */
    public function getPotionDocumentAction(Request $request, Application $app)
    {
        $document = $request->get('document');
        $potion = $request->get('potion');

        $file = __DIR__.'/../../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$potion->getLabel().'.pdf"',
        ]);
    }

    /**
     * Liste des domaines de magie.
     */
    public function domaineListAction(Request $request, Application $app)
    {
        $domaines = $app['orm.em']->getRepository('\\'.\App\Entity\Domaine::class)->findAll();

        return $app['twig']->render('admin/domaine/list.twig', [
            'domaines' => $domaines,
        ]);
    }

    /**
     * Detail d'un domaine de magie.
     */
    public function domaineDetailAction(Request $request, Application $app)
    {
        $domaine = $request->get('domaine');

        return $app['twig']->render('admin/domaine/detail.twig', [
            'domaine' => $domaine,
        ]);
    }

    /**
     * Ajoute un domaine de magie.
     */
    public function domaineAddAction(Request $request, Application $app)
    {
        $domaine = new \App\Entity\Domaine();

        $form = $app['form.factory']->createBuilder(new DomaineForm(), $domaine)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $domaine = $form->getData();

            $app['orm.em']->persist($domaine);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le domaine de magie a été ajouté');

            return $app->redirect($app['url_generator']->generate('magie.domaine.detail', ['domaine' => $domaine->getId()]), 303);
        }

        return $app['twig']->render('admin/domaine/add.twig', [
            'domaine' => $domaine,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un domaine de magie.
     */
    public function domaineUpdateAction(Request $request, Application $app)
    {
        $domaine = $request->get('domaine');

        $form = $app['form.factory']->createBuilder(new DomaineForm(), $domaine)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $domaine = $form->getData();

            $app['orm.em']->persist($domaine);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le domaine de magie a été sauvegardé');

            return $app->redirect($app['url_generator']->generate('magie.domaine.detail', ['domaine' => $domaine->getId()]), 303);
        }

        return $app['twig']->render('admin/domaine/update.twig', [
            'domaine' => $domaine,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un domaine de magie.
     */
    public function domaineDeleteAction(Request $request, Application $app)
    {
        $domaine = $request->get('domaine');

        $form = $app['form.factory']->createBuilder(new DomaineDeleteForm(), $domaine)
            ->add('save', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $domaine = $form->getData();

            $app['orm.em']->remove($domaine);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le domaine de magie a été suprimé');

            return $app->redirect($app['url_generator']->generate('magie.domaine.list'), 303);
        }

        return $app['twig']->render('admin/domaine/delete.twig', [
            'domaine' => $domaine,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des sorts.
     */
    public function sortListAction(Request $request, Application $app)
    {
        $sorts = $app['orm.em']->getRepository('\\'.\App\Entity\Sort::class)->findAll();

        return $app['twig']->render('admin/sort/list.twig', [
            'sorts' => $sorts,
        ]);
    }

    /**
     * Detail d'un sort.
     */
    public function sortDetailAction(Request $request, Application $app)
    {
        $sort = $request->get('sort');

        return $app['twig']->render('admin/sort/detail.twig', [
            'sort' => $sort,
        ]);
    }

    /**
     * Ajoute un sort.
     */
    public function sortAddAction(Request $request, Application $app)
    {
        $sort = new \App\Entity\Sort();

        $form = $app['form.factory']->createBuilder(new SortForm(), $sort)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $sort = $form->getData();

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $app['session']->getFlashBag()->add('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $app->redirect($app['url_generator']->generate('magie.sort.list'), 303);
                }

                $documentFilename = hash('md5', $sort->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $sort->setDocumentUrl($documentFilename);
            }

            $app['orm.em']->persist($sort);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le sort a été ajouté');

            return $app->redirect($app['url_generator']->generate('magie.sort.detail', ['sort' => $sort->getId()]), 303);
        }

        return $app['twig']->render('admin/sort/add.twig', [
            'sort' => $sort,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un sort.
     */
    public function sortUpdateAction(Request $request, Application $app)
    {
        $sort = $request->get('sort');

        $form = $app['form.factory']->createBuilder(new SortForm(), $sort)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $sort = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $app['session']->getFlashBag()->add('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $app->redirect($app['url_generator']->generate('magie.sort.list'), 303);
                }

                $documentFilename = hash('md5', $sort->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $sort->setDocumentUrl($documentFilename);
            }

            $app['orm.em']->persist($sort);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le sort a été sauvegardé');

            return $app->redirect($app['url_generator']->generate('magie.sort.detail', ['sort' => $sort->getId()]), 303);
        }

        return $app['twig']->render('admin/sort/update.twig', [
            'sort' => $sort,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un sortilège.
     */
    public function sortDeleteAction(Request $request, Application $app)
    {
        $sort = $request->get('sort');

        $form = $app['form.factory']->createBuilder(new SortDeleteForm(), $sort)
            ->add('save', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $sort = $form->getData();

            $app['orm.em']->remove($sort);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le sort a été supprimé');

            return $app->redirect($app['url_generator']->generate('magie.sort.list'), 303);
        }

        return $app['twig']->render('admin/sort/delete.twig', [
            'sort' => $sort,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a un sort.
     */
    public function getSortDocumentAction(Request $request, Application $app)
    {
        $document = $request->get('document');
        $sort = $request->get('sort');

        // on ne peux télécharger que les documents des compétences que l'on connait
        /*if  ( ! $app['security.authorization_checker']->isGranted('ROLE_REGLE') )
        {
            if ( $app['User']->getPersonnage() )
            {
                if ( ! $app['User']->getPersonnage()->getCompetences()->contains($competence) )
                {
                    $app['session']->getFlashBag()->add('error', 'Vous n\'avez pas les droits necessaires');
                }
            }
        }*/

        $file = __DIR__.'/../../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$sort->getPrintLabel().'.pdf"',
        ]);
    }

    /**
     * Liste des personnages ayant ce sort.
     *
     * @param Sort
     */
    public function sortPersonnagesAction(Request $request, Application $app, Sort $sort)
    {
        $routeName = 'magie.sort.personnages';
        $routeParams = ['sort' => $sort->getId()];
        $twigFilePath = 'admin/sort/personnages.twig';
        $columnKeys = $this->defaultPersonnageListColumnKeys;
        $personnages = $sort->getPersonnages();
        $additionalViewParams = [
            'sort' => $sort,
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
