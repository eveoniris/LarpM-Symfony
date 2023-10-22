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

use Doctrine\Common\Collections\ArrayCollection;
use LarpManager\Form\PostDeleteForm;
use LarpManager\Form\PostForm;
use LarpManager\Form\TopicForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LarpManager\Controllers\ForumController.
 *
 * @author kevin
 */
class ForumController
{
    /**
     * Liste des forums de premier niveau.
     */
    #[Route('/forum', name: 'forum')]
    public function forumAction(Request $request, Application $app)
    {
        if (null == $app['User']) {
            return $app->redirect($app['url_generator']->generate('User.login', 303));
        }

        $topics = $app['orm.em']->getRepository('\\'.\App\Entity\Topic::class)
            ->findAllRoot();

        // rechercher tous les nouveaux posts concernant l'utilisateur
        $newPosts = new ArrayCollection();

        // pour chacun des topics :
        $allTopics = $app['orm.em']->getRepository('\\'.\App\Entity\Topic::class)->findAll();
        foreach ($allTopics as $topic) {
            if ($app['security.authorization_checker']->isGranted('TOPIC_RIGHT', $topic)) {
                $newPosts = new ArrayCollection(array_merge($newPosts->toArray(), $app['User']->newPosts($topic)->toArray()));
            }
        }

        // classer les nouveaux post par ordre de publication
        $iterator = $newPosts->getIterator();
        $iterator->uasort(static function ($first, $second): int {
            return $first->getUpdateDate() < $second->getUpdateDate() ? 1 : -1;
        });
        $newPosts = new ArrayCollection(iterator_to_array($iterator));

        return $app['twig']->render('forum/root.twig', [
            'topics' => $topics,
            'newPosts' => $newPosts,
        ]);
    }

    /**
     * Ajout d'un forum de premier niveau
     * (admin uniquement).
     */
    public function forumAddAction(Request $request, Application $app)
    {
        $topic = new \App\Entity\Topic();

        $form = $app['form.factory']->createBuilder(new TopicForm(), $topic)
            ->add('right', 'choice', [
                'label' => 'Droits',
                'choices' => $app['larp.manager']->getAvailableTopicRight(),
            ])
            ->add('object_id', 'number', [
                'required' => false,
                'label' => 'Identifiant',
            ])
            ->add('key', 'text', [
                'required' => false,
                'label' => 'Clé',
            ])
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $topic = $form->getData();
            $topic->setUser($app['User']);

            $app['orm.em']->persist($topic);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le forum a été ajouté.');

            return $app->redirect($app['url_generator']->generate('forum'), 303);
        }

        return $app['twig']->render('forum/forum_add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'un topic.
     *
     * @return View $view
     */
    public function topicAction(Request $request, Application $app)
    {
        if (null == $app['User']) {
            return $app->redirect($app['url_generator']->generate('User.login', 303));
        }

        $id = $request->get('index');

        $topic = $app['orm.em']->getRepository('\\'.\App\Entity\Topic::class)->find($id);

        return $app['twig']->render('forum/topic.twig', [
            'topic' => $topic,
        ]);
    }

    /**
     * Ajout d'un post dans un topic.
     */
    public function postAddAction(Request $request, Application $app)
    {
        $topicId = $request->get('index');

        $topic = $app['orm.em']->getRepository('\\'.\App\Entity\Topic::class)
            ->find($topicId);

        $post = new \App\Entity\Post();
        $post->setTopic($topic);

        $form = $app['form.factory']->createBuilder(new PostForm(), $post)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $post = $form->getData();
            $post->setTopic($topic);
            $post->setUser($app['User']);
            $post->addWatchingUser($app['User']);

            // ajout de la signature
            $personnage = $app['User']->getPersonnage();
            if ($personnage) {
                $text = $post->getText();
                $text .= '<address><strong>Envoyé par</strong><br />'.$personnage->getNom().' '.$personnage->getSurnom().'<address>';
                $post->setText($text);
            }

            $app['orm.em']->persist($post);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le message a été ajouté.');

            return $app->redirect($app['url_generator']->generate('forum.topic', ['index' => $topic->getId()]), 303);
        }

        return $app['twig']->render('forum/post_add.twig', [
            'form' => $form->createView(),
            'topic' => $topic,
        ]);
    }

    /**
     * Lire un post.
     */
    public function postAction(Request $request, Application $app)
    {
        if (null == $app['User']) {
            return $app->redirect($app['url_generator']->generate('User.login', 303));
        }

        $postId = $request->get('index');

        $post = $app['orm.em']->getRepository('\\'.\App\Entity\Post::class)
            ->find($postId);

        // Mettre à jour les vues de ce post (et de toutes ces réponses)
        if (!$app['User']->alreadyView($post)) {
            $postView = new \App\Entity\PostView();
            $postView->setDate(new \DateTime('NOW'));
            $postView->setUser($app['User']);
            $postView->setPost($post);
            $app['orm.em']->persist($postView);
        }

        foreach ($post->getPosts() as $p) {
            if (!$app['User']->alreadyView($p)) {
                $postView = new \App\Entity\PostView();
                $postView->setDate(new \DateTime('NOW'));
                $postView->setUser($app['User']);
                $postView->setPost($p);

                $app['orm.em']->persist($postView);
            }
        }

        $app['orm.em']->flush();

        return $app['twig']->render('forum/post.twig', [
            'post' => $post,
        ]);
    }

    /**
     * Répondre à un post.
     */
    public function postResponseAction(Request $request, Application $app)
    {
        $postId = $request->get('index');

        $postToResponse = $app['orm.em']->getRepository('\\'.\App\Entity\Post::class)
            ->find($postId);

        $post = new \App\Entity\Post();
        $post->setTitle($postToResponse->getTitle());

        $form = $app['form.factory']->createBuilder(new PostForm(), $post)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $post = $form->getData();
            $post->setPost($postToResponse);
            $post->setUser($app['User']);

            // ajout de la signature
            $personnage = $app['User']->getPersonnage();
            if ($personnage) {
                $text = $post->getText();
                $text .= '<address><strong>Envoyé par</strong><br />'.$personnage->getNom().' '.$personnage->getSurnom().'<address>';
                $post->setText($text);
            }

            $postToResponse->addWatchingUser($app['User']);
            $app['orm.em']->persist($postToResponse);
            $app['orm.em']->persist($post);
            $app['orm.em']->flush();

            // envoie des notifications mails
            $watchingUsers = $postToResponse->getWatchingUsers();
            foreach ($watchingUsers as $User) {
                if ($User == $postToResponse->getUser()) {
                    continue;
                }

                if ($User == $app['User']) {
                    continue;
                }

                $app['User.mailer']->sendNotificationMessage($User, $post);
            }

            $app['session']->getFlashBag()->add('success', 'Le message a été ajouté.');

            return $app->redirect($app['url_generator']->generate('forum.post', ['index' => $postToResponse->getId()]), 303);
        }

        return $app['twig']->render('forum/post_response.twig', [
            'form' => $form->createView(),
            'postToResponse' => $postToResponse,
        ]);
    }

    /**
     * Modifier un post.
     */
    public function postUpdateAction(Request $request, Application $app)
    {
        $postId = $request->get('index');

        $post = $app['orm.em']->getRepository('\\'.\App\Entity\Post::class)
            ->find($postId);

        $form = $app['form.factory']->createBuilder(new PostForm(), $post)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $post = $form->getData();
            $post->setUpdateDate(new \DateTime('NOW'));

            $app['orm.em']->persist($post);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le message a été modifié.');

            return $app->redirect($app['url_generator']->generate('forum.post', ['index' => $post->getId()]), 303);
        }

        return $app['twig']->render('forum/post_update.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    /**
     * Active les notifications sur un post.
     */
    public function postNotificationOnAction(Request $request, Application $app)
    {
        $postId = $request->get('index');

        $post = $app['orm.em']->getRepository('\\'.\App\Entity\Post::class)
            ->find($postId);

        $post->addWatchingUser($app['User']);

        $app['orm.em']->persist($post);
        $app['orm.em']->flush();

        $app['session']->getFlashBag()->add('success', 'Les notifications sont maintenant activées.');

        return $app->redirect($app['url_generator']->generate('forum.post', ['index' => $post->getId()]), 303);
    }

    /**
     * Desactive les notifications sur un post.
     */
    public function postNotificationOffAction(Request $request, Application $app)
    {
        $postId = $request->get('index');

        $post = $app['orm.em']->getRepository('\\'.\App\Entity\Post::class)
            ->find($postId);

        $post->removeWatchingUser($app['User']);

        $app['orm.em']->persist($post);
        $app['orm.em']->flush();

        $app['session']->getFlashBag()->add('success', 'Les notifications sont maintenant desactivées.');

        return $app->redirect($app['url_generator']->generate('forum.post', ['index' => $post->getId()]), 303);
    }

    /**
     * Supprimer un post.
     */
    public function postDeleteAction(Request $request, Application $app)
    {
        $postId = $request->get('index');

        $post = $app['orm.em']->getRepository('\\'.\App\Entity\Post::class)
            ->find($postId);

        $form = $app['form.factory']->createBuilder(new PostDeleteForm(), $post)
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $post = $form->getData();

            if ($post->isRoot()) {
                $url = $app['url_generator']->generate('forum.topic', ['index' => $post->getTopic()->getId()]);
            } else {
                $ancestor = $post->getAncestor();
                $url = $app['url_generator']->generate('forum.post', ['index' => $ancestor->getId()]);
            }

            // supprimer toutes les vues
            foreach ($post->getPostViews() as $view) {
                $app['orm.em']->remove($view);
            }

            // supprimer tous les posts qui en dépendent
            foreach ($post->getPosts() as $child) {
                foreach ($child->getPostViews() as $view) {
                    $app['orm.em']->remove($view);
                }

                $app['orm.em']->remove($child);
            }

            $app['orm.em']->remove($post);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le message a été supprimé.');

            return $app->redirect($url, 303);
        }

        return $app['twig']->render('forum/post_delete.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    /**
     * Ajouter un sous-forum.
     */
    public function topicAddAction(Request $request, Application $app)
    {
        $topicId = $request->get('index');

        $topicRelated = $app['orm.em']->getRepository('\\'.\App\Entity\Topic::class)
            ->find($topicId);

        $topic = new \App\Entity\Topic();

        $form = $app['form.factory']->createBuilder(new TopicForm(), $topic)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $topic = $form->getData();
            $topic->setTopic($topicRelated);
            $topic->setUser($app['User']);
            $topic->setRight($topicRelated->getRight());
            $topic->setObjectId($topicRelated->getObjectId());

            $app['orm.em']->persist($topic);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le forum a été ajouté.');

            return $app->redirect($app['url_generator']->generate('forum.topic', ['index' => $topic->getId()]), 303);
        }

        return $app['twig']->render('forum/topic_add.twig', [
            'form' => $form->createView(),
            'topic' => $topicRelated,
        ]);
    }

    /**
     * Modfifier un topic.
     */
    public function topicUpdateAction(Request $request, Application $app)
    {
        $topicId = $request->get('index');

        $topic = $app['orm.em']->getRepository('\\'.\App\Entity\Topic::class)
            ->find($topicId);

        $formBuilder = $app['form.factory']->createBuilder(new TopicForm(), $topic);

        if ($app['security.authorization_checker']->isGranted('ROLE_MODERATOR')) {
            $formBuilder->add('topic', 'entity', [
                'required' => false,
                'label' => 'Choisissez le topic parent',
                'property' => 'title',
                'class' => \App\Entity\Topic::class,
            ]);
        }

        $form = $formBuilder->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $topic = $form->getData();
            $app['orm.em']->persist($topic);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le forum a été modifié.');

            return $app->redirect($app['url_generator']->generate('forum.topic', ['index' => $topic->getId()]), 303);
        }

        return $app['twig']->render('forum/topic_update.twig', [
            'form' => $form->createView(),
            'topic' => $topic,
        ]);
    }
}
