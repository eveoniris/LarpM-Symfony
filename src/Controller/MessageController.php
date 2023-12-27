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

use App\Entity\Message;
use App\Repository\BaseRepository;
use LarpManager\Form\NewMessageForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MessageController extends AbstractController
{
    /**
     * Affiche la messagerie de l'utilisateur.
     */
    #[Route('/messagerie', name: 'messagerie')]
    public function messagerieAction(Request $request)
    {
        return $this->render('message/messagerie.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Affiche les messages archiver de l'utilisateur.
     */
    #[Route('/messagerie/archive', name: 'message.archives')]
    public function archiveAction(Request $request)
    {
        return $this->render('message/archive.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Affiche les messages envoyé par l'utilisateur.
     */
    #[Route('/messagerie/envoye', name: 'message.envoye')]
    public function envoyeAction(Request $request)
    {
        return $this->render('message/envoye.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Nouveau message.
     */
    #[Route('/messagerie/new', name: 'message.new')]
    public function newAction(Request $request)
    {
        $message = new Message();
        $message->setUserRelatedByAuteur($this->getUser());

        $to_id = $request->get('to');
        if ($to_id) {
            $to = $app['converter.user']->convert($to_id);
            $message->setUserRelatedByDestinataire($to);
        }

        $form = $app['form.factory']->createBuilder(new NewMessageForm(), $message)
            ->add('envoyer', 'submit', ['label' => 'Envoyer votre message'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $message = $form->getData();

            // ajout de la signature
            $personnage = $app['User']->getPersonnage();
            if ($personnage) {
                $text = $message->getText();
                $text .= '<address><strong>Envoyé par</strong><br />'.$personnage->getNom().' '.$personnage->getSurnom().'<address>';
                $message->setText($text);
            }

            $app['orm.em']->persist($message);
            $app['orm.em']->flush();

            // création de la notification
            $destinataire = $message->getUserRelatedByDestinataire();
            $app['notify']->newMessage($destinataire, $message);

            $app['session']->getFlashBag()->add('success', 'Votre message a été envoyé.');

            return $app->redirect($app['url_generator']->generate('homepage'), 303);
        }

        return $app['twig']->render('public/message/new.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Archiver un message.
     *
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    #[Route('/messagerie/message-archive', name: 'message.archive')]
    public function messageArchiveAction(Application $app, Request $request, Message $message): bool
    {
        if ($message->getUserRelatedByDestinataire() != $app['User']) {
            return false;
        }

        $message->setLu(true);
        $app['orm.em']->persist($message);
        $app['orm.em']->flush();

        return true;
    }

    /**
     * Répondre à un message.
     */
    #[Route('/messagerie/response', name: 'message.response')]
    public function messageResponseAction(Application $app, Request $request, Message $message)
    {
        $reponse = new \App\Entity\Message();

        $reponse->setUserRelatedByAuteur($app['User']);
        $reponse->setUserRelatedByDestinataire($message->getUserRelatedByAuteur());
        $reponse->setTitle('Réponse à "'.$message->getTitle().'"');
        $reponse->setCreationDate(new \DateTime('NOW'));
        $reponse->setUpdateDate(new \DateTime('NOW'));

        $form = $app['form.factory']->createBuilder(new NewMessageForm(), $reponse)
            ->add('envoyer', 'submit', ['label' => 'Envoyer votre message'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $message = $form->getData();

            // ajout de la signature
            $personnage = $app['User']->getPersonnageRelatedByPersonnageId();
            if ($personnage) {
                $text = $message->getText();
                $text .= '<address><strong>Envoyé par</strong><br />'.$personnage->getNom().' '.$personnage->getSurnom().'<address>';
                $message->setText($text);
            }

            $app['orm.em']->persist($message);
            $app['orm.em']->flush();

            // création de la notification
            $destinataire = $message->getUserRelatedByDestinataire();
            $app['notify']->newMessage($destinataire, $message);

            $app['session']->getFlashBag()->add('success', 'Votre message a été envoyé.');

            return $app->redirect($app['url_generator']->generate('homepage'), 303);
        }

        return $app['twig']->render('public/message/response.twig', [
            'message' => $message,
            'User' => $app['User'],
            'form' => $form->createView(),
        ]);
    }
}
