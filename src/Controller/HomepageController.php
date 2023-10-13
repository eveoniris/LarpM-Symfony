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

use App\Entity\EtatCivil;
use App\Entity\Restriction;
use LarpManager\Form\EtatCivilForm;
use LarpManager\Form\UserRestrictionForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\HomepageController
 *
 * @author kevin
 *
 */
class HomepageController
{

	/**
	 * Choix de la page d'acceuil en fonction de l'état de l'utilisateur
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function indexAction(Request $request, Application $app) 
	{	

		if ( ! $app['User'] )
		{
			return $this->notConnectedIndexAction($request, $app);
		}
		
		$app['User'] = $app['User.manager']->refreshUser($app['User']);
		
		if ( ! $app['User']->getEtatCivil() )
		{
			return $app->redirect($app['url_generator']->generate('newUser.step1'),303);
		}
		
		$repoAnnonce = $app['orm.em']->getRepository('App\Entity\Annonce');
		$annonces = $repoAnnonce->findBy(array('archive' => false, 'gn' => null),array('update_date' => 'DESC'));
				
		return $app['twig']->render('homepage/index.twig', array(
				'annonces' => $annonces,
				'User' => $app['User'],
		));
	}
	
	/**
	 * Première étape pour un nouvel utilisateur
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function newUserStep1Action(Request $request, Application $app)
	{
		if ( $app['User']->getEtatCivil() )
		{
			$repoAnnonce = $app['orm.em']->getRepository('App\Entity\Annonce');
			$annonces = $repoAnnonce->findBy(array('archive' => false, 'gn' => null),array('update_date' => 'DESC'));
			
			return $app['twig']->render('homepage/index.twig', array(
					'annonces' => $annonces,
					'User' => $app['User'],
			));
		}
		return $app['twig']->render('public/newUser/step1.twig', array());
	}
	
	/**
	 * Seconde étape pour un nouvel utilisateur : enregistrer les informations administratives
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function newUserStep2Action(Request $request, Application $app)
	{
		$etatCivil = $app['User']->getEtatCivil();
		if (  ! $etatCivil )
			$etatCivil = new EtatCivil();
	
		$form = $app['form.factory']->createBuilder(new EtatCivilForm(), $etatCivil)
			->getForm();

		$form->handleRequest($request);
			
		if ( $form->isValid() )
		{
			$etatCivil = $form->getData();
			$app['User']->setEtatCivil($etatCivil);
			
			$app['orm.em']->persist($app['User']);
			$app['orm.em']->flush();
			
			return $app->redirect($app['url_generator']->generate('newUser.step3'),303);
		}
		
		return $app['twig']->render('public/newUser/step2.twig', array(
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Troisième étape pour un nouvel utilisateur : les restrictions alimentaires
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function newUserStep3Action(Request $request, Application $app)
	{
		$form = $app['form.factory']->createBuilder(new UserRestrictionForm(),$app['User'])
			->getForm();
		
		$form->handleRequest($request);
			
		if ( $form->isValid() )
		{
			$User = $form->getData();
			$newRestriction = $form->get("new_restriction")->getData();
			if ( $newRestriction )
			{
				$restriction = new Restriction();
				$restriction->setUserRelatedByAuteurId($app['User']);
				$restriction->setLabel($newRestriction);
			
				$app['orm.em']->persist($restriction);
				$User->addRestriction($restriction);
			}
				
			$app['orm.em']->persist($User);
			$app['orm.em']->flush();
			
			return $app->redirect($app['url_generator']->generate('newUser.step4'),303);
				
		}
		return $app['twig']->render('public/newUser/step3.twig', array(
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Quatrième étape pour un nouvel utilisateur : choisir un GN
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function newUserStep4Action(Request $request, Application $app)
	{
		return $app['twig']->render('public/newUser/step4.twig');
	}
	
	/**
	 * Fourni le blason pour affichage
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function getBlasonAction(Request $request, Application $app)
	{
		$blason = $request->get('blason');
		$filename = __DIR__.'/../../../private/img/blasons/'.$blason;
		return $app->sendFile($filename);
	}
	
	/**
	 * Page d'acceuil pour les utilisateurs non connecté
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function notConnectedIndexAction(Request $request, Application $app)
	{
		return $app['twig']->render('homepage/not_connected.twig');
	}	
	
	/**
	 * Affiche une carte du monde
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function worldAction(Request $request, Application $app)
	{
		return $app['twig']->render('public/world.twig');
	}
	
	/**
	 * Fourni la liste des pays, leur geographie et leur description
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function countriesAction(Request $request, Application $app)
	{
		$repoTerritoire = $app['orm.em']->getRepository('App\Entity\Territoire');
		$territoires = $repoTerritoire->findRoot();
	
		$countries = array();
		foreach ( $territoires as $territoire)
		{
			$countries[] = array(
					'id' => $territoire->getId(),
					'geom' => $territoire->getGeojson(),
					'name' => $territoire->getNom(),
					'color' => $territoire->getColor(),
					'description' => strip_tags($territoire->getDescription()),
					'groupes' =>  array_values($territoire->getGroupesPj()),
					'desordre' => $territoire->getStatutIndex(),
					'langue' => $territoire->getLanguePrincipale(),
			);
		}
	
		return $app->json($countries);
	}
	
	/**
	 * Fourni la liste des régions
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function regionsAction(Request $request, Application $app)
	{
		$repoTerritoire = $app['orm.em']->getRepository('App\Entity\Territoire');
		$territoires = $repoTerritoire->findRegions();
	
		$regions = array();
		foreach ( $territoires as $territoire)
		{
			$regions[] = array(
					'id' => $territoire->getId(),
					'geom' => $territoire->getGeojson(),
					'name' => $territoire->getNom(),
					'color' => $territoire->getColor(),
					'description' => strip_tags($territoire->getDescription()),
					'groupes' =>  array_values($territoire->getGroupesPj()),
					'desordre' => $territoire->getStatutIndex(),
					'langue' => $territoire->getLanguePrincipale(),
			);
		}
	
		return $app->json($regions);
	}
	
	/**
	 * Fourni la liste des fiefs
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function fiefsAction(Request $request, Application $app)
	{
		$repoTerritoire = $app['orm.em']->getRepository('App\Entity\Territoire');
		$territoires = $repoTerritoire->findFiefs();
	
		$fiefs = array();
		foreach ( $territoires as $territoire)
		{
			$fiefs[] = array(
					'id' => $territoire->getId(),
					'geom' => $territoire->getGeojson(),
					'name' => $territoire->getNom(),
					'color' => $territoire->getColor(),
					'description' => strip_tags($territoire->getDescription()),
					'groupes' =>  array_values($territoire->getGroupesPj()),
					'desordre' => $territoire->getStatutIndex(),
					'langue' => $territoire->getLanguePrincipale(),
			);
		}
	
		return $app->json($fiefs);
	}	
	
	/**
	 * Fourni la liste des langues
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function languesAction(Request $request, Application $app)
	{
		$langueList = $app['orm.em']->getRepository('\App\Entity\Langue')->findAll();
		
		$langues = array();
		foreach ( $langueList as $langue)
		{
			if ( $langue->getDiffusion() == 0 ) continue; // ne pas transmettre aux joueurs la liste des langues anciennes
			
			// construction du geojson de la langue (utilisation en tant que langue principale)
			$geojson = '{ "type": "FeatureCollection", "features": [';
			
			$first = true;
			$geoJsonPrincipalCount = 0;
			foreach ( $langue->getTerritoires() as $territoire )
			{
				$geom = $territoire->getGeojson();

				if ( $geom )
				{
					if ( !$first) $geojson .=',';
					$geojson .= $geom;
					$first = false;
					$geoJsonPrincipalCount++;
				}
			}
			
			$geojson .= ']}';
			$geoJsonPrincipal = $geojson;
			
			// construction du geoJson de la langue (utilisation en tant que langue secondaire)
			$geojson = '{ "type": "FeatureCollection", "features": [';
			$first = true;
			$geoJsonSecondaireCount = 0;
			foreach ( $langue->getTerritoireSecondaires() as $territoire )
			{
				$geom = $territoire->getGeojson();
			
				if ( $geom )
				{
					if ( ! $first) $geojson .=',';
					$geojson  .= $geom;
					$first = false;
					$geoJsonSecondaireCount++;
				}
			}
				
			$geojson .= ']}';
			$geoJsonSecondaire = $geojson;
			
			$langues[] = array(
					'id' => $langue->getId(),
					'label' => $langue->getLabel(),
					'description' => $langue->getDescription(),
					'diffusion' => $langue->getDiffusion(),
					'geomPrincipal' => $geoJsonPrincipal,
					'geomSecondaire' => $geoJsonSecondaire,
					'geomPrincipalCount' => $geoJsonPrincipalCount,
					'geomSecondaireCount' => $geoJsonSecondaireCount,
			);
		}
		return $app->json($langues);
	}
	
	/**
	 * Fourni la liste des groupes
	 * @param Request $request
	 * @param Application $app
	 */
	public function groupesAction(Request $request, Application $app)
	{
		// recherche le prochain GN
		$gnRepo = $app['orm.em']->getRepository('\App\Entity\Gn');
		$gn = $gnRepo->findNext();
		
		$groupeGnList = $gn->getGroupeGns();

		$groupes = array();
		foreach ( $groupeGnList as $groupeGn)
		{
			$groupe = $groupeGn->getGroupe();
			if ( $groupe->getPj() === true )
			{	
				$geom = null;
				if ( $groupe->getTerritoires()->count() > 0 )
				{
					$territoire = $groupe->getTerritoires()->first();
					$geom = $territoire->getGeojson();
				}
				
				// mettre en surbrillance le groupe de l'utilisateur
				$highlight = false;
				if ( $app['User'])
				{
					foreach ($app['User']->getParticipants() as $participant)
					{
						if ( $participant->getGn() == $gn )
						{
							if ($participant->getGroupeGn() )
							{
								if ( $participant->getGroupeGn()->getGroupe() == $groupe )
								{
									$highlight = true;
									break;
								}
							}
						}
					}
				}
				$groupes[] = array(
						'id' => $groupe->getId(),
						'nom' => $groupe->getNom(),
						'geom' => $geom,
						'highlight' => $highlight,
					);
			}
		}
		
		return $app->json($groupes);
	}

	/**
	 * Met à jour la geographie d'un pays
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function updateCountryGeomAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
		$geom = $request->get('geom');
		$color = $request->get('color');
		
		$territoire->setGeojson($geom);
		$territoire->setColor($color);
		
		$app['orm.em']->persist($territoire);
		$app['orm.em']->flush();
		
		$country = array(
					'id' => $territoire->getId(),
					'geom' => $territoire->getGeojson(),
					'name' => $territoire->getNom(),
					'description' => strip_tags($territoire->getDescription()),
					'groupes' =>  array_values($territoire->getGroupesPj()),
				);
		return $app->json($country);
	}
	
	/**
	 * Affiche une page récapitulatif des liens pour discuter
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function discuterAction(Request $request, Application $app)
	{
		return $app['twig']->render('public/discuter.twig');
	}
	
	/**
	 * Affiche une page récapitulatif des événements
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function evenementAction(Request $request, Application $app)
	{
		return $app['twig']->render('public/evenement.twig');
	}
	
	/**
	 * Affiche les mentions légales
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function legalAction(Request $request, Application $app)
	{
		return $app['twig']->render('homepage/legal.twig');
	}
	
	/**
	 * Affiche les informations de dev
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function devAction(Request $request, Application $app)
	{
		return $app['twig']->render('homepage/dev.twig');
	}
	
	/**
	 * Statistiques du projet
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function metricsAction(Request $request, Application $app)
	{
		return $app['twig']->render('homepage/metrics/report.html');
	}
}