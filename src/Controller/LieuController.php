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

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use App\Entity\Lieu;
use LarpManager\Form\LieuForm;
use LarpManager\Form\LieuDeleteForm;
use LarpManager\Form\LieuDocumentForm;

/**
 * LarpManager\Controllers\LieuController
 *
 * @author kevin
 *
 */
class LieuController
{
	/**
	 * Liste des lieux
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function indexAction(Request $request, Application $app)
	{
		$lieux = $app['orm.em']->getRepository('\App\Entity\Lieu')->findAllOrderedByNom();
		
		return $app['twig']->render('admin/lieu/index.twig', array('lieux' => $lieux));
	}

	/**
	 * Imprimer la liste des documents
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function printAction(Request $request, Application $app)
	{
		$lieux = $app['orm.em']->getRepository('\App\Entity\Lieu')->findAllOrderedByNom();
	
		return $app['twig']->render('admin/lieu/print.twig', array('lieux' => $lieux));
	}

	/**
	 * Télécharger la liste des lieux
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function downloadAction(Request $request, Application $app)
	{
		$lieux = $app['orm.em']->getRepository('\App\Entity\Lieu')->findAllOrderedByNom();
	
		header("Content-Type: text/csv");
		header("Content-Disposition: attachment; filename=eveoniris_lieux_".date("Ymd").".csv");
		header("Pragma: no-cache");
		header("Expires: 0");
			
		$output = fopen("php://output", "w");
			
		// header
		fputcsv($output,
		array(
		'nom',
		'description',
		'documents'), ';');
			
		foreach ($lieux as $lieu)
		{
			$line = array();
			$line[] = utf8_decode($lieu->getNom());
			$line[] = utf8_decode($lieu->getDescriptionRaw());
	
			$documents = '';
			foreach ( $lieu->getDocuments() as $document)
			{
				$documents .= utf8_decode($document->getIdentity()). ', ';
			}
			$line[] = $documents;
			
			fputcsv($output, $line, ';');
		}
			
		fclose($output);
		exit();
	}
	
	/**
	 * Ajouter un lieu
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function addAction(Request $request, Application $app)
	{
		$form = $app['form.factory']->createBuilder(new LieuForm(), new Lieu())
			->add('save','submit', array('label' => "Sauvegarder"))
			->add('save_continue','submit', array('label' => "Sauvegarder & continuer"))
			->getForm();
		
		$form->handleRequest($request);
			
		if ( $form->isValid() )
		{
			$lieu = $form->getData();
				
			$app['orm.em']->persist($lieu);
			$app['orm.em']->flush();
				
			$app['session']->getFlashBag()->add('success', 'Le lieu a été ajouté.');
				
			if ( $form->get('save')->isClicked())
			{
				return $app->redirect($app['url_generator']->generate('lieu'),303);
			}
			else if ( $form->get('save_continue')->isClicked())
			{
				return $app->redirect($app['url_generator']->generate('lieu.add'),303);
			}
		}
		
		return $app['twig']->render('admin/lieu/add.twig', array(
				'form' => $form->createView()
		));
		
	}
	
	/**
	 * Détail d'un lieu
	 * 
	 * @param Request $request
	 * @param Application $app
	 * @param Lieu $lieu
	 */
	public function detailAction(Request $request, Application $app, Lieu $lieu)
	{		
		return $app['twig']->render('admin/lieu/detail.twig', array('lieu' => $lieu));
	}
	
	/**
	 * Mise à jour d'un lieu
	 * 
	 * @param Request $request
	 * @param Application $app
	 * @param Lieu $lieu
	 */
	public function updateAction(Request $request, Application $app, Lieu $lieu)
	{		
		$form = $app['form.factory']->createBuilder(new LieuForm(), $lieu)
			->add('save','submit', array('label' => "Sauvegarder"))
			->getForm();
		
		$form->handleRequest($request);
		
		if ( $form->isValid() )
		{
			$lieu = $form->getData();	
			$app['orm.em']->persist($lieu);
			$app['orm.em']->flush();
				
			$app['session']->getFlashBag()->add('success', 'Le lieu a été modifié.');
			return $app->redirect($app['url_generator']->generate('lieu'),303);
		}

		return $app['twig']->render('admin/lieu/update.twig', array(
				'lieu' => $lieu,
				'form' => $form->createView(),
		));	
	}
	
	/**
	 * Suppression d'un lieu
	 * 
	 * @param Request $request
	 * @param Application $app
	 * @param Lieu $lieu
	 */
	public function deleteAction(Request $request, Application $app, Lieu $lieu)
	{	
		$form = $app['form.factory']->createBuilder(new LieuDeleteForm(), $lieu)
			->add('save','submit', array('label' => "Supprimer"))
			->getForm();
		
		$form->handleRequest($request);
		
		if ( $form->isValid() )
		{
			$lieu = $form->getData();
				
			$app['orm.em']->remove($lieu);
			$app['orm.em']->flush();
				
			$app['session']->getFlashBag()->add('success', 'Le lieu a été supprimé.');
			return $app->redirect($app['url_generator']->generate('lieu'),303);
		}

		return $app['twig']->render('admin/lieu/delete.twig', array(
				'lieu' => $lieu,
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Gestion de la liste des documents lié à un lieu
	 * 
	 * @param Request $request
	 * @param Application $app
	 * @param Lieu $lieu
	 */
	public function documentAction(Request $request, Application $app, Lieu $lieu)
	{
		$form = $app['form.factory']->createBuilder(new LieuDocumentForm(), $lieu)
			->add('submit','submit', array('label' => 'Enregistrer'))
			->getForm();
		
		$form->handleRequest($request);
			
		if ( $form->isValid() )
		{
			$lieu = $form->getData();
			$app['orm.em']->persist($lieu);
			$app['orm.em']->flush();
				
			$app['session']->getFlashBag()->add('success', 'Le document a été ajouté au lieu.');
			return $app->redirect($app['url_generator']->generate('lieu.detail', array('lieu' => $lieu->getId())));
		}
		
		return $app['twig']->render('admin/lieu/documents.twig', array(
				'lieu' => $lieu,
				'form' => $form->createView(),
		));
	}
		
}