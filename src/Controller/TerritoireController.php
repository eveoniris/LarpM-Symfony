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
use App\Entity\Territoire;
use JasonGrimes\Paginator;
use LarpManager\Form\Territoire\FiefForm;
use LarpManager\Form\Territoire\TerritoireBlasonForm;
use LarpManager\Form\Territoire\TerritoireCiblesForm;
use LarpManager\Form\Territoire\TerritoireCultureForm;
use LarpManager\Form\Territoire\TerritoireDeleteForm;
use LarpManager\Form\Territoire\TerritoireForm;
use LarpManager\Form\Territoire\TerritoireIngredientsForm;
use LarpManager\Form\Territoire\TerritoireLoiForm;
use LarpManager\Form\Territoire\TerritoireStatutForm;
use LarpManager\Form\Territoire\TerritoireStrategieForm;
use LarpManager\Repository\ConstructionRepository;
use LarpManager\Repository\TerritoireRepository;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\TerritoireController.
 *
 * @author kevin
 */
class TerritoireController extends AbstractController
{
    /**
     * Modifier les listes de cibles pour les quêtes commerciales.
     */
    public function updateCiblesAction(Request $request, Application $app, Territoire $territoire)
    {
        $form = $app['form.factory']->createBuilder(new TerritoireCiblesForm(), $territoire)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $territoire = $form->getData();

            $app['orm.em']->persist($territoire);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $app['twig']->render('admin/territoire/cibles.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des territoires.
     */
    public function listAction(Request $request, Application $app)
    {
        $territoires = $app['orm.em']->getRepository('\\'.\App\Entity\Territoire::class)->findRoot();

        return $app['twig']->render('admin/territoire/list.twig', ['territoires' => $territoires]);
    }

    /**
     * Liste des fiefs.
     */
    public function fiefAction(Request $request, Application $app)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 500);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $formData = $request->query->get('personnageFind');
        $pays = isset($formData['pays']) ? $app['orm.em']->find(\App\Entity\Territoire::class, $formData['pays']) : null;
        $province = isset($formData['provinces']) ? $app['orm.em']->find(\App\Entity\Territoire::class, $formData['provinces']) : null;
        $groupe = isset($formData['groupe']) ? $app['orm.em']->find(\App\Entity\Groupe::class, $formData['groupe']) : null;
        $optionalParameters = '';

        $listeGroupes = $app['orm.em']->getRepository('\\'.\App\Entity\Groupe::class)->findList(null, null, ['by' => 'nom', 'dir' => 'ASC'], 1000, 0);
        $listePays = $app['orm.em']->getRepository('\\'.\App\Entity\Territoire::class)->findRoot();
        $listeProvinces = $app['orm.em']->getRepository('\\'.\App\Entity\Territoire::class)->findProvinces();

        $form = $app['form.factory']->createBuilder(
            new FiefForm(),
            null,
            [
                'data' => [
                    'pays' => $pays,
                    'province' => $province,
                    'groupe' => $groupe,
                ],
                'listeGroupes' => $listeGroupes,
                'listePays' => $listePays,
                'listeProvinces' => $listeProvinces,
                'method' => 'get',
                'csrf_protection' => false,
            ]
        )->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            $pays = $data['pays'] ? $data['pays'] : null;
            $province = $data['province'] ? $data['province'] : null;
            $groupe = $data['groupe'] ? $data['groupe'] : null;

            if ($type && $value) {
                switch ($type) {
                    case 'idFief':
                        $criteria['t.id'] = $value;
                        break;
                    case 'nomFief':
                        $criteria['t.nom'] = $value;
                        break;
                }
            }
        }

        if ($groupe) {
            $criteria['tgr.id'] = $groupe->getId();
            $optionalParameters .= '&fief[groupe]='.$groupe->getId();
        }

        if ($pays) {
            $criteria['tp.id'] = $pays->getId();
            $optionalParameters .= '&fief[pays]='.$pays->getId();
        }

        if ($province) {
            $criteria['tpr.id'] = $province->getId();
            $optionalParameters .= '&fief[province]='.$province->getId();
        }

        /* @var TerritoireRepository $repo */
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Territoire::class);
        $fiefs = $repo->findFiefsList(
            $criteria,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset
        );

        $numResults = count($fiefs);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('territoire.fief').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir.$optionalParameters
        );

        return $app['twig']->render('territoire/fief.twig', [
            'fiefs' => $fiefs,
            'form' => $form->createView(),
            'paginator' => $paginator,
            'optionalParameters' => $optionalParameters,
        ]);
    }

    /**
     * Impression des territoires.
     */
    public function printAction(Request $request, Application $app)
    {
        $territoires = $app['orm.em']->getRepository('\\'.\App\Entity\Territoire::class)->findFiefs();

        return $app['twig']->render('admin/territoire/print.twig', ['territoires' => $territoires]);
    }

    /**
     * Liste des fiefs pour les quêtes.
     */
    public function queteAction(Request $request, Application $app)
    {
        $territoires = $app['orm.em']->getRepository('\\'.\App\Entity\Territoire::class)->findFiefs();

        return $app['twig']->render('admin/territoire/quete.twig', ['territoires' => $territoires]);
    }

    /**
     * Liste des pays avec le nombre de noble.
     */
    public function nobleAction(Request $request, Application $app)
    {
        $territoires = $app['orm.em']->getRepository('\\'.\App\Entity\Territoire::class)->findRoot();

        return $app['twig']->render('admin/territoire/noble.twig', ['territoires' => $territoires]);
    }

    /**
     * Detail d'un territoire pour les joueurs.
     */
    public function detailJoueurAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');

        return $app['twig']->render('public/territoire/detail.twig', ['territoire' => $territoire]);
    }

    /**
     * Detail d'un territoire.
     */
    public function detailAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');

        return $app['twig']->render('admin/territoire/detail.twig', ['territoire' => $territoire]);
    }

    /**
     * Ajoute une loi à un territoire.
     */
    public function updateLoiAction(Request $request, Application $app, Territoire $territoire)
    {
        $form = $app['form.factory']->createBuilder(new TerritoireLoiForm(), $territoire)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $territoire = $form->getData();

            $app['orm.em']->persist($territoire);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $app['twig']->render('admin/territoire/loi.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute une construction dans un territoire.
     */
    public function constructionAddAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');

        $form = $app['form.factory']->createBuilder()
            ->add('construction', 'entity', [
                'label' => 'Choisissez la construction',
                'required' => true,
                'class' => \App\Entity\Construction::class,
                'query_builder' => static function (ConstructionRepository $repo) {
                    return $repo->createQueryBuilder('c')->orderBy('c.label', 'ASC');
                },
                'property' => 'fullLabel',
                'expanded' => true,
            ])
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $territoire->addConstruction($data['construction']);
            $app['orm.em']->persist($territoire);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La construction a été ajoutée.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $app['twig']->render('admin/territoire/addConstruction.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retire une construction d'un territoire.
     */
    public function constructionRemoveAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');
        $construction = $request->get('construction');

        $form = $app['form.factory']->createBuilder()
            ->add('save', 'submit', ['label' => 'Retirer la construction'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $territoire->removeConstruction($construction);
            $app['orm.em']->persist($territoire);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La construction a été retiré.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $app['twig']->render('admin/territoire/removeConstruction.twig', [
            'territoire' => $territoire,
            'construction' => $construction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un territoire.
     */
    public function addAction(Request $request, Application $app)
    {
        $territoire = new \App\Entity\Territoire();

        $form = $app['form.factory']->createBuilder(new TerritoireForm(), $territoire)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $territoire = $form->getData();

            /**
             * Création des topics associés à ce groupe
             * un topic doit être créé par GN auquel ce groupe est inscrit.
             *
             * @var \App\Entity\Topic $topic
             */
            $topic = new \App\Entity\Topic();
            $topic->setTitle($territoire->getNom());
            $topic->setDescription($territoire->getDescription());
            $topic->setUser($this->getUser());
            // défini les droits d'accés à ce forum
            // (les membres du groupe ont le droit d'accéder à ce forum)
            $topic->setRight('TERRITOIRE_MEMBER');
            $topic->setTopic($app['larp.manager']->findTopic('TOPIC_TERRITOIRE'));

            $territoire->setTopic($topic);

            $app['orm.em']->persist($topic);
            $app['orm.em']->persist($territoire);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le territoire a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('territoire.admin.list', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('territoire.admin.add', [], 303);
            }
        }

        return $app['twig']->render('admin/territoire/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour de la liste des ingrédients fourni par un territoire.
     */
    public function updateIngredientsAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');

        $form = $app['form.factory']->createBuilder(new TerritoireIngredientsForm(), $territoire)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $territoire = $form->getData();

            $app['orm.em']->persist($territoire);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le territoire a été mis à jour.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $app['twig']->render('admin/territoire/updateIngredients.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie un territoire.
     */
    public function updateAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');

        $form = $app['form.factory']->createBuilder(new TerritoireForm(), $territoire)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $territoire = $form->getData();

            $app['orm.em']->persist($territoire);
            $app['orm.em']->flush();
           $this->addFlash('success', 'Le territoire a été mis à jour.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $app['twig']->render('admin/territoire/update.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour la culture d'un territoire.
     */
    public function updateCultureAction(Request $request, Application $app, Territoire $territoire)
    {
        $form = $app['form.factory']->createBuilder(new TerritoireCultureForm(), $territoire)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $app['orm.em']->persist($territoire);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $app['twig']->render('admin/territoire/culture.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour le statut d'un territoire.
     */
    public function updateStatutAction(Request $request, Application $app, Territoire $territoire)
    {
        $form = $app['form.factory']->createBuilder(new TerritoireStatutForm(), $territoire)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $app['orm.em']->persist($territoire);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $app['twig']->render('admin/territoire/updateStatut.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour le blason d'un territoire.
     */
    public function updateBlasonAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');

        $form = $app['form.factory']->createBuilder(new TerritoireBlasonForm(), $territoire)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $files = $request->files->get($form->getName());

            $path = __DIR__.'/../../../private/img/blasons/';
            $filename = $files['blason']->getClientOriginalName();
            $extension = $files['blason']->guessExtension();

            if (!$extension || !in_array($extension, ['png', 'jpg', 'jpeg', 'bmp'])) {
               $this->addFlash('error', 'Désolé, votre image ne semble pas valide (vérifiez le format de votre image)');

                return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
            }

            $blasonFilename = hash('md5', $this->getUser()->getUsername().$filename.time()).'.'.$extension;

            $image = $app['imagine']->open($files['blason']->getPathname());
            $image->resize($image->getSize()->widen(160));
            $image->save($path.$blasonFilename);

            $territoire->setBlason($blasonFilename);
            $app['orm.em']->persist($territoire);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le blason a été enregistré');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $app['twig']->render('admin/territoire/blason.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie le jeu strategique d'un territoire.
     */
    public function updateStrategieAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');

        $form = $app['form.factory']->createBuilder(new TerritoireStrategieForm(), $territoire)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $territoire = $form->getData();

            $app['orm.em']->persist($territoire);
            $app['orm.em']->flush();
           $this->addFlash('success', 'Le territoire a été mis à jour.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $app['twig']->render('admin/territoire/updateStrategie.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supression d'un territoire.
     */
    public function deleteAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');

        $form = $app['form.factory']->createBuilder(new TerritoireDeleteForm(), $territoire)
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $territoire = $form->getData();

            foreach ($territoire->getPersonnages() as $personnage) {
                $personnage->setTerritoire(null);
                $app['orm.em']->persist($personnage);
            }

            foreach ($territoire->getGroupes() as $groupe) {
                $groupe->removeTerritoire($territoire);
                $app['orm.em']->persist($groupe);
            }

            if ($territoire->getGroupe()) {
                $groupe = $territoire->getGroupe();
                $groupe->setTerritoire(null);
                $app['orm.em']->persist($groupe);
            }

            $app['orm.em']->remove($territoire);
            $app['orm.em']->flush();
           $this->addFlash('success', 'Le territoire a été supprimé.');

            return $this->redirectToRoute('territoire.admin.list', [], 303);
        }

        return $app['twig']->render('admin/territoire/delete.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajout d'un topic pour un territoire.
     */
    public function addTopicAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');

        $topic = new \App\Entity\Topic();
        $topic->setTitle($territoire->getNom());
        $topic->setDescription($territoire->getDescription());
        $topic->setUser($this->getUser());
        $topic->setRight('TERRITOIRE_MEMBER');
        $topic->setObjectId($territoire->getId());
        $topic->addTerritoire($territoire);
        $topic->setTopic($app['larp.manager']->findTopic('TOPIC_TERRITOIRE'));

        $territoire->setTopic($topic);

        $app['orm.em']->persist($topic);
        $app['orm.em']->persist($territoire);
        $app['orm.em']->flush();

       $this->addFlash('success', 'Le topic a été ajouté.');

        return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
    }

    /**
     * Supression d'un topic pour un territoire.
     */
    public function deleteTopicAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');

        $topic = $territoire->getTopic();

        if ($topic) {
            $territoire->setTopic(null);

            $app['orm.em']->persist($territoire);
            $app['orm.em']->remove($topic);
            $app['orm.em']->flush();
        }

       $this->addFlash('success', 'Le topic a été supprimé.');

        return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
    }

    /**
     * Ajoute un événement à un territoire.
     */
    public function eventAddAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');
        $event = $request->get('event');

        $event = new \App\Entity\Chronologie();

        $form = $app['form.factory']->createBuilder(new EventForm(), $event)
            ->add('add', 'submit', ['label' => 'Ajouter'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $event = $form->getData();

            $app['orm.em']->persist($event);
            $app['orm.em']->flush();
           $this->addFlash('success', 'L\'evenement a été ajouté.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $app['twig']->render('admin/territoire/addEvent.twig', [
            'territoire' => $territoire,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un événement.
     */
    public function eventUpdateAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');
        $event = $request->get('event');

        $form = $app['form.factory']->createBuilder(new ChronologieForm(), $event)
            ->add('update', 'submit', ['label' => 'Mettre à jour'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $event = $form->getData();

            $app['orm.em']->persist($event);
            $app['orm.em']->flush();
           $this->addFlash('success', 'L\'evenement a été modifié.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $app['twig']->render('admin/territoire/updateEvent.twig', [
            'territoire' => $territoire,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un événement.
     */
    public function eventDeleteAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');
        $event = $request->get('event');

        $form = $app['form.factory']->createBuilder(new ChronologieDeleteForm(), $event)
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $event = $form->getData();

            $app['orm.em']->remove($event);
            $app['orm.em']->flush();
           $this->addFlash('success', 'L\'evenement a été supprimé.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $app['twig']->render('admin/territoire/deleteEvent.twig', [
            'territoire' => $territoire,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }
}
