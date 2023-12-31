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

use App\Entity\Background;
use JasonGrimes\Paginator;
use LarpManager\Form\BackgroundDeleteForm;
use LarpManager\Form\BackgroundFindForm;
use LarpManager\Form\BackgroundForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\BackgroundController.
 *
 * @author kevin
 */
class BackgroundController extends AbstractController
{
    /**
     * Présentation des backgrounds.
     */
    public function listAction(Request $request, Application $app)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $form = $app['form.factory']->createBuilder(new BackgroundFindForm())
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

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Background::class);
        $backgrounds = $repo->findBy(
            $criteria,
            [$order_by => $order_dir],
            $limit,
            $offset);

        $numResults = $repo->findCount($criteria);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('background.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $app['twig']->render('admin/background/list.twig', [
            'backgrounds' => $backgrounds,
            'paginator' => $paginator,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Impression de tous les backgrounds de groupe.
     */
    public function printAction(Request $request, Application $app)
    {
        $gns = $app['orm.em']->getRepository('\\'.\App\Entity\Gn::class)->findActive();
        if (0 == count($gns)) {
            echo 'Erreur : Aucun GN actif trouvé. Veuillez activer le GN en préparation.';
            exit;
        } elseif (count($gns) > 1) {
            echo "Erreur : Il ne peut pas y avoir plus d'un GN actif à la fois. Merci de désactiver le GN précédent.";
            exit;
        }

        $backgrounds = $app['orm.em']->getRepository('\\'.\App\Entity\Background::class)->findBackgrounds($gns[0]->getId());

        return $app['twig']->render('admin/background/print.twig', [
            'backgrounds' => $backgrounds,
        ]);
    }

    /**
     * Impression de tous les backgrounds de personnage.
     */
    public function personnagePrintAction(Request $request, Application $app)
    {
        $gns = $app['orm.em']->getRepository('\\'.\App\Entity\Gn::class)->findActive();
        if (0 == count($gns)) {
            echo 'Erreur : Aucun GN actif trouvé. Veuillez activer le GN en préparation.';
            exit;
        } elseif (count($gns) > 1) {
            echo "Erreur : Il ne peut pas y avoir plus d'un GN actif à la fois. Merci de désactiver le GN précédent.";
            exit;
        }

        $groupeGns = $app['orm.em']->getRepository('\\'.\App\Entity\GroupeGn::class)->findByGn($gns[0]->getId());

        return $app['twig']->render('admin/background/personnagePrint.twig', [
            'groupeGns' => $groupeGns,
        ]);
    }

    /**
     * Ajout d'un background.
     */
    public function addAction(Request $request, Application $app)
    {
        $background = new \App\Entity\Background();
        $groupeId = $request->get('groupe');

        if ($groupeId) {
            $groupe = $app['orm.em']->find('\\'.\App\Entity\Groupe::class, $groupeId);
            if ($groupe) {
                $background->setGroupe($groupe);
            }
        }

        $form = $app['form.factory']->createBuilder(new BackgroundForm(), $background)
            ->add('visibility', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getVisibility(),
            ])
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $background = $form->getData();
            $background->setUser($this->getUser());

            $app['orm.em']->persist($background);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le background a été ajouté.');

            return $this->redirectToRoute('groupe.detail', ['index' => $background->getGroupe()->getId()], [], 303);
        }

        return $app['twig']->render('admin/background/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un background.
     */
    public function deleteAction(Request $request, Application $app, Background $background)
    {
        $form = $app['form.factory']->createBuilder(new BackgroundDeleteForm(), $background)
            ->add('save', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $background = $form->getData();
            $app['orm.em']->remove($background);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le background a été supprimé.');

            return $this->redirectToRoute('groupe.detail', ['index' => $background->getGroupe()->getId()], [], 303);
        }

        return $app['twig']->render('admin/background/delete.twig', [
            'form' => $form->createView(),
            'background' => $background,
        ]);
    }

    /**
     * Mise à jour d'un background.
     */
    public function updateAction(Request $request, Application $app, Background $background)
    {
        $form = $app['form.factory']->createBuilder(new BackgroundForm(), $background)
            ->add('visibility', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getVisibility(),
            ])
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $background = $form->getData();
            $background->setUpdateDate(new \DateTime('NOW'));

            $app['orm.em']->persist($background);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le background a été ajouté.');

            return $this->redirectToRoute('groupe.detail', ['index' => $background->getGroupe()->getId()], [], 303);
        }

        return $app['twig']->render('admin/background/update.twig', [
            'form' => $form->createView(),
            'background' => $background,
        ]);
    }

    /**
     * Détail d'un background.
     */
    public function detailAction(Request $request, Application $app, Background $background)
    {
        return $app['twig']->render('admin/background/detail.twig', [
            'background' => $background,
        ]);
    }
}
