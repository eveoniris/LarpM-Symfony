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

use App\Entity\SecondaryGroup;
use JasonGrimes\Paginator;
use LarpManager\Form\GroupeSecondaire\GroupeSecondaireForm;
use LarpManager\Form\GroupeSecondaire\GroupeSecondaireMaterielForm;
use LarpManager\Form\GroupeSecondaire\GroupeSecondaireNewMembreForm;
use LarpManager\Form\GroupeSecondaire\SecondaryGroupFindForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\GroupeSecondaireController.
 *
 * @author kevin
 */
class GroupeSecondaireController
{
    /**
     * Liste des groupes secondaires (pour les orgas).
     */
    public function adminListAction(Request $request, Application $app)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $form = $app['form.factory']->createBuilder(
            new SecondaryGroupFindForm(),
            null,
            [
                'method' => 'get',
                'csrf_protection' => false,
            ]
        )
            ->add('find', 'submit', ['label' => 'Rechercher'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            switch ($type) {
                case 'nom':
                    // $criteria[] = new LikeExpression("p.nom", "%$value%");
                    $criteria['nom'] = "g.label like '%".preg_replace('/[\'"<>=*;]/', '', (string) $value)."%'";
                    break;
                case 'id':
                    // $criteria[] = new EqualExpression("p.id", $value);
                    $criteria['id'] = 'g.id = '.preg_replace('/[^\d]/', '', (string) $value);
                    break;
            }
        }

        /* @var SecondaryGroupRepository $repo */
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\SecondaryGroup::class);
        $groupeSecondaires = $repo->findList(
            $criteria,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset
        );

        $numResults = $repo->findCount($criteria);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('groupeSecondaire.admin.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $app['twig']->render('admin/groupeSecondaire/list.twig', [
            'groupeSecondaires' => $groupeSecondaires,
            'paginator' => $paginator,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un groupe secondaire.
     */
    public function adminAddAction(Request $request, Application $app)
    {
        $groupeSecondaire = new \App\Entity\SecondaryGroup();

        $form = $app['form.factory']->createBuilder(new GroupeSecondaireForm(), $groupeSecondaire)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupeSecondaire = $form->getData();

            /**
             * Création des topics associés à ce groupe
             * un topic doit être créé par GN auquel ce groupe est inscrit.
             *
             * @var \App\Entity\Topic $topic
             */
            $topic = new \App\Entity\Topic();
            $topic->setTitle($groupeSecondaire->getLabel());
            $topic->setDescription($groupeSecondaire->getDescription());
            $topic->setUser($app['User']);
            $topic->setTopic($app['larp.manager']->findTopic('TOPIC_GROUPE_SECONDAIRE'));
            $app['orm.em']->persist($topic);

            $groupeSecondaire->setTopic($topic);
            $app['orm.em']->persist($groupeSecondaire);
            $app['orm.em']->flush();

            // défini les droits d'accés à ce forum
            // (les membres du groupe ont le droit d'accéder à ce forum)
            $topic->setObjectId($groupeSecondaire->getId());
            $topic->setRight('GROUPE_SECONDAIRE_MEMBER');

            /**
             * Ajoute le responsable du groupe dans le groupe si il n'y est pas déjà.
             */
            $personnage = $groupeSecondaire->getResponsable();
            if ($personnage && !$groupeSecondaire->isMembre($personnage)) {
                $membre = new \App\Entity\Membre();
                $membre->setPersonnage($personnage);
                $membre->setSecondaryGroup($groupeSecondaire);
                $membre->setSecret(false);
                $app['orm.em']->persist($membre);
                $app['orm.em']->flush();
                $groupeSecondaire->addMembre($membre);
            }

            $app['orm.em']->persist($topic);
            $app['orm.em']->persist($groupeSecondaire);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le groupe secondaire a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('groupeSecondaire.admin.list'), 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('groupeSecondaire.admin.add'), 303);
            }
        }

        return $app['twig']->render('admin/groupeSecondaire/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour du matériel necessaire à un groupe secondaire.
     */
    public function materielUpdateAction(Request $request, Application $app, SecondaryGroup $groupeSecondaire)
    {
        $form = $app['form.factory']->createBuilder(new GroupeSecondaireMaterielForm(), $groupeSecondaire)->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupeSecondaire = $form->getData();
            $app['orm.em']->persist($groupeSecondaire);
            $app['orm.em']->flush();
            $app['session']->getFlashBag()->add('success', 'Le groupe secondaire a été mis à jour.');

            return $app->redirect($app['url_generator']->generate('groupeSecondaire.admin.detail', ['groupe' => $groupeSecondaire->getId()]), 303);
        }

        return $app['twig']->render('admin/groupeSecondaire/materiel.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Impression de l'enveloppe du groupe secondaire.
     */
    public function materielPrintAction(Request $request, Application $app, SecondaryGroup $groupeSecondaire)
    {
        return $app['twig']->render('admin/groupeSecondaire/print.twig', [
            'groupeSecondaire' => $groupeSecondaire,
        ]);
    }

    /**
     * Impression de toutes les enveloppes groupe secondaire.
     */
    public function materielPrintAllAction(Request $request, Application $app)
    {
        $groupeSecondaires = $app['orm.em']->getRepository('\\'.\App\Entity\SecondaryGroup::class)->findAll();

        return $app['twig']->render('admin/groupeSecondaire/printAll.twig', [
            'groupeSecondaires' => $groupeSecondaires,
        ]);
    }

    /**
     * Met à jour un de groupe secondaire.
     */
    public function adminUpdateAction(Request $request, Application $app)
    {
        $groupeSecondaire = $request->get('groupe');

        $form = $app['form.factory']->createBuilder(new GroupeSecondaireForm(), $groupeSecondaire)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupeSecondaire = $form->getData();

            if ($form->get('update')->isClicked()) {
                /**
                 * Ajoute le responsable du groupe dans le groupe si il n'y est pas déjà.
                 */
                $personnage = $groupeSecondaire->getResponsable();
                if (!$groupeSecondaire->isMembre($personnage)) {
                    $membre = new \App\Entity\Membre();
                    $membre->setPersonnage($personnage);
                    $membre->setSecondaryGroup($groupeSecondaire);
                    $membre->setSecret(false);

                    $app['orm.em']->persist($membre);
                    $app['orm.em']->flush();

                    $groupeSecondaire->addMembre($membre);
                }
                /*
                 * Retire la candidature du responsable si elle existe
                 */
                foreach ($groupeSecondaire->getPostulants() as $postulant) {
                    if ($postulant->getPersonnage() == $personnage) {
                        $app['orm.em']->remove($postulant);
                    }
                }
                $app['orm.em']->persist($groupeSecondaire);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'Le groupe secondaire a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($groupeSecondaire);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'Le groupe secondaire a été supprimé.');
            }

            return $app->redirect($app['url_generator']->generate('groupeSecondaire.admin.list'));
        }

        return $app['twig']->render('admin/groupeSecondaire/update.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Construit le contexte pour la page détail de groupe secondaire (pour les orgas).
     *
     * @return array of
     */
    public function buildContextDetailTwig(Application $app, SecondaryGroup $groupeSecondaire, array $extraParameters = null): array
    {
        $gnActif = $app['larp.manager']->getGnActif();
        $result = [
            'groupeSecondaire' => $groupeSecondaire,
            'gn' => $gnActif,
        ];

        if (null == $extraParameters) {
            return $result;
        }

        return array_merge($result, $extraParameters);
    }

    /**
     * Détail d'un groupe secondaire (pour les orgas).
     */
    public function adminDetailAction(Request $request, Application $app)
    {
        $groupeSecondaire = $request->get('groupe');

        return $app['twig']->render('admin/groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire)
        );
    }

    /**
     * Ajoute un nouveau membre au groupe secondaire.
     */
    public function adminNewMembreAction(Request $request, Application $app, SecondaryGroup $groupeSecondaire)
    {
        $form = $app['form.factory']->createBuilder(new GroupeSecondaireNewMembreForm())->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $personnage = $form['personnage']->getData();
            // $personnage = $data['personnage'];

            $membre = new \App\Entity\Membre();

            if ($groupeSecondaire->isMembre($personnage)) {
                $app['session']->getFlashBag()->add('warning', 'le personnage est déjà membre du groupe secondaire.');

                return $app->redirect($app['url_generator']->generate('groupeSecondaire.admin.detail', ['groupe' => $groupeSecondaire->getId()]), 303);
            }

            $membre->setPersonnage($personnage);
            $membre->setSecondaryGroup($groupeSecondaire);
            $membre->setSecret(false);

            $app['orm.em']->persist($membre);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'le personnage a été ajouté au groupe secondaire.');

            return $app->redirect($app['url_generator']->generate('groupeSecondaire.admin.detail', ['groupe' => $groupeSecondaire->getId()]), 303);
        }

        return $app['twig']->render('admin/groupeSecondaire/newMembre.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire, ['form' => $form->createView()])
        );
    }

    /**
     * Retire un postulant du groupe.
     */
    public function adminRemovePostulantAction(Request $request, Application $app)
    {
        $groupeSecondaire = $request->get('groupe');
        $postulant = $request->get('postulant');

        $app['orm.em']->remove($postulant);
        $app['orm.em']->flush();

        $app['session']->getFlashBag()->add('success', 'la candidature a été supprimée.');

        return $app['twig']->render('admin/groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire)
        );
    }

    /**
     * Accepte un postulant dans le groupe.
     */
    public function adminAcceptPostulantAction(Request $request, Application $app)
    {
        $groupeSecondaire = $request->get('groupe');
        $postulant = $request->get('postulant');

        $personnage = $postulant->getPersonnage();

        $membre = new \App\Entity\Membre();
        $membre->setPersonnage($personnage);
        $membre->setSecondaryGroup($groupeSecondaire);
        $membre->setSecret(false);

        if ($groupeSecondaire->isMembre($personnage)) {
            $app['session']->getFlashBag()->add('warning', 'le personnage est déjà membre du groupe secondaire.');
        } else {
            $app['orm.em']->persist($membre);
            $app['orm.em']->remove($postulant);
            $app['orm.em']->flush();

            $app['User.mailer']->sendGroupeSecondaireAcceptMessage($personnage->getUser(), $groupeSecondaire);

            $app['session']->getFlashBag()->add('success', 'la candidature a été accepté.');
        }

        return $app['twig']->render('admin/groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire)
        );
    }

    /**
     * Retire un membre du groupe.
     */
    public function adminRemoveMembreAction(Request $request, Application $app)
    {
        $groupeSecondaire = $request->get('groupe');
        $membre = $request->get('membre');

        $app['orm.em']->remove($membre);
        $app['orm.em']->flush();

        $app['session']->getFlashBag()->add('success', 'le membre a été retiré.');

        return $app['twig']->render('admin/groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire)
        );
    }

    /**
     * Retirer le droit de lire les secrets à un utilisateur.
     *
     * @param Applicetion $app
     */
    public function adminSecretOffAction(Request $request, Application $app)
    {
        $groupeSecondaire = $request->get('groupe');
        $membre = $request->get('membre');

        $membre->setSecret(false);
        $app['orm.em']->persist($membre);
        $app['orm.em']->flush();

        return $app['twig']->render('admin/groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire)
        );
    }

    /**
     * Active le droit de lire les secrets à un utilisateur.
     *
     * @param Applicetion $app
     */
    public function adminSecretOnAction(Request $request, Application $app)
    {
        $groupeSecondaire = $request->get('groupe');
        $membre = $request->get('membre');

        $membre->setSecret(true);
        $app['orm.em']->persist($membre);
        $app['orm.em']->flush();

        return $app['twig']->render('admin/groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire)
        );
    }
}
