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

use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use LarpManager\Form\GroupeGn\GroupeGnForm;
use LarpManager\Form\GroupeGn\GroupeGnOrdreForm;
use LarpManager\Form\GroupeGn\GroupeGnPlaceAvailableForm;
use LarpManager\Form\GroupeGn\GroupeGnResponsableForm;
use LarpManager\Repository\ParticipantRepository;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LarpManager\Controllers\GroupeGnController.
 *
 * @author kevin
 */
class GroupeGnController
{
    /**
     * Liste des sessions de jeu pour un groupe.
     */
    public function listAction(Request $request, Application $app, Groupe $groupe)
    {
        return $app['twig']->render('admin/groupeGn/list.twig', [
            'groupe' => $groupe,
        ]);
    }

    /**
     * Ajout d'un groupe à un jeu.
     */
    public function addAction(Request $request, Application $app, Groupe $groupe)
    {
        $groupeGn = new GroupeGn();
        $groupeGn->setGroupe($groupe);

        // Si ce groupe a déjà une participation à un jeu, reprendre le code/jeuStrategique/jeuMaritime/placeAvailable
        if ($groupe->getGroupeGns()->count() > 0) {
            $jeu = $groupe->getGroupeGns()->last();
            $groupeGn->setCode($jeu->getCode());
            $groupeGn->setPlaceAvailable($jeu->getPlaceAvailable());
            $groupeGn->setJeuStrategique($jeu->getJeuStrategique());
            $groupeGn->setJeuMaritime($jeu->getJeuMaritime());
        }

        $form = $app['form.factory']->createBuilder(new GroupeGnForm(), $groupeGn)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupeGn = $form->getData();
            $app['orm.em']->persist($groupeGn);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La participation au jeu a été enregistré.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupeGn/add.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modification de la participation à un jeu du groupe.
     */
    public function updateAction(Request $request, Application $app, Groupe $groupe, GroupeGn $groupeGn)
    {
        $form = $app['form.factory']->createBuilder(new GroupeGnForm(), $groupeGn)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupeGn = $form->getData();
            $app['orm.em']->persist($groupeGn);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La participation au jeu a été enregistré.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupeGn/update.twig', [
            'groupe' => $groupe,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Choisir le responsable.
     */
    public function responsableAction(Request $request, Application $app, Groupe $groupe, GroupeGn $groupeGn)
    {
        $form = $app['form.factory']->createBuilder(new GroupeGnResponsableForm(), $groupeGn)
            ->add('responsable', 'entity', [
                'label' => 'Responsable',
                'required' => false,
                'class' => \App\Entity\Participant::class,
                'property' => 'User.etatCivil',
                'query_builder' => static function (ParticipantRepository $er) use ($groupeGn) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->join('p.User', 'u');
                    $qb->join('p.groupeGn', 'gg');
                    $qb->join('u.etatCivil', 'ec');
                    $qb->orderBy('ec.nom', 'ASC');
                    $qb->where('gg.id = :groupeGnId');
                    $qb->setParameter('groupeGnId', $groupeGn->getId());

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Responsable',
                ],
            ])
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupeGn = $form->getData();
            $app['orm.em']->persist($groupeGn);
            $app['orm.em']->flush();

            $app['notify']->newResponsable($groupeGn->getResponsable()->getUser(), $groupeGn);

            $app['session']->getFlashBag()->add('success', 'Le responsable du groupe a été enregistré.');

            return $app->redirect($app['url_generator']->generate('groupeGn.list', ['groupe' => $groupeGn->getGroupe()->getId()]));
        }

        return $app['twig']->render('admin/groupeGn/responsable.twig', [
            'groupe' => $groupe,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un participant à un groupe.
     */
    public function participantAddAction(Request $request, Application $app, GroupeGn $groupeGn)
    {
        $form = $app['form.factory']->createBuilder()
            ->add('participant', 'entity', [
                'label' => 'Nouveau participant',
                'required' => true,
                'class' => \App\Entity\Participant::class,
                'property' => 'User.etatCivil',
                'query_builder' => static function (ParticipantRepository $er) use ($groupeGn) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->join('p.User', 'u');
                    $qb->join('p.gn', 'gn');
                    $qb->join('u.etatCivil', 'ec');
                    $qb->where($qb->expr()->isNull('p.groupeGn'));
                    $qb->andWhere('gn.id = :gnId');
                    $qb->setParameter('gnId', $groupeGn->getGn()->getId());
                    $qb->orderBy('ec.nom', 'ASC');

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Participant',
                ],
            ])
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $data['participant']->setGroupeGn($groupeGn);
            $app['orm.em']->persist($data['participant']);
            $app['orm.em']->flush();

            $app['notify']->newMembre($data['participant']->getUser(), $groupeGn);

            $app['session']->getFlashBag()->add('success', 'Le joueur a été ajouté à cette session.');

            return $app->redirect($app['url_generator']->generate('groupeGn.list', ['groupe' => $groupeGn->getGroupe()->getId()]));
        }

        return $app['twig']->render('admin/groupeGn/participantAdd.twig', [
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un participant à un groupe (pour les chefs de groupe).
     */
    public function joueurAddAction(Request $request, Application $app, GroupeGn $groupeGn)
    {
        $participant = $app['User']->getParticipant($groupeGn->getGn());

        $form = $app['form.factory']->createBuilder()
            ->add('participant', 'entity', [
                'label' => 'Choisissez le nouveau membre de votre groupe',
                'required' => false,
                'class' => \App\Entity\Participant::class,
                'property' => 'User.Username',
                'query_builder' => static function (ParticipantRepository $er) use ($groupeGn) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->join('p.User', 'u');
                    $qb->join('p.gn', 'gn');
                    $qb->join('u.etatCivil', 'ec');
                    $qb->where($qb->expr()->isNull('p.groupeGn'));
                    $qb->andWhere('gn.id = :gnId');
                    $qb->setParameter('gnId', $groupeGn->getGn()->getId());
                    $qb->orderBy('u.Username', 'ASC');

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Participant',
                ],
            ])
            ->add('submit', 'submit', ['label' => 'Ajouter le joueur choisi'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            if ($data['participant']) {
                $data['participant']->setGroupeGn($groupeGn);
                $app['orm.em']->persist($data['participant']);
                $app['orm.em']->flush();

                $app['notify']->newMembre($data['participant']->getUser(), $groupeGn);

                $app['session']->getFlashBag()->add('success', 'Le joueur a été ajouté à votre groupe.');
            }

            return $app->redirect($app['url_generator']->generate('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]));
        }

        return $app['twig']->render('public/groupeGn/add.twig', [
            'groupeGn' => $groupeGn,
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retire un participant d'un groupe.
     */
    public function participantRemoveAction(Request $request, Application $app, GroupeGn $groupeGn, Participant $participant)
    {
        $form = $app['form.factory']->createBuilder()
            ->add('submit', 'submit', ['label' => 'Retirer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // si le participant est le chef de groupe
            if ($groupeGn->getResponsable() == $participant) {
                $groupeGn->setResponsableNull();
            }

            $participant->setGroupeGnNull();
            $app['orm.em']->persist($participant);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le joueur a été retiré de cette session.');

            return $app->redirect($app['url_generator']->generate('groupeGn.list', ['groupe' => $groupeGn->getGroupe()->getId()]));
        }

        return $app['twig']->render('admin/groupeGn/participantRemove.twig', [
            'groupeGn' => $groupeGn,
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Permet au chef de groupe de modifier le nombre de place disponible.
     */
    public function placeAvailableAction(Request $request, Application $app, GroupeGn $groupeGn)
    {
        $participant = $app['User']->getParticipant($groupeGn->getGn());

        $form = $app['form.factory']->createBuilder(new GroupeGnPlaceAvailableForm(), $groupeGn)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupeGn = $form->getData();
            $app['orm.em']->persist($groupeGn);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Vos modifications ont été enregistré.');

            return $app->redirect($app['url_generator']->generate('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]));
        }

        return $app['twig']->render('public/groupeGn/placeAvailable.twig', [
            'form' => $form->createView(),
            'groupe' => $groupeGn->getGroupe(),
            'participant' => $participant,
            'groupeGn' => $groupeGn,
        ]);
    }

    /**
     * Détail d'un groupe.
     */
    #[Route('/groupeGn/groupe', name: 'groupeGn.groupe')]
    public function groupeAction(Request $request, Application $app, GroupeGn $groupeGn)
    {
        $participant = $app['User']->getParticipant($groupeGn->getGn());

        return $app['twig']->render('public/groupe/detail.twig', [
            'groupe' => $groupeGn->getGroupe(),
            'participant' => $participant,
            'groupeGn' => $groupeGn,
        ]);
    }

    /**
     * Modification du jeu de domaine du groupe.
     */
    public function jeudedomaineAction(Request $request, Application $app, Groupe $groupe, GroupeGn $groupeGn)
    {
        $form = $app['form.factory']->createBuilder(new GroupeGnOrdreForm(), $groupeGn, ['groupeGnId' => $groupeGn->getId()])
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupeGn = $form->getData();
            $app['orm.em']->persist($groupeGn);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le jeu de domaine a été enregistré.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupeGn/jeudedomaine.twig', [
            'groupe' => $groupe,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }
}
