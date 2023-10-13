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
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Dumper;

/**
 * LarpManager\Controllers\InstallController
 *
 * @author kevin
 *
 */
class InstallController
{
	private function loadUserTables($connection, $dir) 
	{
		$sql = file_get_contents($dir.'mysql.sql');
		$statement = $connection->prepare($sql);
		$statement->execute();
	}
	
	private function loadLarpManagerTables($connection, $dir)
	{
		$sql = file_get_contents($dir.'create_or_update.sql');
		$statement = $connection->prepare($sql);
		$statement->execute();
	}
	
	private function noUserInUserTable($app)
	{
		$UsersCount = $app['User.manager']->findCount();
		
		return $UsersCount == 0 ? true : false;
	}
	
	public function createOrUpdateAction(Request $request, Application $app)
	{
		//$app['security.access_rules'] dans le bootstrap definit deja ce comportement, ce check n'est la que
		//comme double securite
		if(!($app['security.authorization_checker']->isGranted('ROLE_ADMIN')))
		{
			return $app->redirect($app['url_generator']->generate('User.login'));
		}
		
		if ( $request->getMethod() === 'POST' )
		{
			$this->loadLarpManagerTables($app['orm.em']->getConnection(), $app['db_install_path']);
			return $app['twig']->render('install/installdone.twig');
		}
		return $app->redirect($app['url_generator']->generate('homepage'));
	}
	
	/**
	 * Fin de l'installation
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function doneAction(Request $request, Application $app) {
		return $app['twig']->render('install/installdone.twig');
	}
	
	
	/**
	 * Création de l'utilisateur admin
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function createUserAction(Request $request, Application $app)
	{
		// preparation du formulaire
		$form = $app['form.factory']->createBuilder(new \LarpManager\Form\InstallUserAdminForm())
			->add('create','submit')
			->getForm();
		
		$form->handleRequest($request);
		
		// si la requête est valide
		if ( $form->isValid() )
		{
			// on récupére les data de l'utilisateur
			$data = $form->getData();
			
			$name = $data['name'];
			$email = $data['email'];
			$password = $data['password'];
		
			$app->register(new SecurityServiceProvider());
			
			$User = new \App\Entity\User($email);
			$encoder = $app['security.encoder_factory']->getEncoder($User);
			$User->setPassword($encoder->encodePassword($password, $User->getSalt()));
			$User->setUsername($name);
			$User->setRoles(array('ROLE_ADMIN'));
			$User->setCreationDate(new \Datetime('NOW'));
			$User->setIsEnabled(true);
			
			$app['orm.em']->persist($User);
			$app['orm.em']->flush();

			
			// supprimer le fichier de cache pour lancer larpmanager en mode normal		
			unlink(__DIR__.'/../../../cache/maintenance.tag');
			
			$app->mount('/', new \LarpManager\HomepageControllerProvider());
			$app['session']->getFlashBag()->add('success', 'L\'installation c\'est déroulée avec succès.');
			return $app->redirect($app['url_generator']->generate('homepage'),303);
		}
		
		return $app['twig']->render('install/installfirstUser.twig', array('form' => $form->createView()));
	}
	
	/**
	 * Mise à jour de la base de données
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function updateAction(Request $request, Application $app)
	{
		$default = array(
				'database_host' => $app['config']['database']['host'],
				'database_name' => $app['config']['database']['dbname'],
				'database_User' => $app['config']['database']['User'],
		);
		
		// preparation du formulaire
		$form = $app['form.factory']->createBuilder(new \LarpManager\Form\InstallDatabaseForm(), $default)
		->add('create','submit')
		->getForm();
		
		$form->handleRequest($request);
		
		// si la requête est valide
		if ( $form->isValid() )
		{
			$databaseConfig = $form->getData();
				
			$newConfig = $app['config'];
				
			$newConfig['database'] = array(
					'host' => $databaseConfig['database_host'],
					'dbname' => $databaseConfig['database_name'],
					'User' =>  $databaseConfig['database_User'],
					'password' => $databaseConfig['database_password'],
			);
			
			// write the new config
			$dumper = new Dumper();
			$yaml = $dumper->dump($newConfig);
			file_put_contents(__DIR__ . '/../../../config/settings.yml', $yaml);
				
			// reload doctrine with the new configuration
			$app->register(new DoctrineServiceProvider(), array(
			'db.options' => $newConfig['database']
			));
			
			// load doctrine tools
			$tool = new \Doctrine\ORM\Tools\SchemaTool($app['orm.em']);
			
			// l'opération peut prendre du temps, il faut donc régler le temps maximum d'execution
			set_time_limit(240);
				
			// on récupére les méta-data de toutes les tables
			$classes = $app['orm.em']->getMetadataFactory()->getAllMetadata();
			
			// on met a jour la base de donnée
			$tool->updateSchema($classes);
			
			return $app['twig']->render('install/installdone.twig');
		}
		return $app['twig']->render('install/update.twig',array('form' => $form->createView()));
	}
	
	/**
	 * Affiche la page d'installation de LarpManager
	 * 
	 * @param Request $request
	 * @param Application $app
	 */
	public function indexAction(Request $request, Application $app) 
	{		
		// valeur par défaut
		$default = array(
			'database_host' => $app['config']['database']['host'],
			'database_name' => $app['config']['database']['dbname'],
			'database_User' => $app['config']['database']['User'],
		);
		
		// preparation du formulaire
		$form = $app['form.factory']->createBuilder(new \LarpManager\Form\InstallDatabaseForm(), $default)
			->add('create','submit')
			->getForm();
		
		$form->handleRequest($request);
		
		// si la requête est valide
		if ( $form->isValid() )
		{
			$databaseConfig = $form->getData();
			
			$newConfig = $app['config'];
			
			$newConfig['database'] = array(
					'host' => $databaseConfig['database_host'],
					'dbname' => $databaseConfig['database_name'], 
					'User' =>  $databaseConfig['database_User'],
					'password' => $databaseConfig['database_password'],
			);
						
			// write the new config
			$dumper = new Dumper();
			$yaml = $dumper->dump($newConfig);
			file_put_contents(__DIR__ . '/../../../config/settings.yml', $yaml);
			
			// reload doctrine with the new configuration
			$app->register(new DoctrineServiceProvider(), array(
	   			'db.options' => $newConfig['database'] 
			));

			// load doctrine tools
			$tool = new \Doctrine\ORM\Tools\SchemaTool($app['orm.em']);
		
			// l'opération peut prendre du temps, il faut donc régler le temps maximum d'execution
			set_time_limit(240);
			
			// on récupére les méta-data de toutes les tables
			$classes = $app['orm.em']->getMetadataFactory()->getAllMetadata();
			
			// on créé la base de donnée
			$tool->createSchema($classes);
			
			// on ajoute des éléments de base
			$etat = new \App\Entity\Etat();
			$etat->setLabel("En stock");
			$app['orm.em']->persist($etat);
			$app['orm.em']->flush();
				
			$etat = new \App\Entity\Etat();
			$etat->setLabel("Hors stock");
			$app['orm.em']->persist($etat);
			$app['orm.em']->flush();
			
			$etat = new \App\Entity\Etat();
			$etat->setLabel("A fabriquer");
			$app['orm.em']->persist($etat);
			$app['orm.em']->flush();
			
			$etat = new \App\Entity\Etat();
			$etat->setLabel("A acheter");
			$app['orm.em']->persist($etat);
			
			
			// Création des niveaux de compétence
			$niveau = new \App\Entity\Level();
			$niveau->setLabel("Apprenti");
			$niveau->setIndex("1");
			$app['orm.em']->persist($niveau);
			
			
			$niveau = new \App\Entity\Level();
			$niveau->setLabel("Initié");
			$niveau->setIndex("2");
			$app['orm.em']->persist($niveau);
			
			
			$niveau = new \App\Entity\Level();
			$niveau->setLabel("Expert");
			$niveau->setIndex("3");
			$app['orm.em']->persist($niveau);
			
			
			$niveau = new \App\Entity\Level();
			$niveau->setLabel("Maître");
			$niveau->setIndex("4");
			$app['orm.em']->persist($niveau);
			
			
			$niveau = new \App\Entity\Level();
			$niveau->setLabel("Secret");
			$niveau->setIndex("5");
			$app['orm.em']->persist($niveau);
			
			
			$rarete = new \App\Entity\Rarete();
			$rarete->setLabel("Commun");
			$rarete->setValue("1");
			$app['orm.em']->persist($rarete);
			
			
			$rarete = new \App\Entity\Rarete();
			$rarete->setLabel("Rare");
			$rarete->setValue("2");
			$app['orm.em']->persist($rarete);
			
			
			$genre = new \App\Entity\Genre();
			$genre->setLabel("Masculin");
			$app['orm.em']->persist($genre);
			
			
			$genre = new \App\Entity\Genre();
			$genre->setLabel("Feminin");
			$app['orm.em']->persist($genre);
			
			
			$age = new \App\Entity\Age();
			$age->setLabel("Jeune adulte");
			$age->setEnableCreation(true);
			$app['orm.em']->persist($age);
			
			
			$age = new \App\Entity\Age();
			$age->setLabel("Adulte");
			$age->setEnableCreation(true);
			$app['orm.em']->persist($age);
			
			
			$age = new \App\Entity\Age();
			$age->setLabel("Mur");
			$age->setEnableCreation(false);
			$app['orm.em']->persist($age);
			
			
			$age = new \App\Entity\Age();
			$age->setLabel("Vieux");
			$age->setEnableCreation(false);
			$app['orm.em']->persist($age);
			
			
			$age = new \App\Entity\Age();
			$age->setLabel("Ancien");
			$age->setEnableCreation(false);
			$app['orm.em']->persist($age);

			
			// Création du topic culte
			$topic = new \App\Entity\Topic();
			$topic->setKey('TOPIC_CULTE');
			$topic->setTitle('Cultes');
			$topic->setDescription('Discussion à propos des cultes');
			$app['orm.em']->persist($topic);
			
			$app['orm.em']->flush();
									
			// création de l'utilisateur admin
			return $app->redirect($app['url_generator']->generate('install_create_User'));
		}
		
		return $app['twig']->render('install/index.twig',array('form' => $form->createView()));
		
	}
}