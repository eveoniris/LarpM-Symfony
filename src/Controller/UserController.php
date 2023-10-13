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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use InvalidArgumentException;

use LarpManager\Form\UserPersonnageSecondaireForm;
use LarpManager\Form\UserFindForm;
use LarpManager\Form\EtatCivilForm;
use LarpManager\Form\MessageForm;
use LarpManager\Form\NewMessageForm;
use LarpManager\Form\RestrictionForm;
use LarpManager\Form\UserRestrictionForm;
use LarpManager\Form\User\UserPersonnageDefaultForm;
use LarpManager\Form\User\UserNewForm;

use App\Entity\Restriction;
use App\Entity\User;
use App\Entity\Gn;
use App\Entity\Participant;
use App\Entity\Message;

use LarpManager\Repository\ParticipantRepository;

use JasonGrimes\Paginator;


/**
 * LarpManager\Controllers\UserController
 *
 * @author kevin
 *
 */
class UserController
{

	/**
	 * 
	 * @var boolean $isEmailConfirmationRequired
	 */
	private $isEmailConfirmationRequired = true;
	
	/**
	 * 
	 * @var boolean $isPasswordResetEnabled
	 */
	private $isPasswordResetEnabled = true;
	
	/**
	 * Genere un mot de passe aléatoire
	 *
	 * @param number $length
	 * @return string $password
	 */
	protected function generatePassword($length = 10)
	{
		$alphabets = range('A','Z');
		$numbers = range('0','9');
		$additional_characters = array('_','.');
	
		$final_array = array_merge($alphabets,$numbers,$additional_characters);
	
		$password = '';
	
		while($length--) {
			$key = array_rand($final_array);
			$password .= $final_array[$key];
		}
	
		return $password;
	}
	
	/**
	 * Création d'un nouvel utilisateur
	 * 
	 * @param Application $app
	 * @param Request $request
	 */
	public function adminNewAction(Application $app, Request $request)
	{				
		$form = $app['form.factory']->createBuilder(new UserNewForm(), array())
			->add('save','submit', array('label' => "Créer l'utilisateur"))
			->getForm();
		
		$form->handleRequest($request);
		
		if ( $form->isValid() )
		{
			$data = $form->getData();
			
			$plainPassword = $this->generatePassword();
			
			$User = $app['User.manager']->createUser(
					$data['email'],
					$plainPassword,
					$data['Username'],
					array('ROLE_User'));
				
			$User->setIsEnabled(true);
			$app['orm.em']->persist($User);
			
			
			if ( $data['gn'] )
			{
				$participant = new Participant();
				$participant->setUser($User);
				$participant->setGn($data['gn']);
				
				if ( $data['billet'] )
				{
					$participant->setBillet($data['billet']);
				}	
					
				$app['orm.em']->persist($participant);
			}
			
			$app['orm.em']->flush();
			
			$app['notify']->newUser($User, $plainPassword);
			
			$app['session']->getFlashBag()->add('success', 'L\'utilisateur a été ajouté.');
			return $app->redirect($app['url_generator']->generate('homepage'),303);
		}
		return $app['twig']->render('admin/User/new.twig', array(
				'form' => $form->createView()
		));
	}
	
	/**
	 * Choix du personnage par défaut de l'utilisateur
	 * 
	 * @param Application $app
	 * @param Request $request
	 */
	public function personnageDefaultAction(Application $app, Request $request, User $User)
	{
		if ( ! $app['security.authorization_checker']->isGranted('ROLE_ADMIN') && ! $User == $app['User'])
		{
			$app['session']->getFlashBag()->add('error', 'Vous n\'avez pas les droits necessaires pour cette opération.');
			return $app->redirect($app['url_generator']->generate('homepage'),303);
		}

		$form = $app['form.factory']->createBuilder(new UserPersonnageDefaultForm(), $app['User'], array('User_id' => $User->getId()))
			->add('save','submit', array('label' => "Sauvegarder"))
			->getForm();
		
		$form->handleRequest($request);
				
		if ( $form->isValid() )
		{
			$User = $form->getData();
			
			$app['orm.em']->persist($User);
			$app['orm.em']->flush();
			
			$app['session']->getFlashBag()->add('success', 'Vos informations ont été enregistrées.');
			return $app->redirect($app['url_generator']->generate('homepage'),303);
		}
		return $app['twig']->render('public/User/personnageDefault.twig', array(
				'form' => $form->createView(),
				'User' => $User,
		));
	}

	
	/**
	 * Choix des restrictions alimentaires par l'utilisateur
	 */
	public function restrictionAction(Application $app, Request $request)
	{	
		$form = $app['form.factory']->createBuilder(new UserRestrictionForm(), $app['User'])
			->add('save','submit', array('label' => "Sauvegarder"))
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
			
			$app['session']->getFlashBag()->add('success', 'Vos informations ont été enregistrées.');
			return $app->redirect($app['url_generator']->generate('homepage'),303);
		}
		
		
		return $app['twig']->render('public/User/restriction.twig', array(
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Formulaire de participation à un jeu
	 * 
	 * @param Application $app
	 * @param Request $request
	 * @param Gn $gn
	 */
	public function gnParticipeAction(Application $app, Request $request, Gn $gn)
	{
		$form = $app['form.factory']->createBuilder()
			->getForm();
		
		$form->handleRequest($request);
		
		if ( $form->isValid() && (!$gn->getBesoinValidationCi() || $request->request->get('acceptCi') == 'ok') ) {
		    $participant = new Participant();
		    $participant->setUser($app['User']);
		    $participant->setGn($gn);
		    
		    if($gn->getBesoinValidationCi()) {
		        $participant->setValideCiLe(new \DateTime('NOW'));
		    }
		    
		    $app['orm.em']->persist($participant);		    		   
			$app['orm.em']->flush();
			
			$app['session']->getFlashBag()->add('success', 'Vous participez maintenant à '.$gn->getLabel().' !');
			return $app->redirect($app['url_generator']->generate('homepage'),303);
		}
		
		return $app['twig']->render('public/gn/participe.twig', array(
				'gn' => $gn,
				'form' => $form->createView(),
		));
	}
	
	
	/**
	 * Formulaire de validation des cg , si cette validation n'a pas été réalisé à la participation
	 *
	 * @param Application $app
	 * @param Request $request
	 * @param Gn $gn
	 */
	public function gnValidCiAction(Application $app, Request $request, Gn $gn)
	{
	    $form = $app['form.factory']->createBuilder()
	    ->getForm();
	    
	    $form->handleRequest($request);
	    
	    if ( $form->isValid() && $request->request->get('acceptCi') == 'ok')
	    {
	        $participant = $app['User']->getParticipant($gn);
	        $participant->setValideCiLe(new \DateTime('NOW'));
	        	        	        
	        $app['orm.em']->persist($participant);
	        $app['orm.em']->flush();
	        
	        $app['session']->getFlashBag()->add('success', 'Vous avez validé les condition d\'inscription pour '.$gn->getLabel().' !');
	        return $app->redirect($app['url_generator']->generate('homepage'),303);
	    }
	    
	    return $app['twig']->render('public/gn/validation_ci.twig', array(
	        'gn' => $gn,
	        'form' => $form->createView(),
	    ));
	}
		
	/**
	 * Affiche le détail d'un billet d'un utilisateur
	 * 
	 * @param Application $app
	 * @param Request $request
	 * @param UserHasBillet $UserHasBillet
	 */
	public function UserHasBilletDetailAction(Application $app, Request $request, UserHasBillet $UserHasBillet)
	{
		if ( $UserHasBillet->getUser() != $app['User'])
		{
			$app['session']->getFlashBag()->add('error','Vous ne pouvez pas acceder à cette information');
			return $app->redirect($app['url_generator']->generate('homepage'),303);
		}
		
		return $app['twig']->render('public/UserHasBillet/detail.twig', array(
				'UserHasBillet' => $UserHasBillet,
		));
	}
	
	/**
	 * Affiche la liste des billets de l'utilisateur
	 * 
	 * @param Application $app
	 * @param Request $request
	 */
	public function UserHasBilletListAction(Application $app, Request $request)
	{	
		$UserHasBillets = $app['User']->getUserHasBillets();
		
		return $app['twig']->render('public/UserHasBillet/list.twig', array(
				'UserHasBillets' => $UserHasBillets,
		));
	}
	
	/**
	 * Affiche les informations de la fédéGN
	 * 
	 * @param Application $app
	 * @param Request $request
	 */
	public function fedegnAction(Application $app, Request $request)
	{
		return $app['twig']->render('public/User/fedegn.twig', array(
				'etatCivil' => $app['User']->getEtatCivil(),
		));
	}
	
	/**
	 * Enregistrement de l'état-civil
	 *
	 * @param Application $app
	 * @param Request $request
	 * @param unknown $id
	 */
	public function etatCivilAction(Application $app, Request $request)
	{
		$etatCivil = $app['User']->getEtatCivil();
		
		if ( ! $etatCivil )
		{
			$etatCivil = new \App\Entity\EtatCivil();
		}
		
		$form = $app['form.factory']->createBuilder(new EtatCivilForm(), $etatCivil)
			->add('save','submit', array('label' => "Sauvegarder"))
			->getForm();
	
		$form->handleRequest($request);
	
		if ( $form->isValid() )
		{
			$etatCivil = $form->getData();
			$app['User']->setEtatCivil($etatCivil);
	
			$app['orm.em']->persist($app['User']);
			$app['orm.em']->persist($etatCivil);
			$app['orm.em']->flush();
				
			$app['session']->getFlashBag()->add('success', 'Vos informations ont été enregistrées.');
			return $app->redirect($app['url_generator']->generate('homepage'),303);
		}
	
		return $app['twig']->render('public/User/etatCivil.twig', array(
				'form' => $form->createView(),
		));
	}
	
	/**
	 * Création d'un utilisateur
	 * 
	 * @param Request $request
	 * @return User
	 * @throws InvalidArgumentException
	 */
	protected function createUserFromRequest(Application $app, Request $request)
	{
		if ($request->request->get('password') != $request->request->get('confirm_password')) {
			throw new InvalidArgumentException('Passwords don\'t match.');
		}
	
		$User = $app['User.manager']->createUser(
				$request->request->get('email'),
				$request->request->get('password'),
				$request->request->get('name') ?: null,
				array('ROLE_User'));
	
		if ($Username = $request->request->get('Username')) {
			$User->setUsername($Username);
		}
	
		$errors = $app['User.manager']->validate($User);
	
		if (!empty($errors)) {
			throw new InvalidArgumentException(implode("\n", $errors));
		}
	
		return $User;
	}
	
	/**
	 * Affiche le détail de l'utilisateur courant
	 * 
	 * @param Application $app
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function viewSelfAction(Application $app) {
		if (!$app['User']) {
			return $app->redirect($app['url_generator']->generate('User.login'));
		}
	
		return $app->redirect($app['url_generator']->generate('User.view', array('id' => $app['User']->getId())));
	}

	/**
	 * View User action.
	 *
	 * @param Application $app
	 * @param Request $request
	 * @param int $id
	 * @return Response
	 * @throws NotFoundHttpException if no User is found with that ID.
	 */
	public function viewAction(Application $app, Request $request, $id)
	{
		$User = $app['User.manager']->getUser($id);
	
		if (!$User) {
			throw new NotFoundHttpException('No User was found with that ID.');
		}
	
		if (!$User->isEnabled() && !$app['security']->isGranted('ROLE_ADMIN')) {
			throw new NotFoundHttpException('That User is disabled (pending email confirmation).');
		}
	
		return $app['twig']->render('public/User/detail.twig', array(
				'User' => $User
		));
	}
	
	public function likeAction(Application $app, Request $request, User $User)
	{
		if ( $User == $app['User'])
		{
			$app['session']->getFlashBag()->add('error', 'Désolé ... Avez vous vraiment cru que cela allait fonctionner ? un peu de patience !');				
		}
		else
		{
			$User->addCoeur();
			$app['orm.em']->persist($User);
			$app['orm.em']->flush();
			$app['notify']->coeur($app['User'],$User);
			$app['session']->getFlashBag()->add('success', 'Votre coeur a été envoyé !');
		}
		return $app->redirect($app['url_generator']->generate('User.view', array('id' => $User->getId())));
		
	}
		
	/**
	 * Edit User action.
	 *
	 * @param Application $app
	 * @param Request $request
	 * @param int $id
	 * @return Response
	 * @throws NotFoundHttpException if no User is found with that ID.
	 */
	public function editAction(Application $app, Request $request, $id)
	{
		$errors = array();
	
		$User = $app['User.manager']->getUser($id);
		
		if ( !$User ) 
		{
			throw new NotFoundHttpException('No User was found with that ID.');
		}
		
		if ($request->isMethod('POST')) 
		{
			$User->setEmail($request->request->get('email'));
			if ($request->request->has('Username')) {
				$User->setUsername($request->request->get('Username'));
			}
			if ($request->request->get('password')) {
				if ($request->request->get('password') != $request->request->get('confirm_password')) {
					$errors['password'] = 'Passwords don\'t match.';
				} else if ($error = $app['User.manager']->validatePasswordStrength($User, $request->request->get('password'))) {
					$errors['password'] = $error;
				} else {
					$app['User.manager']->setUserPassword($User, $request->request->get('password'));
				}
			}
			if ($app['security']->isGranted('ROLE_ADMIN') && $request->request->has('roles')) {
				$User->setRoles($request->request->get('roles'));
			}
		
			$errors += $app['User.manager']->validate($User);
			
			if (empty($errors)) {
				$app['User.manager']->update($User);
				$msg = 'Saved account information.' . ($request->request->get('password') ? ' Changed password.' : '');
				$app['session']->getFlashBag()->set('alert', $msg);
			}
			
		}
	
		return $app['twig']->render('admin/User/update.twig', array(
				'error' => implode("\n", $errors),
				'User' => $User,
				'available_roles' => $app['larp.manager']->getAvailableRoles()
		));
	}
	
	
	/**
	 * Login
	 * 
	 * @param Application $app
	 * @param Request $request
	 */
	public function loginAction(Application $app, Request $request)
	{
		$authException = $app['User.last_auth_exception']($request);
		
		if ($authException instanceof DisabledException) {
			// This exception is thrown if (!$User->isEnabled())
			// Warning: Be careful not to disclose any User information besides the email address at this point.
			// The Security system throws this exception before actually checking if the password was valid.
			$User = $app['User.manager']->refreshUser($authException->getUser());
		
			return $app['twig']->render('User/login-confirmation-needed.twig', array(
					'email' => $User->getEmail(),
					'fromAddress' => $app['User.mailer']->getFromAddress(),
					'resendUrl' => $app['url_generator']->generate('User.resend-confirmation'),
			));
		}
		
		return $app['twig']->render('User/login.twig', array(
				'error' => $authException ? $authException->getMessageKey() : null,
				'last_Username' => $app['session']->get('_security.last_Username'),
				'allowRememberMe' => isset($app['security.remember_me.response_listener']),
		));
	}
	
	/**
	 * Liste des utilisateurs
	 * 
	 * @param Application $app
	 * @param Request $request
	 */
	public function adminListAction(Application $app, Request $request)
	{
		$order_by = $request->get('order_by') ?: 'Username';
		$order_dir = $request->get('order_dir') == 'DESC' ? 'DESC' : 'ASC';
		$limit = (int)($request->get('limit') ?: 50);
		$page = (int)($request->get('page') ?: 1);
		$offset = ($page - 1) * $limit;
		$criteria = array();
		$type= null;
		$value = null;

		$form = $app['form.factory']->createBuilder(new UserFindForm())->getForm();
		
		$form->handleRequest($request);
				
		if ( $form->isValid() )
		{
			$data = $form->getData();
			$type = $data['type'];
			$value = $data['value'];
		}
		
		$repo = $app['orm.em']->getRepository('\App\Entity\User');
		$Users = $repo->findList(
						$type,
						$value,
						array( 'by' =>  $order_by, 'dir' => $order_dir),
						$limit,
						$offset);
		
		$numResults = $repo->findCount($criteria);

		$paginator = new Paginator($numResults, $limit, $page,
				$app['url_generator']->generate('User.admin.list') . '?page=(:num)&limit=' . $limit . '&order_by=' . $order_by . '&order_dir=' . $order_dir
				);

		return $app['twig']->render('admin/User/list.twig', array(
				'Users' => $Users,
				'paginator' => $paginator,
				'form' => $form->createView(),
		));
	}
		
	/**
	 * Enregistre un utilisateur
	 * 
	 * @param Application $app
	 * @param Request $request
	 * @throws InvalidArgumentException
	 */
	public function registerAction(Application $app, Request $request)
	{
		if ($request->isMethod('POST')) {
			try {
				$User = $this->createUserFromRequest($app, $request);
				
				if ($error = $app['User.manager']->validatePasswordStrength($User, $request->request->get('password'))) {
					throw new InvalidArgumentException($error);
				}
				if ($this->isEmailConfirmationRequired) {
					$User->setEnabled(false);
					$User->setConfirmationToken($app['User.tokenGenerator']->generateToken());
				}
				$app['User.manager']->insert($User);
		
				if ($this->isEmailConfirmationRequired) {
					// Send email confirmation.
					$app['User.mailer']->sendConfirmationMessage($User);
		
					// Render the "go check your email" page.
					return $app['twig']->render('User/register-confirmation-sent.twig', array(
						'email' => $User->getEmail(),
					));
				} else {
					// Log the User in to the new account.
					$app['User.manager']->loginAsUser($User);
		
					$app['session']->getFlashBag()->set('success', 'Votre compte a été créé ! vous pouvez maintenant rejoindre un groupe et créer votre personnage');
		
					return $app->redirect($app['url_generator']->generate('homepage'));
				}
		
			} catch (InvalidArgumentException $e) {
				$error = $e->getMessage();
			}
		}
		
		return $app['twig']->render('User/register.twig', array(
				'error' => isset($error) ? $error : null,
				'name' => $request->request->get('name'),
				'email' => $request->request->get('email'),
				'Username' => $request->request->get('Username'),
		));
	}
	
	/**
	 * Confirmation de l'adresse email
	 *
	 * @param Application $app
	 * @param Request $request
	 * @param string $token
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function confirmEmailAction(Application $app, Request $request, $token)
	{
		$repo = $app['orm.em']->getRepository('\App\Entity\User');
		$User = $repo->findOneByConfirmationToken($token);
		
		if (!$User) {
			$app['session']->getFlashBag()->set('alert', 'Désolé, votre lien de confirmation a expiré.');
			return $app->redirect($app['url_generator']->generate('User.login'));
		}
		$User->setConfirmationToken(null);
		$User->setEnabled(true);
		$app['orm.em']->persist($User);
		$app['orm.em']->flush();
		
		$app['User.manager']->loginAsUser($User);
		$app['session']->getFlashBag()->set('alert', 'Merci ! Votre compte a été activé.');
		return $app->redirect($app['url_generator']->generate('newUser.step1', array('id' => $User->getId())));
	}
	
	/**
	 * Renvoyer un email de confirmation
	 *
	 * @param Application $app
	 * @param Request $request
	 * @return mixed
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function resendConfirmationAction(Application $app, Request $request)
	{
		$email = $request->request->get('email');
		
		$repo = $app['orm.em']->getRepository('\App\Entity\User');
		$User = $repo->findOneByEmail($email);
		
		if (!$User) {
			throw new NotFoundHttpException('Aucun compte n\'a été trouvé avec cette adresse email.');
		}
		if (!$User->getConfirmationToken()) {
			$User->setConfirmationToken($app['User.tokenGenerator']->generateToken());
			$app['orm.em']->persist($User);
			$app['orm.em']->flush();
		}
		$app['User.mailer']->sendConfirmationMessage($User);

		return $app['twig']->render('User/register-confirmation-sent.twig', array(
			'email' => $User->getEmail(),
		));
	}
	

	/**
	 * Traitement mot de passe oublié
	 * 
	 * @param Application $app
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function forgotPasswordAction(Application $app, Request $request)
	{
		if (!$this->isPasswordResetEnabled) {
			throw new NotFoundHttpException('Password resetting is not enabled.');
		}
		$error = null;
		if ($request->isMethod('POST')) {
			$email = $request->request->get('email');
			
			$repo = $app['orm.em']->getRepository('\App\Entity\User');
			$User = $repo->findOneByEmail($email);
			
			if ($User) {
				// Initialize and send the password reset request.
				$User->setTimePasswordResetRequested(time());
				if (!$User->getConfirmationToken()) {
					$User->setConfirmationToken($app['User.tokenGenerator']->generateToken());
				}
				$app['orm.em']->persist($User);
				$app['orm.em']->flush();
				
				$app['User.mailer']->sendResetMessage($User);
				$app['session']->getFlashBag()->set('alert', 'Les instructions pour enregistrer votre mot de passe ont été envoyé par mail.');
				$app['session']->set('_security.last_Username', $email);
				return $app->redirect($app['url_generator']->generate('User.login'));
			}
			$error = 'No User account was found with that email address.';
		} else {
			$email = $request->request->get('email') ?: ($request->query->get('email') ?: $app['session']->get('_security.last_Username'));
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';
		}
		return $app['twig']->render('User/forgot-password.twig', array(
				'email' => $email,
				'fromAddress' => $app['User.mailer']->getFromAddress(),
				'error' => $error,
		));
	}
	

	/**
	 * @param Application $app
	 * @param Request $request
	 * @param string $token
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function resetPasswordAction(Application $app, Request $request, $token)
	{
		if (!$this->isPasswordResetEnabled) {
			throw new NotFoundHttpException('Password resetting is not enabled.');
		}
		$tokenExpired = false;
		
		$repo = $app['orm.em']->getRepository('\App\Entity\User');
		$User = $repo->findOneByConfirmationToken($token);
		
		if (!$User) {
			$tokenExpired = true;
		} else if ($User->isPasswordResetRequestExpired($app['config']['User']['passwordReset']['tokenTTL'])) {
			$tokenExpired = true;
		}
		if ($tokenExpired) {
			$app['session']->getFlashBag()->set('alert', 'Sorry, your password reset link has expired.');
			return $app->redirect($app['url_generator']->generate('User.login'));
		}
		$error = '';
		if ($request->isMethod('POST')) {
			// Validate the password
			$password = $request->request->get('password');
			if ($password != $request->request->get('confirm_password')) {
				$error = 'Passwords don\'t match.';
			} else if ($error = $app['User.manager']->validatePasswordStrength($User, $password)) {
				;
			} else {
				// Set the password and log in.
				$app['User.manager']->setUserPassword($User, $password);
				$User->setConfirmationToken(null);
				$User->setEnabled(true);
				$app['orm.em']->persist($User);
				$app['orm.em']->flush();
				$app['User.manager']->loginAsUser($User);
				$app['session']->getFlashBag()->set('alert', 'Your password has been reset and you are now signed in.');
				return $app->redirect($app['url_generator']->generate('User.view', array('id' => $User->getId())));
			}
		}
		return $app['twig']->render('User/reset-password.twig', array(
				'User' => $User,
				'token' => $token,
				'error' => $error,
		));
	}
	
	/**
	 * Met a jours les droits des utilisateurs
	 * 
	 * @param Application $app
	 * @param Request $request
	 */
	public function rightAction(Application $app, Request $request)
	{
		$Users = $app['User.manager']->findAll();
		
		if ( $request->getMethod() == 'POST')
		{			
			$newRoles = $request->get('User');
			
			foreach( $Users as $User ) 
			{			
				$User->setRoles(array_keys($newRoles[$User->getId()]));
				$app['orm.em']->persist($User);
			}
			$app['orm.em']->flush();
		}
		
		// trouve tous les rôles
		return $app['twig']->render('User/right.twig', array(
				'Users' => $Users,
				'roles' => $app['larp.manager']->getAvailableRoles())
		);
	}
}