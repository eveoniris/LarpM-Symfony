<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use LarpManager\Form\RegionForm;


class RegionController
{
	public function indexAction(Request $request, Application $app)
	{
		$repo = $app['orm.em']->getRepository('\App\Entity\Region');
		$regions = $repo->findAll();
		return $app['twig']->render('region/index.twig', array('regions' => $regions));
	}

	public function addAction(Request $request, Application $app)
	{
		$region = new \App\Entity\Region();
		
		$form = $app['form.factory']->createBuilder(new RegionForm(), $region)
			->add('save','submit', array('label' => "Sauvegarder"))
			->add('save_continue','submit', array('label' => "Sauvegarder & continuer"))
			->getForm();
		
		$form->handleRequest($request);
		
		if ( $form->isValid() )
		{
			$region = $form->getData();
			$region->setCreator($app['User']);
						
			$app['orm.em']->persist($region);
			$app['orm.em']->flush();
				
			$app['session']->getFlashBag()->add('success', 'La région a été ajouté.');
			
			if ( $form->get('save')->isClicked())
			{
				return $app->redirect($app['url_generator']->generate('region'),303);
			}
			else if ( $form->get('save_continue')->isClicked())
			{
				return $app->redirect($app['url_generator']->generate('region.add'),303);
			}
		}
		
		return $app['twig']->render('region/add.twig', array(
				'form' => $form->createView(),	
			));			
	}

	public function updateAction(Request $request, Application $app)
	{
		$id = $request->get('index');
		
		$region = $app['orm.em']->find('\App\Entity\Region',$id);
				
		$form = $app['form.factory']->createBuilder(new RegionForm(), $region)
			->add('update','submit', array('label' => "Sauvegarder"))
			->add('delete','submit', array('label' => "Supprimer"))
			->getForm();
		
		$form->handleRequest($request);
		
		if ( $form->isValid() )
		{
			$region = $form->getData();
						
			if ($form->get('update')->isClicked())
			{
				$region->setUpdateDate(new \DateTime('NOW'));
				
				$app['orm.em']->persist($region);
				$app['orm.em']->flush();
				$app['session']->getFlashBag()->add('success', 'La région a été mise à jour.');
				return $app->redirect($app['url_generator']->generate('region.detail',array('index' => $id)));
			}
			else if ($form->get('delete')->isClicked())
			{
				$app['orm.em']->remove($region);
				$app['orm.em']->flush();
			
				$app['session']->getFlashBag()->add('success', 'La région a été supprimée.');
				return $app->redirect($app['url_generator']->generate('region'));
			}
		}
		
		return $app['twig']->render('region/update.twig', array(
				'region' => $region,
				'form' => $form->createView(),
		));
	}

	public function detailAction(Request $request, Application $app)
	{
		$id = $request->get('index');

		$region = $app['orm.em']->find('\App\Entity\Region',$id);

		if ( $region )
		{
			return $app['twig']->render('region/detail.twig', array('region' => $region));
		}
		else
		{
			$app['session']->getFlashBag()->add('error', 'La région n\'a pas été trouvée.');
			return $app->redirect($app['url_generator']->generate('region'));
		}
	}
	
	public function detailExportAction(Request $request, Application $app)
	{
		$id = $request->get('index');
	}
	
	public function exportAction(Request $request, Application $app)
	{
		
	}
}