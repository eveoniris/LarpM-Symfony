<?php


namespace App\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use App\Form\PostDeleteForm;
use App\Form\PostForm;
use App\Form\TopicForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LarpManager\Controllers\ForumController.
 *
 * @author kevin
 */
class ForumController extends AbstractController
{
    /**
     * Liste des forums de premier niveau.
     */
    #[Route('/forum', name: 'forum')]
    public function forumAction(Request $request,  EntityManagerInterface $entityManager)
    {
        if (null == $this->getUser()) {
            return $this->redirectToRoute('user.login', [], 303);
        }

        $topics = $entityManager->getRepository('\\'.\App\Entity\Topic::class)
            ->findAllRoot();

        // rechercher tous les nouveaux posts concernant l'utilisateur
        $newPosts = new ArrayCollection();

        // pour chacun des topics :
        $allTopics = $entityManager->getRepository('\\'.\App\Entity\Topic::class)->findAll();
        foreach ($allTopics as $topic) {
            if ($app['security.authorization_checker']->isGranted('TOPIC_RIGHT', $topic)) {
                $newPosts = new ArrayCollection(array_merge($newPosts->toArray(), $this->getUser()->newPosts($topic)->toArray()));
            }
        }

        // classer les nouveaux post par ordre de publication
        $iterator = $newPosts->getIterator();
        $iterator->uasort(static function ($first, $second): int {
            return $first->getUpdateDate() < $second->getUpdateDate() ? 1 : -1;
        });
        $newPosts = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('forum/root.twig', [
            'topics' => $topics,
            'newPosts' => $newPosts,
        ]);
    }

    /**
     * Ajout d'un forum de premier niveau
     * (admin uniquement).
     */
    public function forumAddAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $topic = new \App\Entity\Topic();

        $form = $this->createForm(TopicForm::class, $topic)
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
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $topic = $form->getData();
            $topic->setUser($this->getUser());

            $entityManager->persist($topic);
            $entityManager->flush();

           $this->addFlash('success', 'Le forum a été ajouté.');

            return $this->redirectToRoute('forum', [], 303);
        }

        return $this->render('forum/forum_add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'un topic.
     *
     * @return View $view
     */
    public function topicAction(Request $request,  EntityManagerInterface $entityManager)
    {
        if (null == $this->getUser()) {
            return $this->redirectToRoute('user.login', [], 303);
        }

        $id = $request->get('index');

        $topic = $entityManager->getRepository('\\'.\App\Entity\Topic::class)->find($id);

        return $this->render('forum/topic.twig', [
            'topic' => $topic,
        ]);
    }

    /**
     * Ajout d'un post dans un topic.
     */
    public function postAddAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $topicId = $request->get('index');

        $topic = $entityManager->getRepository('\\'.\App\Entity\Topic::class)
            ->find($topicId);

        $post = new \App\Entity\Post();
        $post->setTopic($topic);

        $form = $this->createForm(PostForm::class, $post)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $post->setTopic($topic);
            $post->setUser($this->getUser());
            $post->addWatchingUser($this->getUser());

            // ajout de la signature
            $personnage = $this->getUser()->getPersonnage();
            if ($personnage) {
                $text = $post->getText();
                $text .= '<address><strong>Envoyé par</strong><br />'.$personnage->getNom().' '.$personnage->getSurnom().'<address>';
                $post->setText($text);
            }

            $entityManager->persist($post);
            $entityManager->flush();

           $this->addFlash('success', 'Le message a été ajouté.');

            return $this->redirectToRoute('forum.topic', ['index' => $topic->getId()], [], 303);
        }

        return $this->render('forum/post_add.twig', [
            'form' => $form->createView(),
            'topic' => $topic,
        ]);
    }

    /**
     * Lire un post.
     */
    public function postAction(Request $request,  EntityManagerInterface $entityManager)
    {
        if (null == $this->getUser()) {
            return $this->redirectToRoute('user.login', [],303);
        }

        $postId = $request->get('index');

        $post = $entityManager->getRepository('\\'.\App\Entity\Post::class)
            ->find($postId);

        // Mettre à jour les vues de ce post (et de toutes ces réponses)
        if (!$this->getUser()->alreadyView($post)) {
            $postView = new \App\Entity\PostView();
            $postView->setDate(new \DateTime('NOW'));
            $postView->setUser($this->getUser());
            $postView->setPost($post);
            $entityManager->persist($postView);
        }

        foreach ($post->getPosts() as $p) {
            if (!$this->getUser()->alreadyView($p)) {
                $postView = new \App\Entity\PostView();
                $postView->setDate(new \DateTime('NOW'));
                $postView->setUser($this->getUser());
                $postView->setPost($p);

                $entityManager->persist($postView);
            }
        }

        $entityManager->flush();

        return $this->render('forum/post.twig', [
            'post' => $post,
        ]);
    }

    /**
     * Répondre à un post.
     */
    public function postResponseAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $postId = $request->get('index');

        $postToResponse = $entityManager->getRepository('\\'.\App\Entity\Post::class)
            ->find($postId);

        $post = new \App\Entity\Post();
        $post->setTitle($postToResponse->getTitle());

        $form = $this->createForm(PostForm::class, $post)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $post->setPost($postToResponse);
            $post->setUser($this->getUser());

            // ajout de la signature
            $personnage = $this->getUser()->getPersonnage();
            if ($personnage) {
                $text = $post->getText();
                $text .= '<address><strong>Envoyé par</strong><br />'.$personnage->getNom().' '.$personnage->getSurnom().'<address>';
                $post->setText($text);
            }

            $postToResponse->addWatchingUser($this->getUser());
            $entityManager->persist($postToResponse);
            $entityManager->persist($post);
            $entityManager->flush();

            // envoie des notifications mails
            $watchingUsers = $postToResponse->getWatchingUsers();
            foreach ($watchingUsers as $User) {
                if ($User == $postToResponse->getUser()) {
                    continue;
                }

                if ($User == $this->getUser()) {
                    continue;
                }

                $app['User.mailer']->sendNotificationMessage($User, $post);
            }

           $this->addFlash('success', 'Le message a été ajouté.');

            return $this->redirectToRoute('forum.post', ['index' => $postToResponse->getId()], [], 303);
        }

        return $this->render('forum/post_response.twig', [
            'form' => $form->createView(),
            'postToResponse' => $postToResponse,
        ]);
    }

    /**
     * Modifier un post.
     */
    public function postUpdateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $postId = $request->get('index');

        $post = $entityManager->getRepository('\\'.\App\Entity\Post::class)
            ->find($postId);

        $form = $this->createForm(PostForm::class, $post)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $post->setUpdateDate(new \DateTime('NOW'));

            $entityManager->persist($post);
            $entityManager->flush();

           $this->addFlash('success', 'Le message a été modifié.');

            return $this->redirectToRoute('forum.post', ['index' => $post->getId()], [], 303);
        }

        return $this->render('forum/post_update.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    /**
     * Active les notifications sur un post.
     */
    public function postNotificationOnAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $postId = $request->get('index');

        $post = $entityManager->getRepository('\\'.\App\Entity\Post::class)
            ->find($postId);

        $post->addWatchingUser($this->getUser());

        $entityManager->persist($post);
        $entityManager->flush();

       $this->addFlash('success', 'Les notifications sont maintenant activées.');

        return $this->redirectToRoute('forum.post', ['index' => $post->getId()], [], 303);
    }

    /**
     * Desactive les notifications sur un post.
     */
    public function postNotificationOffAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $postId = $request->get('index');

        $post = $entityManager->getRepository('\\'.\App\Entity\Post::class)
            ->find($postId);

        $post->removeWatchingUser($this->getUser());

        $entityManager->persist($post);
        $entityManager->flush();

       $this->addFlash('success', 'Les notifications sont maintenant desactivées.');

        return $this->redirectToRoute('forum.post', ['index' => $post->getId()], [], 303);
    }

    /**
     * Supprimer un post.
     */
    public function postDeleteAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $postId = $request->get('index');

        $post = $entityManager->getRepository('\\'.\App\Entity\Post::class)
            ->find($postId);

        $form = $this->createForm(PostDeleteForm::class, $post)
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();

            if ($post->isRoot()) {
                $url = $app['url_generator']->generate('forum.topic', ['index' => $post->getTopic()->getId()]);
            } else {
                $ancestor = $post->getAncestor();
                $url = $app['url_generator']->generate('forum.post', ['index' => $ancestor->getId()]);
            }

            // supprimer toutes les vues
            foreach ($post->getPostViews() as $view) {
                $entityManager->remove($view);
            }

            // supprimer tous les posts qui en dépendent
            foreach ($post->getPosts() as $child) {
                foreach ($child->getPostViews() as $view) {
                    $entityManager->remove($view);
                }

                $entityManager->remove($child);
            }

            $entityManager->remove($post);
            $entityManager->flush();

           $this->addFlash('success', 'Le message a été supprimé.');

            return $app->redirect($url, 303);
        }

        return $this->render('forum/post_delete.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    /**
     * Ajouter un sous-forum.
     */
    public function topicAddAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $topicId = $request->get('index');

        $topicRelated = $entityManager->getRepository('\\'.\App\Entity\Topic::class)
            ->find($topicId);

        $topic = new \App\Entity\Topic();

        $form = $this->createForm(TopicForm::class, $topic)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $topic = $form->getData();
            $topic->setTopic($topicRelated);
            $topic->setUser($this->getUser());
            $topic->setRight($topicRelated->getRight());
            $topic->setObjectId($topicRelated->getObjectId());

            $entityManager->persist($topic);
            $entityManager->flush();

           $this->addFlash('success', 'Le forum a été ajouté.');

            return $this->redirectToRoute('forum.topic', ['index' => $topic->getId()], [], 303);
        }

        return $this->render('forum/topic_add.twig', [
            'form' => $form->createView(),
            'topic' => $topicRelated,
        ]);
    }

    /**
     * Modfifier un topic.
     */
    public function topicUpdateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $topicId = $request->get('index');

        $topic = $entityManager->getRepository('\\'.\App\Entity\Topic::class)
            ->find($topicId);

        $formBuilder = $this->createForm(TopicForm::class, $topic);

        if ($app['security.authorization_checker']->isGranted('ROLE_MODERATOR')) {
            $formBuilder->add('topic', 'entity', [
                'required' => false,
                'label' => 'Choisissez le topic parent',
                'property' => 'title',
                'class' => \App\Entity\Topic::class,
            ]);
        }

        $form = $formBuilder->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $topic = $form->getData();
            $entityManager->persist($topic);
            $entityManager->flush();

           $this->addFlash('success', 'Le forum a été modifié.');

            return $this->redirectToRoute('forum.topic', ['index' => $topic->getId()], [], 303);
        }

        return $this->render('forum/topic_update.twig', [
            'form' => $form->createView(),
            'topic' => $topic,
        ]);
    }
}
