<?php

namespace App\Controller;

use App\Entity\Gn;
use App\Entity\Participant;
use App\Entity\Restriction;
use App\Entity\User;
use App\Form\Entity\UserSearch;
use App\Form\UserFindForm;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    private bool $isEmailConfirmationRequired = true;

    private bool $isPasswordResetEnabled = true;

    /**
     * Genere un mot de passe aléatoire.
     *
     * @param number $length
     *
     * @return string $password
     */
    protected function generatePassword($length = 10): string
    {
        $alphabets = range('A', 'Z');
        $numbers = range('0', '9');
        $additional_characters = ['_', '.'];

        $final_array = array_merge($alphabets, $numbers, $additional_characters);

        $password = '';

        while ($length--) {
            $key = array_rand($final_array);
            $password .= $final_array[$key];
        }

        return $password;
    }

    /**
     * Création d'un nouvel utilisateur.
     */
    #[Route('/user/admin/new', name: 'user.admin.new')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    public function adminNewAction(Request $request)
    {
        $form = $app['form.factory']->createBuilder(new UserNewForm(), [])
            ->add('save', 'submit', ['label' => "Créer l'utilisateur"])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $plainPassword = $this->generatePassword();

            $User = $app['User.manager']->createUser(
                $data['email'],
                $plainPassword,
                $data['Username'],
                ['ROLE_USER']);

            $User->setIsEnabled(true);
            $app['orm.em']->persist($User);

            if ($data['gn']) {
                $participant = new Participant();
                $participant->setUser($User);
                $participant->setGn($data['gn']);

                if ($data['billet']) {
                    $participant->setBillet($data['billet']);
                }

                $app['orm.em']->persist($participant);
            }

            $app['orm.em']->flush();

            $app['notify']->newUser($User, $plainPassword);

            $app['session']->getFlashBag()->add('success', 'L\'utilisateur a été ajouté.');

            return $app->redirect($app['url_generator']->generate('homepage'), 303);
        }

        return $app['twig']->render('admin/User/new.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Choix du personnage par défaut de l'utilisateur.
     */
    public function personnageDefaultAction(Application $app, Request $request, User $User)
    {
        if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN') && !$User == $app['User']) {
            $app['session']->getFlashBag()->add('error', 'Vous n\'avez pas les droits necessaires pour cette opération.');

            return $app->redirect($app['url_generator']->generate('homepage'), 303);
        }

        $form = $app['form.factory']->createBuilder(new UserPersonnageDefaultForm(), $app['User'], ['User_id' => $User->getId()])
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $User = $form->getData();

            $app['orm.em']->persist($User);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Vos informations ont été enregistrées.');

            return $app->redirect($app['url_generator']->generate('homepage'), 303);
        }

        return $app['twig']->render('public/User/personnageDefault.twig', [
            'form' => $form->createView(),
            'User' => $User,
        ]);
    }

    /**
     * Choix des restrictions alimentaires par l'utilisateur.
     */
    #[Route('/user/restriction', name: 'user.restriction')]
    public function restrictionAction(Application $app, Request $request)
    {
        $form = $app['form.factory']->createBuilder(new UserRestrictionForm(), $app['User'])
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $User = $form->getData();
            $newRestriction = $form->get('new_restriction')->getData();
            if ($newRestriction) {
                $restriction = new Restriction();
                $restriction->setUserRelatedByAuteurId($app['User']);
                $restriction->setLabel($newRestriction);

                $app['orm.em']->persist($restriction);
                $User->addRestriction($restriction);
            }

            $app['orm.em']->persist($User);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Vos informations ont été enregistrées.');

            return $app->redirect($app['url_generator']->generate('homepage'), 303);
        }

        return $app['twig']->render('public/User/restriction.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire de participation à un jeu.
     */
    public function gnParticipeAction(Application $app, Request $request, Gn $gn)
    {
        $form = $app['form.factory']->createBuilder()
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid() && (!$gn->getBesoinValidationCi() || 'ok' == $request->request->get('acceptCi'))) {
            $participant = new Participant();
            $participant->setUser($app['User']);
            $participant->setGn($gn);

            if ($gn->getBesoinValidationCi()) {
                $participant->setValideCiLe(new \DateTime('NOW'));
            }

            $app['orm.em']->persist($participant);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Vous participez maintenant à '.$gn->getLabel().' !');

            return $app->redirect($app['url_generator']->generate('homepage'), 303);
        }

        return $app['twig']->render('public/gn/participe.twig', [
            'gn' => $gn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire de validation des cg , si cette validation n'a pas été réalisé à la participation.
     */
    public function gnValidCiAction(Application $app, Request $request, Gn $gn)
    {
        $form = $app['form.factory']->createBuilder()
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid() && 'ok' == $request->request->get('acceptCi')) {
            $participant = $app['User']->getParticipant($gn);
            $participant->setValideCiLe(new \DateTime('NOW'));

            $app['orm.em']->persist($participant);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Vous avez validé les condition d\'inscription pour '.$gn->getLabel().' !');

            return $app->redirect($app['url_generator']->generate('homepage'), 303);
        }

        return $app['twig']->render('public/gn/validation_ci.twig', [
            'gn' => $gn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche le détail d'un billet d'un utilisateur.
     */
    public function UserHasBilletDetailAction(Application $app, Request $request, UserHasBillet $UserHasBillet)
    {
        if ($UserHasBillet->getUser() != $app['User']) {
            $app['session']->getFlashBag()->add('error', 'Vous ne pouvez pas acceder à cette information');

            return $app->redirect($app['url_generator']->generate('homepage'), 303);
        }

        return $app['twig']->render('public/UserHasBillet/detail.twig', [
            'UserHasBillet' => $UserHasBillet,
        ]);
    }

    /**
     * Affiche la liste des billets de l'utilisateur.
     */
    public function UserHasBilletListAction(Application $app, Request $request)
    {
        $UserHasBillets = $app['User']->getUserHasBillets();

        return $app['twig']->render('public/UserHasBillet/list.twig', [
            'UserHasBillets' => $UserHasBillets,
        ]);
    }

    /**
     * Affiche les informations de la fédéGN.
     */
    #[Route('/user/fedegn', name: 'user.fedegn')]
    public function fedegnAction(Application $app, Request $request)
    {
        return $app['twig']->render('public/User/fedegn.twig', [
            'etatCivil' => $app['User']->getEtatCivil(),
        ]);
    }

    /**
     * Enregistrement de l'état-civil.
     */
    #[Route('/user/etatCivil', name: 'user.etatCivil')]
    public function etatCivilAction(Application $app, Request $request)
    {
        $etatCivil = $app['User']->getEtatCivil();

        if (!$etatCivil) {
            $etatCivil = new \App\Entity\EtatCivil();
        }

        $form = $app['form.factory']->createBuilder(new EtatCivilForm(), $etatCivil)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $etatCivil = $form->getData();
            $app['User']->setEtatCivil($etatCivil);

            $app['orm.em']->persist($app['User']);
            $app['orm.em']->persist($etatCivil);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Vos informations ont été enregistrées.');

            return $app->redirect($app['url_generator']->generate('homepage'), 303);
        }

        return $app['twig']->render('public/User/etatCivil.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Création d'un utilisateur.
     *
     * @return User
     *
     * @throws \InvalidArgumentException
     */
    protected function createUserFromRequest(Application $app, Request $request)
    {
        if ($request->request->get('password') != $request->request->get('confirm_password')) {
            throw new \InvalidArgumentException("Passwords don't match.");
        }

        $User = $app['User.manager']->createUser(
            $request->request->get('email'),
            $request->request->get('password'),
            $request->request->get('name') ?: null,
            ['ROLE_USER']);

        if ($Username = $request->request->get('Username')) {
            $User->setUsername($Username);
        }

        $errors = $app['User.manager']->validate($User);

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode("\n", $errors));
        }

        return $User;
    }

    /**
     * Affiche le détail de l'utilisateur courant.
     */
    #[Route('/self', name: 'user.self')]
    public function viewSelfAction(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->forward(
            'App\Controller\UserController::viewAction',
            [
                'id' => $user->getId(),
            ]
        );
    }

    /**
     * View User action.
     *
     * @return Response
     *
     * @throws NotFoundHttpException if no User is found with that ID
     */
    #[Route('/user/{id}', name: 'user.view')]
    public function viewAction(Request $request, ManagerRegistry $managerRegistry, int $id)
    {
        $user = $managerRegistry->getRepository(User::class)->find($id);

        if (!$user) {
            throw new NotFoundHttpException('No user was found with that ID.');
        }

        if (!$user->isEnabled() && !$this->isGranted('ROLE_ADMIN')) {
            throw new NotFoundHttpException('That User is disabled (pending email confirmation).');
        }

        return $this->render('user/detail.twig', ['user' => $user]);
    }

    #[Route('/user/like', name: 'user.like')]
    public function likeAction(Application $app, Request $request, User $User)
    {
        if ($User == $app['User']) {
            $app['session']->getFlashBag()->add('error', 'Désolé ... Avez vous vraiment cru que cela allait fonctionner ? un peu de patience !');
        } else {
            $User->addCoeur();
            $app['orm.em']->persist($User);
            $app['orm.em']->flush();
            $app['notify']->coeur($app['User'], $User);
            $app['session']->getFlashBag()->add('success', 'Votre coeur a été envoyé !');
        }

        return $app->redirect($app['url_generator']->generate('User.view', ['id' => $User->getId()]));
    }

    /**
     * Edit User action.
     *
     * @param int $id
     *
     * @return Response
     *
     * @throws NotFoundHttpException if no User is found with that ID
     */
    #[Route('/user/{id}/edit', name: 'user.edit')]
    public function editAction(Request $request, $id)
    {
        $errors = [];

        $User = $app['User.manager']->getUser($id);

        if (!$User) {
            throw new NotFoundHttpException('No User was found with that ID.');
        }

        if ($request->isMethod('POST')) {
            $User->setEmail($request->request->get('email'));
            if ($request->request->has('Username')) {
                $User->setUsername($request->request->get('Username'));
            }

            if ($request->request->get('password')) {
                if ($request->request->get('password') != $request->request->get('confirm_password')) {
                    $errors['password'] = "Passwords don't match.";
                } elseif ($error = $app['User.manager']->validatePasswordStrength($User, $request->request->get('password'))) {
                    $errors['password'] = $error;
                } else {
                    $app['User.manager']->setUserPassword($User, $request->request->get('password'));
                }
            }

            if ($app['security']->isGranted('ROLE_ADMIN') && $request->request->has('roles')) {
                $User->setRoles($request->request->get('roles'));
            }

            $errors += $app['User.manager']->validate($User);

            if ([] === $errors) {
                $app['User.manager']->update($User);
                $msg = 'Saved account information.'.($request->request->get('password') ? ' Changed password.' : '');
                $app['session']->getFlashBag()->set('alert', $msg);
            }
        }

        return $app['twig']->render('admin/User/update.twig', [
            'error' => implode("\n", $errors),
            'User' => $User,
            'available_roles' => $app['larp.manager']->getAvailableRoles(),
        ]);
    }

    #[Route('/user', name: 'user.login')]
    public function loginAction(Application $app, Request $request)
    {
        $authException = $app['User.last_auth_exception']($request);

        if ($authException instanceof DisabledException) {
            // This exception is thrown if (!$User->isEnabled())
            // Warning: Be careful not to disclose any User information besides the email address at this point.
            // The Security system throws this exception before actually checking if the password was valid.
            $User = $app['User.manager']->refreshUser($authException->getUser());

            return $app['twig']->render('User/login-confirmation-needed.twig', [
                'email' => $User->getEmail(),
                'fromAddress' => $app['User.mailer']->getFromAddress(),
                'resendUrl' => $app['url_generator']->generate('User.resend-confirmation'),
            ]);
        }

        return $app['twig']->render('User/login.twig', [
            'error' => $authException ? $authException->getMessageKey() : null,
            'last_Username' => $app['session']->get('_security.last_Username'),
            'allowRememberMe' => isset($app['security.remember_me.response_listener']),
        ]);
    }

    /**
     * Liste des utilisateurs.
     */
    #[Route('/user/admin/list', name: 'user.admin.list')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    public function adminListAction(Request $request, UserRepository $userRepository): Response
    {
        $type = null;
        $value = null;

        $userSearch = new UserSearch();
        $form = $this->createForm(UserFindForm::class, $userSearch);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data->getType();
            $value = $data->getValue();
        }

        /** Sample with QUERY
         * $query = $userRepository->createQueryBuilder('u')
         * ->orderBy('u.'.$orderBy, $orderDir);.
         *
         * if ($type) {
         * $query->where($type.' = :type');
         * $query->setParameter('type', $value);
         * }
         * $paginator = $userRepository->findPaginatedQuery(
         * $query->getQuery()
         * );
         */
        $alias = UserRepository::getEntityAlias();

        $criterias = [];
        if (!empty($value)) {
            if (empty($type) || '*' === $type) {
                if (is_numeric($value)) {
                    $criterias[] = Criteria::create()->where(
                        Criteria::expr()?->contains($alias.'.id', $value)
                    );
                } else {
                    $criterias[] = Criteria::create()->where(
                        Criteria::expr()?->contains($alias.'.username', $value)
                    )->orWhere(
                        Criteria::expr()?->contains($alias.'.email', $value)
                    )->orWhere(
                        Criteria::expr()?->contains($alias.'.roles', $value)
                    );
                }
            } else {
                $criterias[] = Criteria::create()->andWhere(
                    Criteria::expr()?->contains($alias.'.'.$type, $value)
                );
            }
        }

        $orderBy = $this->getRequestOrder(
            defOrderBy: 'username',
            alias: $alias,
            allowedFields: $userRepository->getFieldNames()
        );

        $paginator = $userRepository->getPaginator(
            limit: $this->getRequestLimit(),
            page: $this->getRequestPage(),
            orderBy: $orderBy,
            alias: $alias,
            criterias: $criterias
        );

        return $this->render(
            'user/list.twig',
            [
                'paginator' => $paginator,
                'form' => $form->createView(),
            ]
        );
    }

    #[Route('/user', name: 'user.register')]
    public function registerAction(Application $app, Request $request)
    {
        if ($request->isMethod('POST')) {
            try {
                $User = $this->createUserFromRequest($app, $request);

                if ($error = $app['User.manager']->validatePasswordStrength($User, $request->request->get('password'))) {
                    throw new \InvalidArgumentException($error);
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
                    return $app['twig']->render('User/register-confirmation-sent.twig', [
                        'email' => $User->getEmail(),
                    ]);
                } else {
                    // Log the User in to the new account.
                    $app['User.manager']->loginAsUser($User);

                    $app['session']->getFlashBag()->set('success', 'Votre compte a été créé ! vous pouvez maintenant rejoindre un groupe et créer votre personnage');

                    return $app->redirect($app['url_generator']->generate('homepage'));
                }
            } catch (\InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }

        return $app['twig']->render('User/register.twig', [
            'error' => isset($error) ? $error : null,
            'name' => $request->request->get('name'),
            'email' => $request->request->get('email'),
            'Username' => $request->request->get('Username'),
        ]);
    }

    /**
     * Confirmation de l'adresse email.
     *
     * @param string $token
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function confirmEmailAction(Application $app, Request $request, $token)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\User::class);
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

        return $app->redirect($app['url_generator']->generate('newUser.step1', ['id' => $User->getId()]));
    }

    /**
     * Renvoyer un email de confirmation.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function resendConfirmationAction(Application $app, Request $request)
    {
        $email = $request->request->get('email');

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\User::class);
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

        return $app['twig']->render('User/register-confirmation-sent.twig', [
            'email' => $User->getEmail(),
        ]);
    }

    #[Route('/user', name: 'user.forgot-password')]
    public function forgotPasswordAction(Application $app, Request $request)
    {
        if (!$this->isPasswordResetEnabled) {
            throw new NotFoundHttpException('Password resetting is not enabled.');
        }

        $error = null;
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');

            $repo = $app['orm.em']->getRepository('\\'.\App\Entity\User::class);
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
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = '';
            }
        }

        return $app['twig']->render('User/forgot-password.twig', [
            'email' => $email,
            'fromAddress' => $app['User.mailer']->getFromAddress(),
            'error' => $error,
        ]);
    }

    /**
     * @param string $token
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function resetPasswordAction(Application $app, Request $request, $token)
    {
        if (!$this->isPasswordResetEnabled) {
            throw new NotFoundHttpException('Password resetting is not enabled.');
        }

        $tokenExpired = false;

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\User::class);
        $User = $repo->findOneByConfirmationToken($token);

        if (!$User) {
            $tokenExpired = true;
        } elseif ($User->isPasswordResetRequestExpired($app['config']['User']['passwordReset']['tokenTTL'])) {
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
                $error = "Passwords don't match.";
            } elseif ($error = $app['User.manager']->validatePasswordStrength($User, $password)) {
            } else {
                // Set the password and log in.
                $app['User.manager']->setUserPassword($User, $password);
                $User->setConfirmationToken(null);
                $User->setEnabled(true);
                $app['orm.em']->persist($User);
                $app['orm.em']->flush();
                $app['User.manager']->loginAsUser($User);
                $app['session']->getFlashBag()->set('alert', 'Your password has been reset and you are now signed in.');

                return $app->redirect($app['url_generator']->generate('User.view', ['id' => $User->getId()]));
            }
        }

        return $app['twig']->render('User/reset-password.twig', [
            'User' => $User,
            'token' => $token,
            'error' => $error,
        ]);
    }

    /**
     * Met a jours les droits des utilisateurs.
     */
    public function rightAction(Application $app, Request $request)
    {
        $Users = $app['User.manager']->findAll();

        if ('POST' == $request->getMethod()) {
            $newRoles = $request->get('User');

            foreach ($Users as $User) {
                $User->setRoles(array_keys($newRoles[$User->getId()]));
                $app['orm.em']->persist($User);
            }

            $app['orm.em']->flush();
        }

        // trouve tous les rôles
        return $app['twig']->render('User/right.twig', [
                'Users' => $Users,
                'roles' => $app['larp.manager']->getAvailableRoles()]
        );
    }
}
