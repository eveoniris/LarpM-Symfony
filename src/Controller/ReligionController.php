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

use App\Repository\ReligionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use LarpManager\Form\Religion\ReligionBlasonForm;
use LarpManager\Form\Religion\ReligionForm;
use LarpManager\Form\Religion\ReligionLevelForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LarpManager\Controllers\ReligionController.
 *
 * @author kevin
 */
class ReligionController extends AbstractController
{
    /**
     * Liste des perso ayant cette religion.
     */
    public function persoAction(Request $request, Application $app)
    {
        $religion = $request->get('religion');

        return $app['twig']->render('admin/religion/perso.twig', ['religion' => $religion]);
    }

    /**
     * affiche la liste des religions.
     */
    #[Route('/religion', name: 'religion')]
    public function indexAction(Request $request, ReligionRepository $religionRepository): Response
    {
        /*
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Religion::class);
        if ($app['security.authorization_checker']->isGranted('ROLE_REGLE')) {
            $religions = $repo->findAllOrderedByLabel();
        } else {
            $religions = $repo->findAllPublicOrderedByLabel();
        }

        return $app['twig']->render('religion/list.twig', ['religions' => $religions]);
        */

        $page = $request->query->getInt('page', 1);
        $orderBy = $request->query->getString('order_by', 'id');
        $orderDir = $request->query->getString('order_dir', 'ASC');
        $limit = 10;
        
        if (in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true)) {
            $where = '1=1';
        } else {
            $where = 'r.secret = 0';
        }
        

        $paginator = $religionRepository->findPaginated($page, $limit, $orderBy, $orderDir, $where);
        
        return $this->render(
            'religion\list.twig',
            [
                'paginator' => $paginator,
                'limit' => $limit,
                'page' => $page
            ]
        );
    }

    /**
     * affiche la liste des religions.
     */
    public function mailAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Religion::class);
        $religions = $repo->findAllOrderedByLabel();

        return $app['twig']->render('admin/religion/mail.twig', ['religions' => $religions]);
    }

    /**
     * Detail d'une religion.
     */
    public function detailAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $religion = $app['orm.em']->find('\\'.\App\Entity\Religion::class, $id);

        return $app['twig']->render('admin/religion/detail.twig', ['religion' => $religion]);
    }

    /**
     * Ajoute une religion.
     */
    public function addAction(Request $request, Application $app)
    {
        $religion = new \App\Entity\Religion();

        $form = $app['form.factory']->createBuilder(new ReligionForm(), $religion)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        // si l'utilisateur soumet une nouvelle religion
        if ($form->isValid()) {
            $religion = $form->getData();

            /**
             * Création du topic associés à cette religion
             * Ce topic doit être placé dans le topic "culte".
             *
             * @var \App\Entity\Topic $topic
             */
            $topic = new \App\Entity\Topic();
            $topic->setTitle($religion->getLabel());
            $topic->setDescription($religion->getDescription());
            $topic->setUser($app['User']);
            $topic->setTopic($app['larp.manager']->findTopic('TOPIC_CULTE'));
            $topic->setRight('CULTE');

            $app['orm.em']->persist($topic);
            $app['orm.em']->flush();

            $religion->setTopic($topic);
            $app['orm.em']->persist($religion);
            $app['orm.em']->flush();

            $topic->setObjectId($religion->getId());
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La religion a été ajoutée.');

            // l'utilisateur est redirigé soit vers la liste des religions, soit vers de nouveau
            // vers le formulaire d'ajout d'une religion
            if ($form->get('save')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('religion'), 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('religion.add'), 303);
            }
        }

        return $app['twig']->render('admin/religion/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie une religion. Si l'utilisateur clique sur "sauvegarder", la religion est sauvegardée et
     * l'utilisateur est redirigé vers la liste des religions.
     * Si l'utilisateur clique sur "supprimer", la religion est supprimée et l'utilisateur est
     * redirigé vers la liste des religions.
     */
    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $religion = $app['orm.em']->find('\\'.\App\Entity\Religion::class, $id);

        $form = $app['form.factory']->createBuilder(new ReligionForm(), $religion)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $originalSpheres = new ArrayCollection();
        foreach ($religion->getSpheres() as $sphere) {
            $originalSpheres->add($sphere);
        }

        $form->handleRequest($request);
        if ($form->isValid()) {
            $religion = $form->getData();

            if ($form->get('update')->isClicked()) {
                foreach ($religion->getSpheres() as $sphere) {
                    if (false == $sphere->getReligions()->contains($religion)) {
                        $sphere->addReligion($religion);
                    }
                }
                foreach ($originalSpheres as $sphere) {
                    if (false == $religion->getspheres()->contains($sphere)) {
                        $sphere->removeReligion($religion);
                    }
                }
                $app['orm.em']->persist($religion);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'La religion a été mise à jour.');

                return $app->redirect($app['url_generator']->generate('religion.detail', ['index' => $id]), 303);
            } elseif ($form->get('delete')->isClicked()) {
                /*$app['orm.em']->remove($religion);
                        $app['orm.em']->flush();
                        $app['session']->getFlashBag()->add('success', 'La religion a été supprimée.');*/
                return $app->redirect($app['url_generator']->generate('religion'), 303);
            }
        }

        return $app['twig']->render('admin/religion/update.twig', [
            'religion' => $religion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour le blason d'une religion.
     */
    public function updateBlasonAction(Request $request, Application $app)
    {
        $religion = $request->get('religion');

        $form = $app['form.factory']->createBuilder(new ReligionBlasonForm(), $religion)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $files = $request->files->get($form->getName());

            $path = __DIR__.'/../../../private/img/blasons/';
            $filename = $files['blason']->getClientOriginalName();
            $extension = $files['blason']->guessExtension();

            if (!$extension || !in_array($extension, ['png', 'jpg', 'jpeg', 'bmp'])) {
                $app['session']->getFlashBag()->add('error', 'Désolé, votre image ne semble pas valide (vérifiez le format de votre image)');

                return $app->redirect($app['url_generator']->generate('religion.detail', ['index' => $religion->getId()]), 303);
            }

            $blasonFilename = hash('md5', $app['User']->getUsername().$filename.time()).'.'.$extension;

            $image = $app['imagine']->open($files['blason']->getPathname());
            $image->resize($image->getSize()->widen(160));
            $image->save($path.$blasonFilename);

            $religion->setBlason($blasonFilename);
            $app['orm.em']->persist($religion);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le blason a été enregistré');

            return $app->redirect($app['url_generator']->generate('religion.detail', ['index' => $religion->getId()]), 303);
        }

        return $app['twig']->render('admin/religion/blason.twig', [
            'religion' => $religion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * affiche la liste des niveau de fanatisme.
     */
    public function levelIndexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\ReligionLevel::class);
        $religionLevels = $repo->findAllOrderedByIndex();

        return $app['twig']->render('admin/religion/level/index.twig', ['religionLevels' => $religionLevels]);
    }

    /**
     * Detail d'un niveau de fanatisme.
     */
    public function levelDetailAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $religionLevel = $app['orm.em']->find('\\'.\App\Entity\ReligionLevel::class, $id);

        return $app['twig']->render('admin/religion/level/detail.twig', ['religionLevel' => $religionLevel]);
    }

    /**
     * Ajoute un niveau de fanatisme.
     */
    public function levelAddAction(Request $request, Application $app)
    {
        $religionLevel = new \App\Entity\ReligionLevel();

        $form = $app['form.factory']->createBuilder(new ReligionLevelForm(), $religionLevel)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        // si l'utilisateur soumet une nouvelle religion
        if ($form->isValid()) {
            $religionLevel = $form->getData();

            $app['orm.em']->persist($religionLevel);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le niveau de religion a été ajoutée.');

            // l'utilisateur est redirigé soit vers la liste des niveaux de religions, soit vers de nouveau
            // vers le formulaire d'ajout d'un niveau de religion
            if ($form->get('save')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('religion.level'), 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('religion.level.add'), 303);
            }
        }

        return $app['twig']->render('admin/religion/level/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie un niveau de religion. Si l'utilisateur clique sur "sauvegarder", le niveau de religion est sauvegardée et
     * l'utilisateur est redirigé vers la liste des niveaux de religions.
     * Si l'utilisateur clique sur "supprimer", le niveau religion est supprimée et l'utilisateur est
     * redirigé vers la liste de niveaux de religions.
     */
    public function levelUpdateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $religionLevel = $app['orm.em']->find('\\'.\App\Entity\ReligionLevel::class, $id);

        $form = $app['form.factory']->createBuilder(new ReligionLevelForm(), $religionLevel)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $religionLevel = $form->getData();

            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($religionLevel);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'Le niveau de religion a été mise à jour.');

                return $app->redirect($app['url_generator']->generate('religion.level.detail', ['index' => $id]), 303);
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($religionLevel);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'Le niveau de religion a été supprimée.');

                return $app->redirect($app['url_generator']->generate('religion.level'), 303);
            }
        }

        return $app['twig']->render('admin/religion/level/update.twig', [
            'religionLevel' => $religionLevel,
            'form' => $form->createView(),
        ]);
    }
}
