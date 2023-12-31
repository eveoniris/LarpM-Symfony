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

use App\Entity\Intrigue;
use App\Entity\IntrigueHasModification;
use App\Entity\Relecture;
use Doctrine\Common\Collections\ArrayCollection;
use JasonGrimes\Paginator;
use LarpManager\Form\Intrigue\IntrigueDeleteForm;
use LarpManager\Form\Intrigue\IntrigueFindForm;
use LarpManager\Form\Intrigue\IntrigueForm;
use LarpManager\Form\Intrigue\IntrigueRelectureForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\IntrigueController.
 *
 * @author kevin
 */
class IntrigueController extends AbstractController
{
    /**
     * Liste de toutes les intrigues.
     */
    public function listAction(Request $request, Application $app)
    {
        $order_by = $request->get('order_by') ?: 'titre';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $type = null;
        $value = null;

        $form = $app['form.factory']->createBuilder(new IntrigueFindForm())->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['search'];
        }

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Intrigue::class);

        $intrigues = $repo->findList(
            $type,
            $value,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset);

        $numResults = $repo->findCount($type, $value);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('intrigue.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $app['twig']->render('admin/intrigue/list.twig', [
            'form' => $form->createView(),
            'intrigues' => $intrigues,
            'paginator' => $paginator,
        ]);
    }

    /**
     * Lire une intrigue.
     */
    public function detailAction(Request $request, Application $app, Intrigue $intrigue)
    {
        return $app['twig']->render('admin/intrigue/detail.twig', [
            'intrigue' => $intrigue,
        ]);
    }

    /**
     * Ajouter une intrigue.
     */
    public function addAction(Request $request, Application $app)
    {
        $intrigue = new Intrigue();
        $form = $app['form.factory']->createBuilder(new IntrigueForm(), $intrigue)
            ->add('state', 'choice', [
                'required' => true,
                'label' => 'Etat',
                'choices' => $app['larp.manager']->getState(),
            ])
            ->add('add', 'submit', ['label' => "Ajouter l'intrigue"])->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $intrigue = $form->getData();

            if (!$intrigue->getDescription()) {
               $this->addFlash('error', 'la description de votre intrigue est obligatoire.');
            } elseif (!$intrigue->getText()) {
               $this->addFlash('error', 'le texte de votre intrigue est obligatoire.');
            } else {
                $intrigue->setUser($this->getUser());

                /*
                 * Pour tous les groupes de l'intrigue
                 */
                foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
                    $intrigueHasGroupe->setIntrigue($intrigue);
                }

                /*
                 * Pour tous les groupes secondaires de l'intrigue
                 */
                foreach ($intrigue->getIntrigueHasGroupeSecondaires() as $intrigueHasGroupeSecondaire) {
                    $intrigueHasGroupeSecondaire->setIntrigue($intrigue);
                }

                /*
                 * Pour tous les documents de l'intrigue
                 */
                foreach ($intrigue->getIntrigueHasDocuments() as $intrigueHasDocument) {
                    $intrigueHasDocument->setIntrigue($intrigue);
                }

                /*
                 * Pour tous les lieux de l'intrigue
                 */
                foreach ($intrigue->getIntrigueHasLieus() as $intrigueHasLieu) {
                    $intrigueHasLieu->setIntrigue($intrigue);
                }

                /*
                 * Pour tous les événements de l'intrigue
                 */
                foreach ($intrigue->getIntrigueHasEvenements() as $intrigueHasEvenement) {
                    $intrigueHasEvenement->setIntrigue($intrigue);
                }

                /*
                 * Pour tous les objectifs de l'intrigue
                 */
                foreach ($intrigue->getIntrigueHasObjectifs() as $intrigueHasObjectif) {
                    $intrigueHasObjectif->setIntrigue($intrigue);
                }

                $app['orm.em']->persist($intrigue);
                $app['orm.em']->flush();

                /*
                 * Envoyer une notification à tous les scénaristes des groupes concernés (hors utilisateur courant)
                 */
                foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
                    if (false == $this->getUser()->getGroupeScenariste()->contains($intrigueHasGroupe->getGroupe())) {
                        $app['notify']->intrigue($intrigue, $intrigueHasGroupe->getGroupe());
                    }
                }

               $this->addFlash('success', 'Votre intrigue a été ajouté.');

                return $this->redirectToRoute('intrigue.list', [], 303);
            }
        }

        return $app['twig']->render('admin/intrigue/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mettre à jour une intrigue.
     */
    public function updateAction(Request $request, Application $app, Intrigue $intrigue)
    {
        $originalIntrigueHasGroupes = new ArrayCollection();
        $originalIntrigueHasGroupeSecondaires = new ArrayCollection();
        $originalIntrigueHasEvenements = new ArrayCollection();
        $originalIntrigueHasObjectifs = new ArrayCollection();
        $originalIntrigueHasDocuments = new ArrayCollection();
        $originalIntrigueHasLieus = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets IntrigueHasGroupe de l'intrigue
         */
        foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
            $originalIntrigueHasGroupes->add($intrigueHasGroupe);
        }

        /*
         *  Crée un tableau contenant les objets IntrigueHasGroupeSecondaire de l'intrigue
         */
        foreach ($intrigue->getIntrigueHasGroupeSecondaires() as $intrigueHasGroupeSecondaire) {
            $originalIntrigueHasGroupeSecondaires->add($intrigueHasGroupeSecondaire);
        }

        /*
         *  Crée un tableau contenant les objets IntrigueHasEvenement de l'intrigue
         */
        foreach ($intrigue->getIntrigueHasEvenements() as $intrigueHasEvenement) {
            $originalIntrigueHasEvenements->add($intrigueHasEvenement);
        }

        /*
         *  Crée un tableau contenant les objets IntrigueHasObjectif de l'intrigue
         */
        foreach ($intrigue->getIntrigueHasObjectifs() as $intrigueHasObjectif) {
            $originalIntrigueHasObjectifs->add($intrigueHasObjectif);
        }

        /*
         *  Crée un tableau contenant les objets IntrigueHasDocument de l'intrigue
         */
        foreach ($intrigue->getIntrigueHasDocuments() as $intrigueHasDocument) {
            $originalIntrigueHasDocuments->add($intrigueHasDocument);
        }

        /*
         *  Crée un tableau contenant les objets IntrigueHasLieu de l'intrigue
         */
        foreach ($intrigue->getIntrigueHasLieus() as $intrigueHasLieu) {
            $originalIntrigueHasLieus->add($intrigueHasLieu);
        }

        $form = $app['form.factory']->createBuilder(new IntrigueForm(), $intrigue)
            ->add('state', 'choice', [
                'required' => true,
                'label' => 'Etat',
                'choices' => $app['larp.manager']->getState(),
            ])
            ->add('enregistrer', 'submit', ['label' => 'Enregistrer'])->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $intrigue = $form->getData();
            $intrigue->setDateUpdate(new \DateTime('NOW'));

            /*
             * Pour tous les groupes de l'intrigue
             */
            foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
                $intrigueHasGroupe->setIntrigue($intrigue);
            }

            /*
             * Pour tous les groupes secondaires de l'intrigue
             */
            foreach ($intrigue->getIntrigueHasGroupeSecondaires() as $intrigueHasGroupeSecondaire) {
                $intrigueHasGroupeSecondaire->setIntrigue($intrigue);
            }

            /*
             * Pour tous les événements de l'intrigue
             */
            foreach ($intrigue->getIntrigueHasEvenements() as $intrigueHasEvenement) {
                $intrigueHasEvenement->setIntrigue($intrigue);
            }

            /*
             * Pour tous les objectifs de l'intrigue
             */
            foreach ($intrigue->getIntrigueHasObjectifs() as $intrigueHasObjectif) {
                $intrigueHasObjectif->setIntrigue($intrigue);
            }

            /*
             * Pour tous les documents de l'intrigue
             */
            foreach ($intrigue->getIntrigueHasDocuments() as $intrigueHasDocument) {
                $intrigueHasDocument->setIntrigue($intrigue);
            }

            /*
             * Pour tous les lieus de l'intrigue
             */
            foreach ($intrigue->getIntrigueHasLieus() as $intrigueHasLieu) {
                $intrigueHasLieu->setIntrigue($intrigue);
            }

            /*
             *  supprime la relation entre intrigueHasGroupe et l'intrigue
             */
            foreach ($originalIntrigueHasGroupes as $intrigueHasGroupe) {
                if (false == $intrigue->getIntrigueHasGroupes()->contains($intrigueHasGroupe)) {
                    $app['orm.em']->remove($intrigueHasGroupe);
                }
            }

            /*
             *  supprime la relation entre intrigueHasGroupe et l'intrigue
             */
            foreach ($originalIntrigueHasGroupeSecondaires as $intrigueHasGroupeSecondaire) {
                if (false == $intrigue->getIntrigueHasGroupes()->contains($intrigueHasGroupeSecondaire)) {
                    $app['orm.em']->remove($intrigueHasGroupeSecondaire);
                }
            }

            /*
             *  supprime la relation entre intrigueHasEvenement et l'intrigue
             */
            foreach ($originalIntrigueHasEvenements as $intrigueHasEvenement) {
                if (false == $intrigue->getIntrigueHasEvenements()->contains($intrigueHasEvenement)) {
                    $app['orm.em']->remove($intrigueHasEvenement);
                }
            }

            /*
             *  supprime la relation entre intrigueHasObjectif et l'intrigue
             */
            foreach ($originalIntrigueHasObjectifs as $intrigueHasObjectif) {
                if (false == $intrigue->getIntrigueHasObjectifs()->contains($intrigueHasObjectif)) {
                    $app['orm.em']->remove($intrigueHasObjectif);
                }
            }

            /*
             *  supprime la relation entre intrigueHasDocument et l'intrigue
             */
            foreach ($originalIntrigueHasDocuments as $intrigueHasDocument) {
                if (false == $intrigue->getIntrigueHasDocuments()->contains($intrigueHasDocument)) {
                    $app['orm.em']->remove($intrigueHasDocument);
                }
            }

            /*
             *  supprime la relation entre intrigueHasLieu et l'intrigue
             */
            foreach ($originalIntrigueHasLieus as $intrigueHasLieu) {
                if (false == $intrigue->getIntrigueHasLieus()->contains($intrigueHasLieu)) {
                    $app['orm.em']->remove($intrigueHasLieu);
                }
            }

            /**
             * Création d'une ligne dans la liste des modifications de l'intrigue.
             */
            $modification = new IntrigueHasModification();
            $modification->setUser($this->getUser());
            $modification->setIntrigue($intrigue);
            $app['orm.em']->persist($modification);
            $app['orm.em']->persist($intrigue);
            $app['orm.em']->flush();

            /*
             * Envoyer une notification à tous les scénaristes des groupes concernés (hors utilisateur courant)
             */
            foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
                if (false == $this->getUser()->getGroupeScenariste()->contains($intrigueHasGroupe->getGroupe())) {
                    $app['notify']->intrigue($intrigue, $intrigueHasGroupe->getGroupe());
                }
            }

            /*
             * Envoyer une notification à tous les utilisateurs ayant préalablement modifier cette intrigue (hors utilisateur courant, et hors scénariste d'un groupe concerné)
             */
            foreach ($intrigue->getIntrigueHasModifications() as $modification) {
                if ($modification->getUser() != $this->getUser()) {
                    $sendNotification = true;
                    foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
                        if (true == $modification->getUser()->getGroupeScenariste()->contains($intrigueHasGroupe->getGroupe())) {
                            $sendNotification = false;
                        }
                    }

                    if ($sendNotification) {
                        $app['notify']->intrigue($intrigue, $intrigueHasGroupe->getGroupe());
                    }
                }
            }

           $this->addFlash('success', 'Votre intrigue a été modifiée.');

            return $this->redirectToRoute('intrigue.detail', ['intrigue' => $intrigue->getId()], [], 303);
        }

        return $app['twig']->render('admin/intrigue/update.twig', [
            'form' => $form->createView(),
            'intrigue' => $intrigue,
        ]);
    }

    /**
     * Supression d'une intrigue.
     */
    public function deleteAction(Request $request, Application $app, Intrigue $intrigue)
    {
        $form = $app['form.factory']->createBuilder(new IntrigueDeleteForm(), $intrigue)
            ->add('supprimer', 'submit', ['label' => 'Supprimer'])->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $intrigue = $form->getData();
            $app['orm.em']->remove($intrigue);
            $app['orm.em']->flush();

           $this->addFlash('success', 'L\'intrigue a été supprimée.');

            return $this->redirectToRoute('intrigue.list', [], 303);
        }

        return $app['twig']->render('admin/intrigue/delete.twig', [
            'form' => $form->createView(),
            'intrigue' => $intrigue,
        ]);
    }

    /**
     * Ajout d'une relecture.
     */
    public function relectureAddAction(Request $request, Application $app, Intrigue $intrigue)
    {
        $relecture = new Relecture();
        $form = $app['form.factory']->createBuilder(new IntrigueRelectureForm(), $relecture)
            ->add('enregistrer', 'submit', ['label' => 'Enregistrer'])->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $relecture = $form->getData();
            $relecture->setUser($this->getUser());
            $relecture->setIntrigue($intrigue);

            $app['orm.em']->persist($relecture);
            $app['orm.em']->flush();

            /*
             * Envoyer une notification à tous les scénaristes des groupes concernés (hors utilisateur courant)
             */
            foreach ($intrigue->getIntrigueHasGroupes() as $intrigueHasGroupe) {
                if (false == $this->getUser()->getGroupeScenariste()->contains($intrigueHasGroupe->getGroupe())) {
                    $app['notify']->relecture($intrigue, $intrigueHasGroupe->getGroupe());
                }
            }

           $this->addFlash('success', 'Votre relecture a été enregistrée.');

            return $this->redirectToRoute('intrigue.detail', ['intrigue' => $intrigue->getId()], [], 303);
        }

        return $app['twig']->render('admin/intrigue/relecture.twig', [
            'form' => $form->createView(),
            'intrigue' => $intrigue,
        ]);
    }
}
