<?php

namespace App\Controller;

use App\Entity\Gn;
use App\Entity\Participant;
use App\Entity\Restriction;
use App\Entity\User;
use App\Form\Entity\UserSearch;
use App\Form\User\UserNewForm;
use App\Form\EtatCivilForm;
use App\Form\UserFindForm;
use App\Form\UserRestrictionForm;
use App\Form\UserPersonnageDefaultForm;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
    public function adminNewAction(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ) {
        $form = $this->createForm(UserNewForm::class, [])
            ->add('save', SubmitType::class, ['label' => "Créer l'utilisateur"]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $user = new User();
            $user->setIsEnabled(true);
            $user->setEmail($data['email']);
            $user->setUsername($data['username']);
            $user->setRoles([User::ROLE_USER]);

            $plainPassword = $this->generatePassword();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);

            if ($data['gn']) {
                $participant = new Participant();
                $participant->setUser($user);
                $participant->setGn($data['gn']);

                if ($data['billet']) {
                    $participant->setBillet($data['billet']);
                }

                $entityManager->persist($participant);
            }

            $entityManager->flush();

            // TODO // NOTIFY $app['notify']->newUser($user, $plainPassword);

            $this->addFlash('success', 'L\'utilisateur a été ajouté.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render(
            'user/new.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Choix du personnage par défaut de l'utilisateur.
     */
    public function personnageDefaultAction(EntityManagerInterface $entityManager, Request $request, User $User)
    {
        if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN') && !$User == $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas les droits necessaires pour cette opération.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        $form = $this->createForm(UserPersonnageDefaultForm::class, $this->getUser(), ['User_id' => $User->getId()])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $User = $form->getData();

            $entityManager->persist($User);
            $entityManager->flush();

            $this->addFlash('success', 'Vos informations ont été enregistrées.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('user/personnageDefault.twig', [
            'form' => $form->createView(),
            'User' => $User,
        ]);
    }

    /**
     * Choix des restrictions alimentaires par l'utilisateur.
     */
    #[Route('/user/restriction', name: 'user.restriction')]
    public function restrictionAction(EntityManagerInterface $entityManager, Request $request)
    {
        $form = $this->createForm(UserRestrictionForm::class, $this->getUser())
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $User = $form->getData();
            $newRestriction = $form->get('new_restriction')->getData();
            if ($newRestriction) {
                $restriction = new Restriction();
                $restriction->setUserRelatedByAuteurId($this->getUser());
                $restriction->setLabel($newRestriction);

                $entityManager->persist($restriction);
                $User->addRestriction($restriction);
            }

            $entityManager->persist($User);
            $entityManager->flush();

            $this->addFlash('success', 'Vos informations ont été enregistrées.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('user/restriction.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire de participation à un jeu.
     */
    public function gnParticipeAction(EntityManagerInterface $entityManager, Request $request, Gn $gn)
    {
        $form = $this->createForm();

        $form->handleRequest($request);

        if ($form->isValid() && (!$gn->getBesoinValidationCi() || 'ok' == $request->request->get('acceptCi'))) {
            $participant = new Participant();
            $participant->setUser($this->getUser());
            $participant->setGn($gn);

            if ($gn->getBesoinValidationCi()) {
                $participant->setValideCiLe(new \DateTime('NOW'));
            }

            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Vous participez maintenant à '.$gn->getLabel().' !');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('gn/participe.twig', [
            'gn' => $gn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire de validation des cg , si cette validation n'a pas été réalisé à la participation.
     */
    #[Route('/user/{gn}/validationci', name: 'user.gn.validationci')]
    public function gnValidCiAction(EntityManagerInterface $entityManager, Request $request, Gn $gn)
    {
        $form = $this->createFormBuilder()->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && 'ok' == $request->request->get('acceptCi')) {
            $participant = $this->getUser()->getParticipant($gn);
            $participant->setValideCiLe(new \DateTime('NOW'));

            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Vous avez validé les condition d\'inscription pour '.$gn->getLabel().' !');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('gn/validation_ci.twig', [
            'gn' => $gn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche le détail d'un billet d'un utilisateur.
     */
    public function UserHasBilletDetailAction(EntityManagerInterface $entityManager, Request $request, UserHasBillet $UserHasBillet)
    {
        if ($UserHasBillet->getUser() != $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas acceder à cette information');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('UserHasBillet/detail.twig', [
            'UserHasBillet' => $UserHasBillet,
        ]);
    }

    /**
     * Affiche la liste des billets de l'utilisateur.
     */
    public function UserHasBilletListAction(EntityManagerInterface $entityManager, Request $request)
    {
        $UserHasBillets = $this->getUser()->getUserHasBillets();

        return $this->render('UserHasBillet/list.twig', [
            'UserHasBillets' => $UserHasBillets,
        ]);
    }

    /**
     * Affiche les informations de la fédéGN.
     */
    #[Route('/user/fedegn', name: 'user.fedegn')]
    public function fedegnAction(EntityManagerInterface $entityManager, Request $request)
    {
        return $this->render('user/fedegn.twig', [
            'etatCivil' => $this->getUser()->getEtatCivil(),
        ]);
    }

    /**
     * Enregistrement de l'état-civil.
     */
    #[Route('/user/etatCivil', name: 'user.etatCivil')]
    public function etatCivilAction(EntityManagerInterface $entityManager, Request $request)
    {
        $etatCivil = $this->getUser()->getEtatCivil();

        if (!$etatCivil) {
            $etatCivil = new \App\Entity\EtatCivil();
        }

        $form = $this->createForm(EtatCivilForm::class, $etatCivil)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $etatCivil = $form->getData();
            $this->getUser()->setEtatCivil($etatCivil);

            $entityManager->persist($this->getUser());
            $entityManager->persist($etatCivil);
            $entityManager->flush();

            $this->addFlash('success', 'Vos informations ont été enregistrées.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('user/etatCivil.twig', [
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
    protected function createUserFromRequest(EntityManagerInterface $entityManager, Request $request)
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
     * @throws NotFoundHttpException if no User is found with that ID
     */
    #[Route('/user/{user}', name: 'user.view')]
    public function viewAction(Request $request, #[MapEntity] User $user): Response
    {
        if (!$user) {
            throw new NotFoundHttpException('No user was found with that ID.');
        }

        if (!$user->isEnabled() && !$this->isGranted('ROLE_ADMIN')) {
            throw new NotFoundHttpException('That User is disabled (pending email confirmation).');
        }

        return $this->render('user/detail.twig', ['user' => $user]);
    }

    #[Route('/user/like', name: 'user.like')]
    public function likeAction(EntityManagerInterface $entityManager, Request $request, User $User)
    {
        if ($User == $this->getUser()) {
            $this->addFlash('error', 'Désolé ... Avez vous vraiment cru que cela allait fonctionner ? un peu de patience !');
        } else {
            $User->addCoeur();
            $entityManager->persist($User);
            $entityManager->flush();
            // NOTIFY $app['notify']->coeur($this->getUser(), $User);
            $this->addFlash('success', 'Votre coeur a été envoyé !');
        }

        return $this->redirectToRoute('User.view', ['id' => $User->getId()]);
    }

    /**
     * Edit User action.
     *
     * @throws NotFoundHttpException if no User is found with that ID
     */
    #[Route('/user/{user}/edit', name: 'user.edit')]
    public function editAction(Request $request, #[MapEntity] ?User $user, UserPasswordHasherInterface $passwordHasher, UserRepository $repository): Response
    {
        $errors = [];

        if (!$user) {
            throw new NotFoundHttpException('No User was found with that ID.');
        }

        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'));
            if ($request->request->has('username')) {
                $user->setUsername($request->request->get('username'));
            }

            $password = $request->request->get('password');
            if ($password) {
                $hashedPassword = $passwordHasher->hashPassword($user, $password);

                if ($password !== $request->request->get('confirm_password')) {
                    $errors['password'] = "Passwords don't match.";
                } elseif ($error = $user->validatePasswordStrength($password)) {
                    $errors['password'] = $error;
                } else {
                    $user->setPassword($hashedPassword);
                }
            }

            $roles = $request->request->all('roles');
            if (
                !empty($roles)
                && $this->isGranted(User::ROLE_ADMIN)
            ) {
                $user->setRoles($roles);
            }

            if ($repository->emailExists($user)) {
                $errors[] = "L'adresse e-mail existe déjà";
            }

            if ($repository->usernameExists($user)) {
                $errors[] = "Nom d'utilisateur existe déjà";
            }

            if ([] === $errors) {
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                $msg = 'Saved account information.'.($request->request->get('password') ? ' Changed password.' : '');
                $this->addFlash('alert', $msg);
            }
        }

        $availableLabels = [];
        foreach (User::getAvailableRoles() as $availableLabel) {
            $availableLabels[] = ['label' => $availableLabel, 'value' => $availableLabel];
        }

        return $this->render(
            'user/update.twig',
            [
                'error' => implode("\n", $errors),
                'user' => $user,
                'available_roles' => $availableLabels,
            ]
        );
    }

    #[Route('/user', name: 'user.login')]
    public function loginAction(EntityManagerInterface $entityManager, Request $request)
    {
        $authException = $app['User.last_auth_exception']($request);

        if ($authException instanceof DisabledException) {
            // This exception is thrown if (!$User->isEnabled())
            // Warning: Be careful not to disclose any User information besides the email address at this point.
            // The Security system throws this exception before actually checking if the password was valid.
            $User = $app['User.manager']->refreshUser($authException->getUser());

            return $this->render('user/login-confirmation-needed.twig', [
                'email' => $User->getEmail(),
                'fromAddress' => $app['User.mailer']->getFromAddress(),
                'resendUrl' => $app['url_generator']->generate('User.resend-confirmation'),
            ]);
        }

        return $this->render('user/login.twig', [
            'error' => $authException ? $authException->getMessageKey() : null,
            'last_Username' => $app['session']->get('_security.last_Username'),
            'allowRememberMe' => isset($app['security.remember_me.response_listener']),
        ]);
    }

    /**
     * Liste des utilisateurs.
     * Todo voir pour lien vers Personnages, Groupes et Participation (ou sur le détail)
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
                    )/*->orWhere(
                        Criteria::expr()?->contains('ec'.'.nom', $value)
                    )->orWhere(
                        Criteria::expr()?->contains('ec'.'.prenom', $value)
                    )*/
                    ;
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
    public function registerAction(EntityManagerInterface $entityManager, Request $request)
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
                    return $this->render('user/register-confirmation-sent.twig', [
                        'email' => $User->getEmail(),
                    ]);
                } else {
                    // Log the User in to the new account.
                    $app['User.manager']->loginAsUser($User);

                    $this->addFlash('success', 'Votre compte a été créé ! vous pouvez maintenant rejoindre un groupe et créer votre personnage');

                    return $this->redirectToRoute('homepage');
                }
            } catch (\InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('user/register.twig', [
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
    public function confirmEmailAction(EntityManagerInterface $entityManager, Request $request, $token)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\User::class);
        $User = $repo->findOneByConfirmationToken($token);

        if (!$User) {
            $this->addFlash('alert', 'Désolé, votre lien de confirmation a expiré.');

            return $this->redirectToRoute('User.login');
        }

        $User->setConfirmationToken(null);
        $User->setEnabled(true);
        $entityManager->persist($User);
        $entityManager->flush();

        $app['User.manager']->loginAsUser($User);
        $this->addFlash('alert', 'Merci ! Votre compte a été activé.');

        return $this->redirectToRoute('newUser.step1', ['id' => $User->getId()]);
    }

    /**
     * Renvoyer un email de confirmation.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function resendConfirmationAction(EntityManagerInterface $entityManager, Request $request)
    {
        $email = $request->request->get('email');

        $repo = $entityManager->getRepository('\\'.\App\Entity\User::class);
        $User = $repo->findOneByEmail($email);

        if (!$User) {
            throw new NotFoundHttpException('Aucun compte n\'a été trouvé avec cette adresse email.');
        }

        if (!$User->getConfirmationToken()) {
            $User->setConfirmationToken($app['User.tokenGenerator']->generateToken());
            $entityManager->persist($User);
            $entityManager->flush();
        }

        $app['User.mailer']->sendConfirmationMessage($User);

        return $this->render('user/register-confirmation-sent.twig', [
            'email' => $User->getEmail(),
        ]);
    }

    #[Route('/user', name: 'user.forgot-password')]
    public function forgotPasswordAction(EntityManagerInterface $entityManager, Request $request)
    {
        if (!$this->isPasswordResetEnabled) {
            throw new NotFoundHttpException('Password resetting is not enabled.');
        }

        $error = null;
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');

            $repo = $entityManager->getRepository('\\'.\App\Entity\User::class);
            $User = $repo->findOneByEmail($email);

            if ($User) {
                // Initialize and send the password reset request.
                $User->setTimePasswordResetRequested(time());
                if (!$User->getConfirmationToken()) {
                    $User->setConfirmationToken($app['User.tokenGenerator']->generateToken());
                }

                $entityManager->persist($User);
                $entityManager->flush();

                $app['User.mailer']->sendResetMessage($User);
                $this->addFlash('alert', 'Les instructions pour enregistrer votre mot de passe ont été envoyé par mail.');
                $app['session']->set('_security.last_Username', $email);

                return $this->redirectToRoute('User.login');
            }

            $error = 'No User account was found with that email address.';
        } else {
            $email = $request->request->get('email') ?: ($request->query->get('email') ?: $app['session']->get('_security.last_Username'));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = '';
            }
        }

        return $this->render('user/forgot-password.twig', [
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
    public function resetPasswordAction(EntityManagerInterface $entityManager, Request $request, $token)
    {
        if (!$this->isPasswordResetEnabled) {
            throw new NotFoundHttpException('Password resetting is not enabled.');
        }

        $tokenExpired = false;

        $repo = $entityManager->getRepository('\\'.\App\Entity\User::class);
        $User = $repo->findOneByConfirmationToken($token);

        if (!$User) {
            $tokenExpired = true;
        } elseif ($User->isPasswordResetRequestExpired($app['config']['User']['passwordReset']['tokenTTL'])) {
            $tokenExpired = true;
        }

        if ($tokenExpired) {
            $this->addFlash('alert', 'Sorry, your password reset link has expired.');

            return $this->redirectToRoute('User.login');
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
                $entityManager->persist($User);
                $entityManager->flush();
                $app['User.manager']->loginAsUser($User);
                $this->addFlash('alert', 'Your password has been reset and you are now signed in.');

                return $this->redirectToRoute('User.view', ['id' => $User->getId()]);
            }
        }

        return $this->render('user/reset-password.twig', [
            'User' => $User,
            'token' => $token,
            'error' => $error,
        ]);
    }

    /**
     * Met a jours les droits des utilisateurs.
     */
    public function rightAction(EntityManagerInterface $entityManager, Request $request)
    {
        $Users = $app['User.manager']->findAll();

        if ('POST' == $request->getMethod()) {
            $newRoles = $request->get('User');

            foreach ($Users as $User) {
                $User->setRoles(array_keys($newRoles[$User->getId()]));
                $entityManager->persist($User);
            }

            $entityManager->flush();
        }

        // trouve tous les rôles
        return $this->render('user/right.twig', [
                'Users' => $Users,
                'roles' => $app['larp.manager']->getAvailableRoles()]
        );
    }
}
