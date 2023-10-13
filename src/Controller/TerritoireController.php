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

use JasonGrimes\Paginator;
use LarpManager\Form\Territoire\FiefForm;
use LarpManager\Repository\TerritoireRepository;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use LarpManager\Form\Territoire\TerritoireForm;
use LarpManager\Form\Territoire\TerritoireDeleteForm;
use LarpManager\Form\Territoire\TerritoireStrategieForm;
use LarpManager\Form\Territoire\TerritoireIngredientsForm;
use LarpManager\Form\Territoire\TerritoireBlasonForm;

use LarpManager\Form\Territoire\TerritoireCultureForm;
use LarpManager\Form\Territoire\TerritoireLoiForm;
use LarpManager\Form\Territoire\TerritoireStatutForm;
use LarpManager\Form\Territoire\TerritoireCiblesForm;

use App\Entity\Territoire;
use App\Entity\Loi;

use LarpManager\Repository\ConstructionRepository;

/**
 * LarpManager\Controllers\TerritoireController
 *
 * @author kevin
 */
class TerritoireController
{

	/**
	 * Modifier les listes de cibles pour les quêtes commerciales
	 */
	public function updateCiblesAction(Request $request, Application $app, Territoire $territoire)
	{
		$form = $app['form.factory']->createBuilder(new TerritoireCiblesForm(), $territoire)
                        ->add('update','submit', array('label' => "Sauvegarder"))
                        ->getForm();

                $form->handleRequest($request);
                        
                if ( $form->isValid() )
                {
                        $territoire = $form->getData();
                        
                        $app['orm.em']->persist($territoire);
                        $app['orm.em']->flush();
                                
                        $app['session']->getFlashBag()->add('success','Le territoire a été mis à jour');
                        return $app->redirect($app['url_generator']->generate('territoire.admin.detail', array('territoire' => $territoire->getId())),303);
                }


		return $app['twig']->render('admin/territoire/cibles.twig', array(
			'territoire' => $territoire,
			'form' => $form->createView()
		));
	}

	/**
	 * Liste des territoires
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function listAction(Request $request, Application $app)
	{
		$territoires = $app['orm.em']->getRepository('\App\Entity\Territoire')->findRoot();
				
		return $app['twig']->render('admin/territoire/list.twig', array('territoires' => $territoires));
	}
	
	/**
	 * Liste des fiefs
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function fiefAction(Request $request, Application $app)
	{
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = $request->get('order_dir') == 'DESC' ? 'DESC' : 'ASC';
        $limit = (int)($request->get('limit') ?: 500);
        $page = (int)($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = array();

        $formData = $request->query->get('personnageFind');
        $pays = isset($formData['pays'])?$app['orm.em']->find('App\Entity\Territoire',$formData['pays']):null;
        $province = isset($formData['provinces'])?$app['orm.em']->find('App\Entity\Territoire',$formData['provinces']):null;
        $groupe = isset($formData['groupe'])?$app['orm.em']->find('App\Entity\Groupe',$formData['groupe']):null;
        $optionalParameters = "";

        $listeGroupes = $app['orm.em']->getRepository('\App\Entity\Groupe')->findList(null,null,['by'=>'nom','dir'=>'ASC'],1000,0);
        $listePays = $app['orm.em']->getRepository('\App\Entity\Territoire')->findRoot();
        $listeProvinces = $app['orm.em']->getRepository('\App\Entity\Territoire')->findProvinces();

        $form = $app['form.factory']->createBuilder(
            new FiefForm(),
            null,
            array(
                'data' => [
                    'pays' => $pays,
                    'province' => $province,
                    'groupe' => $groupe
                ],
                'listeGroupes' => $listeGroupes,
                'listePays' => $listePays,
                'listeProvinces' => $listeProvinces,
                'method' => 'get',
                'csrf_protection' => false
            )
        )->getForm();

        $form->handleRequest($request);

        if ( $form->isValid() )
        {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            $pays = $data['pays'] ? $data['pays'] : null;
            $province = $data['province'] ? $data['province'] : null;
            $groupe = $data['groupe'] ? $data['groupe'] : null;

            if($type && $value)
            {
                switch ($type){
                    case 'idFief':
                        $criteria["t.id"] = $value;
                        break;
                    case 'nomFief':
                        $criteria["t.nom"] = $value;
                        break;
                }
            }
        }
        if($groupe){
            $criteria["tgr.id"] = $groupe->getId();
            $optionalParameters .= "&fief[groupe]={$groupe->getId()}";
        }
        if($pays){
            $criteria["tp.id"] = $pays->getId();
            $optionalParameters .= "&fief[pays]={$pays->getId()}";
        }
        if($province){
            $criteria["tpr.id"] = $province->getId();
            $optionalParameters .= "&fief[province]={$province->getId()}";
        }

        /* @var TerritoireRepository $repo */
        $repo = $app['orm.em']->getRepository('\App\Entity\Territoire');
        $fiefs = $repo->findFiefsList(
            $criteria,
            array( 'by' =>  $order_by, 'dir' => $order_dir),
            $limit,
            $offset
        );

        $numResults = count($fiefs);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('territoire.fief') . '?page=(:num)&limit=' . $limit . '&order_by=' . $order_by . '&order_dir=' . $order_dir . $optionalParameters
        );

		return $app['twig']->render('territoire/fief.twig', array(
		    'fiefs' => $fiefs,
            'form' => $form->createView(),
            'paginator' => $paginator,
            'optionalParameters' => $optionalParameters
        ));
	}
	
	/**
	 * Impression des territoires
	 * @param Request $request
	 * @param Application $app
	 */
	public  function printAction(Request $request, Application $app)
	{
		$territoires = $app['orm.em']->getRepository('\App\Entity\Territoire')->findFiefs();
		
		return $app['twig']->render('admin/territoire/print.twig', array('territoires' => $territoires));
	}
	
	
	/**
	 * Liste des fiefs pour les quêtes
	 * @param Request $request
	 * @param Application $app
	 */
	public  function queteAction(Request $request, Application $app)
	{
		$territoires = $app['orm.em']->getRepository('\App\Entity\Territoire')->findFiefs();
	
		return $app['twig']->render('admin/territoire/quete.twig', array('territoires' => $territoires));
	}
	

	/**
	 * Liste des pays avec le nombre de noble
	 * @param Request $request
	 * @param Application $app
	 */
	public  function nobleAction(Request $request, Application $app)
	{
		$territoires = $app['orm.em']->getRepository('\App\Entity\Territoire')->findRoot();
	
		return $app['twig']->render('admin/territoire/noble.twig', array('territoires' => $territoires));
	}
	
	/**
	 * Detail d'un territoire pour les joueurs
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function detailJoueurAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
	
		return $app['twig']->render('public/territoire/detail.twig', array('territoire' => $territoire));
	}
	
	/**
	 * Detail d'un territoire
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function detailAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
		
		return $app['twig']->render('admin/territoire/detail.twig', array('territoire' => $territoire));
	}
	
	/**
	 * Ajoute une loi à un territoire
	 * 
	 * @param Request $request
	 * @param Application $app
	 * @param Territoire $territoire
	 */
	public function updateLoiAction(Request $request, Application $app, Territoire $territoire)
	{
		$form = $app['form.factory']->createBuilder(new TerritoireLoiForm(), $territoire)
			->add('update','submit', array('label' => "Sauvegarder"))
			->getForm();
		
		$form->handleRequest($request);
			
		if ( $form->isValid() )
		{
			$territoire = $form->getData();
			
			$app['orm.em']->persist($territoire);
			$app['orm.em']->flush();
				
			$app['session']->getFlashBag()->add('success','Le territoire a été mis à jour');
			return $app->redirect($app['url_generator']->generate('territoire.admin.detail', array('territoire' => $territoire->getId())),303);
		}
		
		return $app['twig']->render('admin/territoire/loi.twig', array(
				'territoire' => $territoire,
				'form' => $form->createView(),
		));
	}
		
	/**
	 * Ajoute une construction dans un territoire
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function constructionAddAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
				
		$form = $app['form.factory']->createBuilder()
			->add('construction','entity', array(
					'label' => 'Choisissez la construction',
					'required' => true,
					'class' => 'App\Entity\Construction',
					'query_builder' => function(ConstructionRepository $repo) {
						return $repo->createQueryBuilder('c')->orderBy('c.label', 'ASC');
					},
					'property' => 'fullLabel',
					'expanded' => true,
			))
			->add('save','submit', array('label' => "Sauvegarder"))
			->getForm();
		
		$form->handleRequest($request);
		
		if ( $form->isValid() )
		{
			$data = $form->getData();
			
			$territoire->addConstruction($data['construction']);
			$app['orm.em']->persist($territoire);
			$app['orm.em']->flush();
			
			$app['session']->getFlashBag()->add('success', 'La construction a été ajoutée.');
			return $app->redirect($app['url_generator']->generate('territoire.admin.detail',array('territoire' => $territoire->getId())),303);
		}
		
		return $app['twig']->render('admin/territoire/addConstruction.twig', array(
				'territoire' => $territoire,
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Retire une construction d'un territoire
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function constructionRemoveAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
		$construction = $request->get('construction');
		
		$form = $app['form.factory']->createBuilder()
			->add('save','submit', array('label' => "Retirer la construction"))
			->getForm();
		
		$form->handleRequest($request);
			
		if ( $form->isValid() )
		{
			$data = $form->getData();
				
			$territoire->removeConstruction($construction);
			$app['orm.em']->persist($territoire);
			$app['orm.em']->flush();
					
			$app['session']->getFlashBag()->add('success', 'La construction a été retiré.');
			return $app->redirect($app['url_generator']->generate('territoire.admin.detail',array('territoire' => $territoire->getId())),303);
		}
			
		return $app['twig']->render('admin/territoire/removeConstruction.twig', array(
				'territoire' => $territoire,
				'construction' => $construction,
				'form' => $form->createView(),
		));	
	}
	
	/**
	 * Ajoute un territoire
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function addAction(Request $request, Application $app)
	{
		$territoire = new \App\Entity\Territoire();
		
		$form = $app['form.factory']->createBuilder(new TerritoireForm(), $territoire)
			->add('save','submit', array('label' => "Sauvegarder"))
			->add('save_continue','submit', array('label' => "Sauvegarder & continuer"))
			->getForm();
		
		$form->handleRequest($request);
		
		if ( $form->isValid() )
		{
			$territoire = $form->getData();
			
			/**
			 * Création des topics associés à ce groupe
			 * un topic doit être créé par GN auquel ce groupe est inscrit
			 * @var \App\Entity\Topic $topic
			 */
			$topic = new \App\Entity\Topic();
			$topic->setTitle($territoire->getNom());
			$topic->setDescription($territoire->getDescription());
			$topic->setUser($app['User']);
			// défini les droits d'accés à ce forum
			// (les membres du groupe ont le droit d'accéder à ce forum)
			$topic->setRight('TERRITOIRE_MEMBER');
			$topic->setTopic($app['larp.manager']->findTopic('TOPIC_TERRITOIRE'));
				
			$territoire->setTopic($topic);
				
			$app['orm.em']->persist($topic);
			$app['orm.em']->persist($territoire);
			$app['orm.em']->flush();
														
			$app['session']->getFlashBag()->add('success', 'Le territoire a été ajouté.');
				
			if ( $form->get('save')->isClicked())
			{
				return $app->redirect($app['url_generator']->generate('territoire.admin.list'),303);
			}
			else if ( $form->get('save_continue')->isClicked())
			{
				return $app->redirect($app['url_generator']->generate('territoire.admin.add'),303);
			}
		}
		
		return $app['twig']->render('admin/territoire/add.twig', array(
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Mise à jour de la liste des ingrédients fourni par un territoire
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function updateIngredientsAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
		
		$form = $app['form.factory']->createBuilder(new TerritoireIngredientsForm(), $territoire)
			->add('update','submit', array('label' => "Sauvegarder"))
			->getForm();
		
		$form->handleRequest($request);
		
		if ( $form->isValid() )
		{
			$territoire = $form->getData();
			
			$app['orm.em']->persist($territoire);
			$app['orm.em']->flush();
			
			$app['session']->getFlashBag()->add('success', 'Le territoire a été mis à jour.');
			return $app->redirect($app['url_generator']->generate('territoire.admin.detail',array('territoire' => $territoire->getId())),303);
		}
		
		return $app['twig']->render('admin/territoire/updateIngredients.twig', array(
				'territoire' => $territoire,
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Modifie un territoire
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function updateAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
				
		$form = $app['form.factory']->createBuilder(new TerritoireForm(), $territoire)
			->add('update','submit', array('label' => "Sauvegarder"))
			->getForm();
		
		$form->handleRequest($request);
		
		if ( $form->isValid() )
		{
			$territoire = $form->getData();
			
			$app['orm.em']->persist($territoire);
			$app['orm.em']->flush();
			$app['session']->getFlashBag()->add('success', 'Le territoire a été mis à jour.');
		
			return $app->redirect($app['url_generator']->generate('territoire.admin.detail',array('territoire' => $territoire->getId())),303);
		}		

		return $app['twig']->render('admin/territoire/update.twig', array(
				'territoire' => $territoire,
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Met à jour la culture d'un territoire
	 * 
	 * @param Request $request
	 * @param Application $app
	 * @param Territoire $territoire
	 */
	public function updateCultureAction(Request $request, Application $app, Territoire $territoire)
	{
		$form = $app['form.factory']->createBuilder(new TerritoireCultureForm(), $territoire)
			->add('update','submit', array('label' => "Sauvegarder"))
			->getForm();
		
		$form->handleRequest($request);
			
		if ( $form->isValid() )
		{
			$app['orm.em']->persist($territoire);
			$app['orm.em']->flush();
			
			$app['session']->getFlashBag()->add('success','Le territoire a été mis à jour');
			return $app->redirect($app['url_generator']->generate('territoire.admin.detail', array('territoire' => $territoire->getId())),303);
		}
		
		return $app['twig']->render('admin/territoire/culture.twig', array(
				'territoire' => $territoire,
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Met à jour le statut d'un territoire
	 *
	 * @param Request $request
	 * @param Application $app
	 * @param Territoire $territoire
	 */
	public function updateStatutAction(Request $request, Application $app, Territoire $territoire)
	{
		$form = $app['form.factory']->createBuilder(new TerritoireStatutForm(), $territoire)
			->add('update','submit', array('label' => "Sauvegarder"))
			->getForm();
	
		$form->handleRequest($request);
			
		if ( $form->isValid() )
		{
			$app['orm.em']->persist($territoire);
			$app['orm.em']->flush();
				
			$app['session']->getFlashBag()->add('success','Le territoire a été mis à jour');
			return $app->redirect($app['url_generator']->generate('territoire.admin.detail', array('territoire' => $territoire->getId())),303);
		}
	
		return $app['twig']->render('admin/territoire/updateStatut.twig', array(
				'territoire' => $territoire,
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Met à jour le blason d'un territoire
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function updateBlasonAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
		
		$form = $app['form.factory']->createBuilder(new TerritoireBlasonForm(), $territoire)
			->add('update','submit', array('label' => "Sauvegarder"))
			->getForm();
		
		$form->handleRequest($request);
		
		if ( $form->isValid() )
		{
			$files = $request->files->get($form->getName());
			
			$path = __DIR__.'/../../../private/img/blasons/';
			$filename = $files['blason']->getClientOriginalName();
			$extension = $files['blason']->guessExtension();
				
			if (!$extension || ! in_array($extension, array('png', 'jpg', 'jpeg','bmp'))) {
				$app['session']->getFlashBag()->add('error','Désolé, votre image ne semble pas valide (vérifiez le format de votre image)');
				return $app->redirect($app['url_generator']->generate('territoire.admin.detail', array('territoire' => $territoire->getId())),303);
			}
			
			$blasonFilename = hash('md5',$app['User']->getUsername().$filename . time()).'.'.$extension;
			
			$image = $app['imagine']->open($files['blason']->getPathname());
			$image->resize($image->getSize()->widen(160));
			$image->save($path. $blasonFilename);
			
			$territoire->setBlason($blasonFilename);
			$app['orm.em']->persist($territoire);
			$app['orm.em']->flush();
			
			$app['session']->getFlashBag()->add('success','Le blason a été enregistré');
			return $app->redirect($app['url_generator']->generate('territoire.admin.detail', array('territoire' => $territoire->getId())),303);
		}
		
		return $app['twig']->render('admin/territoire/blason.twig', array(
				'territoire' => $territoire,
				'form' => $form->createView(),
		));
	}
		
	/**
	 * Modifie le jeu strategique d'un territoire
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function updateStrategieAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
	
		$form = $app['form.factory']->createBuilder(new TerritoireStrategieForm(), $territoire)
			->add('update','submit', array('label' => "Sauvegarder"))
			->getForm();
	
		$form->handleRequest($request);
	
		if ( $form->isValid() )
		{
			$territoire = $form->getData();
				
			$app['orm.em']->persist($territoire);
			$app['orm.em']->flush();
			$app['session']->getFlashBag()->add('success', 'Le territoire a été mis à jour.');
	
			return $app->redirect($app['url_generator']->generate('territoire.admin.detail',array('territoire' => $territoire->getId())),303);
		}
	
		return $app['twig']->render('admin/territoire/updateStrategie.twig', array(
				'territoire' => $territoire,
				'form' => $form->createView(),
		));
	}	
	
	/**
	 * Supression d'un territoire
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function deleteAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
		
		$form = $app['form.factory']->createBuilder(new TerritoireDeleteForm(), $territoire)
			->add('delete','submit', array('label' => "Supprimer"))
			->getForm();
		
		$form->handleRequest($request);
		
		if ( $form->isValid() )
		{
			$territoire = $form->getData();
			
			foreach ( $territoire->getPersonnages() as $personnage)
			{
				$personnage->setTerritoire(null);
				$app['orm.em']->persist($personnage);
			}
			
			foreach ($territoire->getGroupes() as $groupe)
			{
				$groupe->removeTerritoire($territoire);
				$app['orm.em']->persist($groupe);
			}
			
			if ( $territoire->getGroupe() )
			{
				$groupe = $territoire->getGroupe();
				$groupe->setTerritoire(null);
				$app['orm.em']->persist($groupe);
			}
			
			$app['orm.em']->remove($territoire);
			$app['orm.em']->flush();
			$app['session']->getFlashBag()->add('success', 'Le territoire a été supprimé.');
			return $app->redirect($app['url_generator']->generate('territoire.admin.list'),303);
		}
		
		return $app['twig']->render('admin/territoire/delete.twig', array(
				'territoire' => $territoire,
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Ajout d'un topic pour un territoire
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function addTopicAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
		
		$topic = new \App\Entity\Topic();
		$topic->setTitle($territoire->getNom());
		$topic->setDescription($territoire->getDescription());
		$topic->setUser($app['User']);
		$topic->setRight('TERRITOIRE_MEMBER');
		$topic->setObjectId($territoire->getId());
		$topic->addTerritoire($territoire);
		$topic->setTopic($app['larp.manager']->findTopic('TOPIC_TERRITOIRE'));
		
		$territoire->setTopic($topic);

		$app['orm.em']->persist($topic);
		$app['orm.em']->persist($territoire);
		$app['orm.em']->flush();
		
		$app['session']->getFlashBag()->add('success', 'Le topic a été ajouté.');
		return $app->redirect($app['url_generator']->generate('territoire.admin.detail', array('territoire' => $territoire->getId())),303);
	}
	
	/**
	 * Supression d'un topic pour un territoire
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function deleteTopicAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
		
		$topic = $territoire->getTopic();
		
		if ( $topic)
		{
			$territoire->setTopic(null);
			
			$app['orm.em']->persist($territoire);
			$app['orm.em']->remove($topic);
			$app['orm.em']->flush();
		}
		
		$app['session']->getFlashBag()->add('success', 'Le topic a été supprimé.');
		return $app->redirect($app['url_generator']->generate('territoire.admin.detail', array('territoire' => $territoire->getId())),303);
	}
	
	/**
	 * Ajoute un événement à un territoire
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function eventAddAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
		$event = $request->get('event');

		$event = new \App\Entity\Chronologie();
		
		$form = $app['form.factory']->createBuilder(new EventForm(), $event)
			->add('add','submit', array('label' => "Ajouter"))
			->getForm();

		$form->handleRequest($request);
			
		if ( $form->isValid() )
		{
			$event = $form->getData();
					
			$app['orm.em']->persist($event);
			$app['orm.em']->flush();
			$app['session']->getFlashBag()->add('success', 'L\'evenement a été ajouté.');
			return $app->redirect($app['url_generator']->generate('territoire.admin.detail', array('territoire' => $territoire->getId())),303);
		}
			
		return $app['twig']->render('admin/territoire/addEvent.twig', array(
				'territoire' => $territoire,
				'event' => $event,
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Met à jour un événement
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function eventUpdateAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
		$event = $request->get('event');

		$form = $app['form.factory']->createBuilder(new ChronologieForm(), $event)
			->add('update','submit', array('label' => "Mettre à jour"))
			->getForm();

		$form->handleRequest($request);
			
		if ( $form->isValid() )
		{
			$event = $form->getData();
					
			$app['orm.em']->persist($event);
			$app['orm.em']->flush();
			$app['session']->getFlashBag()->add('success', 'L\'evenement a été modifié.');
			return $app->redirect($app['url_generator']->generate('territoire.admin.detail', array('territoire' => $territoire->getId())),303);
		}
			
		return $app['twig']->render('admin/territoire/updateEvent.twig', array(
				'territoire' => $territoire,
				'event' => $event,
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Supprime un événement
	 *
	 * @param Request $request
	 * @param Application $app
	 */
	public function eventDeleteAction(Request $request, Application $app)
	{
		$territoire = $request->get('territoire');
		$event = $request->get('event');

		$form = $app['form.factory']->createBuilder(new ChronologieDeleteForm(), $event)
			->add('delete','submit', array('label' => "Supprimer"))
			->getForm();

		$form->handleRequest($request);
			
		if ( $form->isValid() )
		{
			$event = $form->getData();
					
			$app['orm.em']->remove($event);
			$app['orm.em']->flush();
			$app['session']->getFlashBag()->add('success', 'L\'evenement a été supprimé.');
			return $app->redirect($app['url_generator']->generate('territoire.admin.detail', array('territoire' => $territoire->getId())),303);
		}
			
		return $app['twig']->render('admin/territoire/deleteEvent.twig', array(
				'territoire' => $territoire,
				'event' => $event,
				'form' => $form->createView(),
		));
	}	
}
