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

namespace App\Manager;

use Silex\Application;
use Doctrine\Common\Collections\ArrayCollection;

use App\Entity\User;
use App\Entity\Message;
use App\Entity\Billet;
use App\Entity\GroupeGn;
use App\Entity\Intrigue;
use App\Entity\Groupe;
use App\Entity\Notification;

class NotifyManager
{
	protected $fromAddress;
	protected $fromName;
	
	/**
	 * Constructeur
	 */
	public function __construct()
	{
		$this->fromAddress = $app['config']['user']['mailer']['fromEmail']['address'];
		$this->fromName = $app['config']['user']['mailer']['fromEmail']['name'];
	}
	
	/**
	 * Génére une notification coeur
	 * 
	 * @param User $userTo
	 * @param User $userFrom
	 */
	public function coeur(User $userTo, User $userFrom)
	{
		$notification = new Notification();
		$notification->setText('Vous avez reçu un coeur de '.$userTo->getUserName());
		$notification->setUser($userFrom);
		$notification->setUrl($this->app['url_generator']->generate('user.view', array('id' => $userTo->getId()), true));
		
		$this->app['orm.em']->persist($notification);
		$this->app['orm.em']->flush();

		//$entityManager->persist($etatCivil);
		//$entityManager->flush();
	}
	
	/**
	 * Envoi une notification "modification d'une intrigue" au scénariste s'occupant de ce groupe
	 * 
	 * @param Intrigue $intrigue
	 * @param Groupe $groupe
	 */
	public function intrigue(Intrigue $intrigue, Groupe $groupe)
	{
		$user = $groupe->getScenariste();
		if ( $user )
		{
			$url = $this->app['url_generator']->generate('intrigue.detail', array('intrigue' => $intrigue->getId()), true);
			$notification = new Notification();
			$notification->setText('Une intrigue concernant votre groupe '.$groupe->getNom().' vient d\'être édité');
			$notification->setUser($user);
			$notification->setUrl($url);
			
			$this->app['orm.em']->persist($notification);
			$this->app['orm.em']->flush();
			
			// envoi de la notification mail
			$this->sendMessage(
					'user/email/intrigue.twig',
					array(
						'groupe' => $groupe,
						'intrigue' => $intrigue,
						'url' => $url),
					$this->fromAddress,
					$user->getEmail()
					);
		}
	}
	
	/**
	 * Envoi une notification "Ajout d'une relecture" au scénariste s'occupant de ce groupe
	 * @param Intrigue $intrigue
	 * @param Groupe $groupe
	 */
	public function relecture(Intrigue $intrigue, Groupe $groupe)
	{
		$user = $groupe->getScenariste();
		if ( $user )
		{
			$url = $this->app['url_generator']->generate('intrigue.detail', array('intrigue' => $intrigue->getId()), true);
			$notification = new Notification();
			$notification->setText('Une intrigue concernant votre groupe '.$groupe->getNom().' vient d\'être relue');
			$notification->setUser($user);
			$notification->setUrl($url);
				
			$this->app['orm.em']->persist($notification);
			$this->app['orm.em']->flush();
				
			// envoi de la notification mail
			$this->sendMessage(
				'user/email/relecture.twig',
				array(
					'groupe' => $groupe,
					'intrigue' => $intrigue,
					'url' => $url),
				$this->fromAddress,
				$user->getEmail()
			);
		}
	}
			
	
	/**
	 * Gestion de la notification "Nouveau Message"
	 * - envoi d'un mail de notification
	 * - creation de la notification
	 * 
	 * @param User $user
	 */
	public function newMessage(User $user, Message $message)
	{
		$notification = new Notification();
		$notification->setText('Vous avez reçu un nouveau message de '.$message->getUserRelatedByAuteur()->getUserName());
		$notification->setUser($user);
		$notification->setUrl($this->app['url_generator']->generate('messagerie'),array(), true);
		
		$this->app['orm.em']->persist($notification);
		$this->app['orm.em']->flush();

		$url = $this->app['url_generator']->generate('homepage', array(), true);
		
		$this->sendMessage(
				'user/email/newMessage.twig', 
				array(
					'message' => $message,
					'messagerieUrl' => $url),
				$this->fromAddress, 
				$user->getEmail()
			);
	}
	
	/**
	 * Notification "nouveau Billet"
	 * 
	 * @param User $user
	 * @param Billet $billet
	 */
	public function newBillet(User $user, Billet $billet)
	{
		$notification = new Notification();
		$notification->setText('Vous avez reçu un nouveau billet : '.$billet->getGn()->getLabel().' '.$billet->getLabel());
		$notification->setUser($user);
		$notification->setUrl($this->app['url_generator']->generate('user.gn.detail', array('gn' => $billet->getGn()->getId()), true));
		
		
		$this->app['orm.em']->persist($notification);
		$this->app['orm.em']->flush();
		
		$url = $this->app['url_generator']->generate('homepage', array(), true);
		
		$this->sendMessage(
				'user/email/newBillet.twig',
				array(
						'billet' => $billet,
						'messagerieUrl' => $url),
				$this->fromAddress,
				$user->getEmail()
			);
	}
	
	/**
	 * Notification désigner comme responsable
	 * 
	 * @param User $user
	 * @param GroupeGn $groupeGn
	 */
	public function newResponsable($user, $groupeGn)
	{
		$notification = new Notification();
		$notification->setText('Vous avez été désigné responsable du groupe : '.$groupeGn->getGroupe()->getNom());
		$notification->setUser($user);
		$notification->setUrl($this->app['url_generator']->generate('groupe.detail', array('groupeGn' => $groupeGn->getId(), 'groupe' => $groupeGn->getGroupe()->getId()), true));
				
		$this->app['orm.em']->persist($notification);
		$this->app['orm.em']->flush();
		
		$url = $this->app['url_generator']->generate('homepage', array(), true);
		
		$this->sendMessage(
				'user/email/newResponsable.twig',
				array(
						'groupeGn' => $groupeGn,
						'messagerieUrl' => $url),
				$this->fromAddress,
				$user->getEmail()
				);
	}
	
	/**
	 * Notification désigner comme nouveau membre d'un groupe
	 *
	 * @param User $user
	 * @param GroupeGn $groupeGn
	 */
	public function newMembre($user, $groupeGn)
	{
		$notification = new Notification();
		$notification->setText('Vous avez été ajouté au groupe : '.$groupeGn->getGroupe()->getNom());
		$notification->setUser($user);
        $notification->setUrl($this->app['url_generator']->generate('groupe.detail', array('groupeGn' => $groupeGn->getId(), 'groupe' => $groupeGn->getGroupe()->getId()), true));

        $this->app['orm.em']->persist($notification);
		$this->app['orm.em']->flush();
	
		$url = $this->app['url_generator']->generate('homepage', array(), true);
		
		$this->sendMessage(
				'user/email/newMembre.twig',
				array(
						'groupeGn' => $groupeGn,
						'messagerieUrl' => $url),
				$this->fromAddress,
				$user->getEmail()
				);
	}
	
	/**
	 * Envoi d'un mail de création de compte
	 * 
	 * @param unknown $user
	 * @param unknown $password
	 */
	public function newUser($user, $plainPassword)
	{
		$url = $this->app['url_generator']->generate('homepage', array(), true);
		
		$this->sendMessage(
				'user/email/newUser.twig',
				array(
						'plainPassword' => $plainPassword,
						'url' => $url),
				$this->fromAddress,
				$user->getEmail()
				);
	}
	
	/**
	 * Notification nouveau membre à destination du chef de groupe et du scénariste
	 * 
	 * @param unknown $participant
	 * @param unknown $groupeGn
	 */
	public function joinGroupe($participant, $groupeGn)
	{
		/*$chef = $groupeGn->getResponsable()->getUser();
		$scenariste = $groupeGn->getGroupe()->getScenariste();
		
		$notificationChef = new Notification();
		$notificationChef->setText($participant->getUser() . ' a rejoint le groupe '.$groupeGn->getGroupe()->getNom());
		$notificationChef->setUser($chef);
		$notification->setUrl($this->app['url_generator']->generate('groupeGn.groupe', array('groupeGn' => $groupeGn->getId()), true));
		$this->app['orm.em']->persist($notificationChef);
		
		$notificationScenariste = new Notification();
		$notificationScenariste->setText($participant->getUser() . ' a rejoint le groupe '.$groupeGn->getGroupe()->getNom());
		$notificationScenariste->setUser($scenariste);
		$notification->setUrl($this->app['url_generator']->generate('groupeGn.groupe', array('groupeGn' => $groupeGn->getId()), true));
		$this->app['orm.em']->persist($notificationScenariste);
		
		$this->app['orm.em']->flush();*/
	}
	
	/**
	 * Demande d'un joueur pour rejoindre un groupe secondaire
	 * 
	 * @param unknown $responsable
	 */
	public function joinGroupeSecondaire($responsable, $groupeSecondaire)
	{			
		$url = $this->app['url_generator']->generate('homepage', array(), true);
		$this->sendMessage('user/email/newCandidature.twig',array('groupeSecondaire' => $groupeSecondaire, 'url' => $url), array('noreply@eveoniris.com'), array($responsable->getUser()->getEmail()));
	}
	
	/**
	 * Demande d'un joueur pour rejoindre un groupe secondaire
	 *
	 * @param unknown $responsable
	 */
	public function acceptGroupeSecondaire($user, $groupeSecondaire)
	{
		$url = $this->app['url_generator']->generate('homepage', array(), true);
		$this->sendMessage('user/email/groupeSecondaireAccept.twig',array('groupeSecondaire' => $groupeSecondaire, 'url' => $url), array('noreply@eveoniris.com'), array($user->getEmail()));
	}
	
	/**
	 * Demande d'un joueur pour rejoindre un groupe secondaire
	 *
	 * @param unknown $responsable
	 */
	public function rejectGroupeSecondaire($user, $groupeSecondaire)
	{
		$url = $this->app['url_generator']->generate('homepage', array(), true);
		$this->sendMessage('user/email/groupeSecondaireReject.twig',array('groupeSecondaire' => $groupeSecondaire, 'url' => $url), array('noreply@eveoniris.com'), array($user->getEmail()));
	}
	
	/**
	 * Demande d'un joueur pour rejoindre un groupe secondaire
	 *
	 * @param unknown $responsable
	 */
	public function waitGroupeSecondaire($user, $groupeSecondaire)
	{
		$url = $this->app['url_generator']->generate('homepage', array(), true);
		$this->sendMessage('user/email/groupeSecondaireWait.twig',array('groupeSecondaire' => $groupeSecondaire, 'url' => $url), array('noreply@eveoniris.com'), array($user->getEmail()));
	}
	
	/**
	 * Envoi du message
	 * 
	 * @param unknown $templateName
	 * @param unknown $context
	 * @param unknown $from
	 * @param unknown $to
	 */
	private function sendMessage($templateName, $context, $from, $to)
	{
		$context = $this->app['twig']->mergeGlobals($context);
		$template = $this->app['twig']->loadTemplate($templateName);
		$subject = $template->renderBlock('subject', $context);
		$textBody = $template->renderBlock('body_text', $context);
		$htmlBody = $template->renderBlock('body_html', $context);
		
		$message = \Swift_Message::newInstance()
			->setSubject($subject)
			->setFrom($from)
			->setTo($to);
		
		if (!empty($htmlBody)) {
			$message->setBody($htmlBody, 'text/html')
			->addPart($textBody, 'text/plain');
		} else {
			$message->setBody($textBody);
		}
		
		$this->app['mailer']->send($message);
	}
}
