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
use Symfony\Component\HttpFoundation\JsonResponse;

use LarpManager\Form\NewMessageForm;
use App\Entity\User;
use App\Entity\Notification;

/**
 * LarpManager\Controllers\NotificationController
 *
 * @author kevin
 *
 */
class NotificationController
{
	/**
	 * Supprime une notification
	 * 
	 * @param Application $app
	 * @param Request $request
	 * @param Notification $notification
	 */
	public function removeAction(Application $app, Request $request, Notification $notification)
	{
		if ( $notification->getUser() != $app['User'])
		{
			return false;
		}
		
		$app['orm.em']->remove($notification);
		$app['orm.em']->flush();
		return true;
	}
	
	/**
	 * Fourni la liste des notifications de l'utilisateur courant
	 * On en profite pour stocker ses informations de connection
	 * 
	 * @param Application $app
	 * @param Request $request
	 */
	public function listAction(Application $app, Request $request)
	{
		
		$qb = $app['orm.em']->createQueryBuilder();
		$qb->from('App\Entity\Notification','n');
		$qb->select('n');
		$qb->join('n.User','u');
		$qb->where('u.id = :UserId');
		$qb->setParameter('UserId',$app['User']->getId());
		$notifications = $qb->getQuery()->getArrayResult();
		
		foreach ( $notifications as $key => $value)
		{
			$value['url_delete'] = $app['url_generator']->generate('notification.remove', array('notification' => $value['id']));
			$notifications[$key] = $value;
		}
		
		
		
		$app['User']->setLastConnectionDate(new \Datetime('NOW'));
		$app['orm.em']->persist($app['User']);
		$app['orm.em']->flush();
		
		$lastConnected = $app['orm.em']->getRepository('App\Entity\User')->lastConnected();
		foreach ( $lastConnected as $key => $value)
		{
			$value['url'] = $app['url_generator']->generate('User.view', array('id' => $value['id']));
			$lastConnected[$key] = $value;
		}
		
		return new JsonResponse(array(
				'notifications' => $notifications,
				'lastConnected' => $lastConnected	
		));
	}
			
}