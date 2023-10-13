<?php

/**
 * LarpManager - A Live Action Role Playing Manager
 * Copyright (C) 2016 Kevin Polez
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

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

use LarpManager\Form\NewMessageForm;
use App\Entity\User;
use App\Entity\Message;

/**
 * LarpManager\Controllers\MessageController
 *
 * @author kevin
 *
 */
class MessageController
{

	/**
	 * Affiche la messagerie de l'utilisateur
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function messagerieAction(Request $request, Application $app)
	{
		return $app['twig']->render('public/message/messagerie.twig', array(
				'User' => $app['User'],
		));
	}
	
	/**
	 * Affiche les messages archiver de l'utilisateur
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function archiveAction(Request $request, Application $app)
	{
		return $app['twig']->render('public/message/archive.twig', array(
				'User' => $app['User'],
		));
	}
	
	/**
	 * Affiche les messages envoyé par l'utilisateur
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function envoyeAction(Request $request, Application $app)
	{
		return $app['twig']->render('public/message/envoye.twig', array(
				'User' => $app['User'],
		));
	}
	
	
	/**
	 * Nouveau message
	 *
	 * @param Application $app
	 * @param Request $request
	 * @param User $User
	 */
	public function newAction(Application $app, Request $request)
	{
		$message = new Message();
		$message->setUserRelatedByAuteur($app['User']);
		
		$to_id = $request->get('to');
		if ( $to_id )
		{
			$to = $app['converter.User']->convert($to_id);
			$message->setUserRelatedByDestinataire($to);
		}
			
		$form = $app['form.factory']->createBuilder(new NewMessageForm(), $message)
			->add('envoyer','submit', array('label' => "Envoyer votre message"))
			->getForm();
	
		$form->handleRequest($request);
	
		if ( $form->isValid() )
		{
			$message = $form->getData();
				
			// ajout de la signature
			$personnage = $app['User']->getPersonnage();
			if ( $personnage )
			{
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
			return $app->redirect($app['url_generator']->generate('homepage'),303);
		}
	
		return $app['twig']->render('public/message/new.twig', array(
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Archiver un message
	 *
	 * @param Application $app
	 * @param Request $request
	 * @throws NotFoundHttpException
	 * @throws AccessDeniedException
	 */
	public function messageArchiveAction(Application $app, Request $request, Message $message)
	{
		if ( $message->getUserRelatedByDestinataire() != $app['User'])
		{
			return false;
		}
	
		$message->setLu(true);
		$app['orm.em']->persist($message);
		$app['orm.em']->flush();
	
		return true;
	}
	
	/**
	 * Répondre à un message
	 * 
	 * @param Application $app
	 * @param Request $request
	 * @param Message $message
	 */
	public function messageResponseAction(Application $app, Request $request, Message $message)
	{
		$reponse = new \App\Entity\Message();
		
		$reponse->setUserRelatedByAuteur($app['User']);
		$reponse->setUserRelatedByDestinataire($message->getUserRelatedByAuteur());
		$reponse->setTitle('Réponse à "'.$message->getTitle().'"');
		$reponse->setCreationDate(new \Datetime('NOW'));
		$reponse->setUpdateDate(new \Datetime('NOW'));
		
		$form = $app['form.factory']->createBuilder(new NewMessageForm(), $reponse)
			->add('envoyer','submit', array('label' => "Envoyer votre message"))
			->getForm();
		
		$form->handleRequest($request);
			
		if ( $form->isValid() )
		{
			$message = $form->getData();
		
			// ajout de la signature
			$personnage = $app['User']->getPersonnageRelatedByPersonnageId();
			if ( $personnage )
			{
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
			return $app->redirect($app['url_generator']->generate('homepage'),303);
		}
		return $app['twig']->render('public/message/response.twig', array(
				'message' => $message,
				'User' => $app['User'],
				'form' => $form->createView(),
		));
	}
}