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
use App\Entity\Religion;
use App\Form\Religion\ReligionBlasonForm;
use App\Form\Religion\ReligionForm;
use App\Form\Religion\ReligionLevelForm;
use App\Repository\TopicRepository;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

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
    public function persoAction(Request $request): Response
    {
        $religion = $request->get('religion');

        return $this->render(
            'admin/religion/perso.twig', 
            [
                'religion' => $religion
            ]
        );
    }

    /**
     * affiche la liste des religions.
     */
    #[Route('/religion', name: 'religion')]
    public function indexAction(Request $request, ReligionRepository $religionRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $orderBy = $request->query->getString('order_by', 'id');
        $orderDir = $request->query->getString('order_dir', 'ASC');
        $limit = 10;
        
        if ($this->isGranted('ROLE_REGLE')) {
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
    public function mailAction(Request $request, ReligionRepository $religionRepository): Response
    {
        $religions = $religionRepository->findAllOrderedByLabel();

        return $this->render(
            'admin/religion/mail.twig', 
            [
                'religions' => $religions
            ]
        );
    }

    /**
     * Detail d'une religion.
     */
    #[Route('/religion/{id}')]
    public function detailAction(Religion $religion): Response
    {
        return $this->render(
            'admin/religion/detail.twig', 
            [
                'religion' => $religion
            ]
        );
    }

    /**
     * Ajoute une religion.
     */
    public function addAction(EntityManagerInterface $entityManager, Request $request, TopicRepository $topicRepository): Response
    {
        $religion = new Religion();

        $form = $this->createForm(ReligionForm::class, $religion)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
        ;

        $form->handleRequest($request);

        // si l'utilisateur soumet une nouvelle religion
        if ($form->isSubmitted() && $form->isValid()) {
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
            $topic->setUser($this->getUser());
            $topic->setTopic($topicRepository->findOneBy(['kay' => 'TOPIC_CULTE']));
            $topic->setRight('CULTE');

            $entityManager->persist($topic);
            $entityManager->flush();

            $religion->setTopic($topic);
            $entityManager->persist($religion);
            $entityManager->flush();

            $topic->setObjectId($religion->getId());
            $entityManager->flush();

            $this->addFlash('success', 'La religion a été ajoutée.');

            // l'utilisateur est redirigé soit vers la liste des religions, soit vers de nouveau
            // vers le formulaire d'ajout d'une religion
            if ($form->get('save')->isClicked()) {
                //return $app->redirect($app['url_generator']->generate('religion'), 303);
                return $this->redirectToRoute('religion', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                //return $app->redirect($app['url_generator']->generate('religion.add'), 303);
                return $this->redirectToRoute('religion.add', [], 303);
            }
        }

        return $this->render(
            'admin/religion/add.twig', 
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Modifie une religion. Si l'utilisateur clique sur "sauvegarder", la religion est sauvegardée et
     * l'utilisateur est redirigé vers la liste des religions.
     * Si l'utilisateur clique sur "supprimer", la religion est supprimée et l'utilisateur est
     * redirigé vers la liste des religions.
     */
    #[Route('/religion/edit/{id}', name: 'religion_edit')]
    public function updateAction(EntityManagerInterface $entityManager, Request $request, int $id)
    {
        $religion = $entityManager->getRepository(Religion::class)->find($id);

        $form = $this->createForm(ReligionForm::class, $religion)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
        ;

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
                $entityManager->persist($religion);
                $entityManager->flush();
                $this->addFlash('success', 'La religion a été mise à jour.');

                //return $app->redirect($app['url_generator']->generate('religion.detail', ['index' => $id]), 303);
                return $this->redirectToRoute('religion.detail', [], 303);
            } elseif ($form->get('delete')->isClicked()) {
                /*$app['orm.em']->remove($religion);
                $app['orm.em']->flush();
                $this->addFlash('success', 'La religion a été supprimée.');*/
                //return $app->redirect($app['url_generator']->generate('religion'), 303);
                return $this->redirectToRoute('religion', [], 303);
            }
        }

        return $this->render(
            'admin/religion/update.twig', 
            [
                'religion' => $religion,
                'form' => $form->createView(),
            ]
        );
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
                $this->addFlash('error', 'Désolé, votre image ne semble pas valide (vérifiez le format de votre image)');

                return $app->redirect($app['url_generator']->generate('religion.detail', ['index' => $religion->getId()]), 303);
            }

            $blasonFilename = hash('md5', $app['User']->getUsername().$filename.time()).'.'.$extension;

            $image = $app['imagine']->open($files['blason']->getPathname());
            $image->resize($image->getSize()->widen(160));
            $image->save($path.$blasonFilename);

            $religion->setBlason($blasonFilename);
            $app['orm.em']->persist($religion);
            $app['orm.em']->flush();

            $this->addFlash('success', 'Le blason a été enregistré');

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

            $this->addFlash('success', 'Le niveau de religion a été ajoutée.');

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
                $this->addFlash('success', 'Le niveau de religion a été mise à jour.');

                return $app->redirect($app['url_generator']->generate('religion.level.detail', ['index' => $id]), 303);
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($religionLevel);
                $app['orm.em']->flush();
                $this->addFlash('success', 'Le niveau de religion a été supprimée.');

                return $app->redirect($app['url_generator']->generate('religion.level'), 303);
            }
        }

        return $app['twig']->render('admin/religion/level/update.twig', [
            'religionLevel' => $religionLevel,
            'form' => $form->createView(),
        ]);
    }
}
