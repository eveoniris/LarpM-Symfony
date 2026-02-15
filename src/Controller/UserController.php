<?php

namespace App\Controller;

use App\Entity\Annonce;
use App\Entity\EtatCivil;
use App\Entity\Gn;
use App\Entity\LogAction;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\Restriction;
use App\Entity\User;
use App\Enum\LogActionType;
use App\Enum\Role;
use App\Form\Entity\ListSearch;
use App\Form\EtatCivilForm;
use App\Form\Personnage\PersonnageForm;
use App\Form\User\UserForgotPasswordForm;
use App\Form\User\UserNewForm;
use App\Form\User\UserNewPasswordForm;
use App\Form\User\UserPersonnageDefaultForm;
use App\Form\User\UserPersonnageSecondaireForm;
use App\Form\UserFindForm;
use App\Form\UserRegisterForm;
use App\Form\UserRestrictionForm;
use App\Manager\FedegnManager;
use App\Repository\PersonnageRepository;
use App\Repository\UserRepository;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Carbon\Carbon;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;

class UserController extends AbstractController
{
    private bool $isEmailConfirmationRequired = true;

    private bool $isPasswordResetEnabled = true;

    /**
     * TODO
     * Affiche le détail d'un billet d'un utilisateur.
     */
    public function UserHasBilletDetailAction(
        EntityManagerInterface $entityManager,
        Request $request,
        UserHasBillet $UserHasBillet,
    ): RedirectResponse|Response {
        if ($UserHasBillet->getUser() != $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas acceder à cette information');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('UserHasBillet/detail.twig', [
            'UserHasBillet' => $UserHasBillet,
        ]);
    }

    /**
     * Liste des utilisateurs.
     * Todo voir pour lien vers Personnages, Groupes et Participation (ou sur le détail).
     */
    #[Route('/user/list', name: 'user.list')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    public function adminListAction(
        Request $request,
        PagerService $pagerService,
        UserRepository $userRepository,
    ): Response {
        $pagerService->setRequest($request)->setRepository($userRepository);

        return $this->render('user/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $userRepository->searchPaginated($pagerService),
        ]);
    }

    public function adminListActionOld(Request $request, UserRepository $userRepository): Response
    {
        $type = null;
        $value = null;

        $userSearch = new ListSearch();
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
                        Criteria::expr()?->contains($alias.'.id', $value),
                    );
                } else {
                    $criterias[] = Criteria::create()->where(
                        Criteria::expr()?->contains($alias.'.username', $value),
                    )->orWhere(
                        Criteria::expr()?->contains($alias.'.email', $value),
                    )->orWhere(
                        Criteria::expr()?->contains($alias.'.roles', $value),
                    )/*->orWhere(
                        Criteria::expr()?->contains('ec'.'.nom', $value)
                    )->orWhere(
                        Criteria::expr()?->contains('ec'.'.prenom', $value)
                    )*/
                    ;
                }
            } else {
                $criterias[] = Criteria::create()->andWhere(
                    Criteria::expr()?->contains($alias.'.'.$type, $value),
                );
            }
        }

        $orderBy = $this->getRequestOrder(
            defOrderBy: 'username',
            alias: $alias,
            allowedFields: $userRepository->getFieldNames(),
        );

        $paginator = $userRepository->getPaginator(
            limit: $this->getRequestLimit(),
            page: $this->getRequestPage(),
            orderBy: $orderBy,
            alias: $alias,
            criterias: $criterias,
        );

        return $this->render(
            'user/list.twig',
            [
                'paginator' => $paginator,
                'form' => $form->createView(),
            ],
        );
    }

    /**
     * Création d'un nouvel utilisateur.
     */
    #[Route('/user/new', name: 'user.new')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    public function adminNewAction(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
    ): RedirectResponse|Response {
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

            $this->entityManager->persist($user);

            if ($data['gn']) {
                $participant = new Participant();
                $participant->setUser($user);
                $participant->setGn($data['gn']);

                if ($data['billet']) {
                    $participant->setBillet($data['billet']);
                }

                $this->entityManager->persist($participant);
            }

            $this->entityManager->flush();

            // TODO // NOTIFY $app['notify']->newUser($user, $plainPassword);

            $this->addFlash('success', 'L\'utilisateur a été ajouté.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render(
            'user/new.twig',
            [
                'form' => $form->createView(),
            ],
        );
    }

    /**
     * Genere un mot de passe aléatoire.
     */
    protected function generatePassword(int $length = 10): string
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

    #[Route('/user/{user}/confirm/{token}', name: 'user.confirm-email')]
    public function confirmEmailAction(
        EntityManagerInterface $entityManager,
        $token,
        #[MapEntity] User $user,
        Security $security,
    ): RedirectResponse {
        if ($user->getConfirmationToken() !== $token) {
            $this->addFlash('alert', 'Désolé, votre lien de confirmation a expiré.');

            return $this->redirectToRoute('app_login');
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);
        $entityManager->persist($user);
        $entityManager->flush();

        $security->login(
            $user,
            'form_login',
            'main',
            [(new RememberMeBadge())->enable()],
        );

        $this->addFlash('alert', 'Merci ! Votre compte a été activé.');

        return $this->redirectToRoute('user.new-step1', ['user' => $user->getId()]);
    }

    /**
     * Edit User action.
     *
     * @throws NotFoundHttpException if no User is found with that ID
     */
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access to this.')]
    #[Route('/user/{user}/edit', name: 'user.edit')]
    public function editAction(
        Request $request,
        #[MapEntity] ?User $user,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $repository,
    ): Response {
        $errors = [];

        if (!$user) {
            throw new NotFoundHttpException('No User was found with that ID.');
        }

        $this->hasAccess($user, [Role::ORGA, Role::ADMIN]);

        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'));
            $user->setEmailContact($request->request->get('email_contact'));
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

                $log = new LogAction();
                $log->setDate(new \DateTime());
                $log->setType(LogActionType::ENTITY_UPDATE);
                $log->setUser($this->getUser());
                $log->setData([
                    'user_id' => $user->getId(),
                    'roles' => $roles,
                ]);
                $this->entityManager->persist($log);
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

        $availableLabels = Role::getLabels();
        if (!$this->isGranted(Role::SUPER_ADMIN->value)) {
            unset($availableLabels[Role::SUPER_ADMIN->value]);
        }

        return $this->render(
            'user/update.twig',
            [
                'error' => implode("\n", $errors),
                'user' => $user,
                'available_roles' => $availableLabels,
            ],
        );
    }

    protected function hasAccess(User $user, array $roles = []): void
    {
        /** @var User $loggedUser */
        $loggedUser = $this->getUser();

        $this->checkHasAccess($roles, static fn () => $user->getId() === $loggedUser->getId());
    }

    /**
     * Enregistrement de l'état-civil.
     */
    #[Route('/user/etatCivil', name: 'user.etatCivil')]
    public function etatCivilAction(Request $request): RedirectResponse|Response
    {
        $etatCivil = $this->getUser()?->getEtatCivil();

        if (!$etatCivil) {
            $etatCivil = new EtatCivil();
        }

        $form = $this->createForm(EtatCivilForm::class, $etatCivil)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $etatCivil = $form->getData();
            $this->getUser()?->setEtatCivil($etatCivil);

            $this->entityManager->persist($this->getUser());
            $this->entityManager->persist($etatCivil);
            $this->entityManager->flush();

            $this->addFlash('success', 'Vos informations ont été enregistrées.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('user/etatCivil.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche les informations de la fédéGN.
     */
    #[Route('/user/fedegn', name: 'user.fedegn')]
    public function fedegnAction(
        FedegnManager $fedegnManager,
    ): Response {
        $etatCivil = $this->getUser()?->getEtatCivil();

        // $statutEtatCivil = $fedegnManager->test($etatCivil);
        return $this->render('user/fedegn.twig', [
            'etatCivil' => $etatCivil,
            'fedegnManager' => $fedegnManager,
        ]);
    }

    #[Route('/user/forgot', name: 'user.forgot-password')]
    public function forgotPasswordAction(
        EntityManagerInterface $entityManager,
        Request $request,
        MailerInterface $mailer,
        ContainerBagInterface $params,
    ): RedirectResponse|Response {
        if (false !== $this->isGranted('ROLE_USER') && null !== $this->getUser()?->getId()) {
            $this->addFlash(
                'alert',
                'Vous êtes déjà connecté',
            );

            return $this->redirectToRoute('user.view', ['user' => $this->getUser()?->getId()]);
        }

        if (!$this->isPasswordResetEnabled) {
            throw new NotFoundHttpException('Password resetting is not enabled.');
        }

        $form = $this->createForm(UserForgotPasswordForm::class, [])
            ->add(
                'save',
                SubmitType::class,
                [
                    'label' => 'Envoyer une réinitialisation de votre mot de passe',
                    'attr' => [
                        'class' => 'btn btn-secondary',
                    ],
                ],
            );

        $form->handleRequest($request);

        $email = $request->request->get(
            'email',
            $request->query->get(
                'email',
                $request->getSession()->get('_security.last_username'),
            ),
        );
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = '';
        }

        $error = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->getData();

            $repo = $entityManager->getRepository(User::class);
            $user = $repo->findOneBy(['email' => $email]);

            if ($user) {
                // Initialize and send the password reset request.
                $user->setTimePasswordResetRequested(Carbon::now()->getTimestamp());
                if (!$user->getConfirmationToken()) {
                    $user->setConfirmationToken($user->generateToken());
                }

                $entityManager->persist($user);
                $entityManager->flush();

                $url = $this->generateUrl(
                    'user.reset-password',
                    ['token' => $user->getConfirmationToken()],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                );

                $resetExpireAt = Carbon::createFromTimestamp($user->getTimePasswordResetRequested(), 'Europe/Paris')
                    ->addSeconds($params->get('passwordTokenTTL'))
                    ->format('Y-m-d H:i:s');
                $context = [
                    'resetUrl' => $url,
                    'resetExpireAt' => $resetExpireAt,
                ];
                $subject = $this->renderBlock(
                    'user/email/forgotPassword.twig',
                    'subject',
                    $context,
                ) ?: 'Mot de passe oublié';
                $textBody = $this->renderBlock('user/email/forgotPassword.twig', 'body_text', $context);
                $context['subject'] = $subject;

                $email = (new TemplatedEmail())
                    ->to($user->getEmail())
                    ->subject($subject->getContent())
                    // TODo ->locale($user->getLocal())
                    ->text($textBody->getContent())
                    ->htmlTemplate('user/email/forgotPassword.twig')
                    ->context($context);
                $mailer->send($email);

                $this->addFlash(
                    'alert',
                    'Les instructions pour enregistrer votre mot de passe ont été envoyé par mail.',
                );
                $request->getSession()->get('_security.last_username', $email);

                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('error', 'No user account was found with that email address.');
        }

        return $this->render('user/forgot-password.twig', [
            'email' => $email,
            'fromAddress' => $params->get('fromEmailAddress'),
            'error' => $error,
            'form' => $form,
        ]);
    }

    /**
     * Formulaire de participation à un jeu.
     */
    #[Route('/user/{gn}/participe', name: 'user.gn.participe')]
    public function gnParticipeAction(Request $request, Gn $gn): RedirectResponse|Response
    {
        $form = $this->createFormBuilder($gn)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && (!$gn->getBesoinValidationCi() || 'ok' === $request->request->get('acceptCi'))) {
            $participant = new Participant();
            $participant->setUser($this->getUser());
            $participant->setGn($gn);

            if ($gn->getBesoinValidationCi()) {
                $participant->setValideCiLe(new \DateTime('NOW'));
            }

            $this->entityManager->persist($participant);
            $this->entityManager->flush();

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
    public function gnValidCiAction(Request $request, Gn $gn): RedirectResponse|Response
    {
        $form = $this->createFormBuilder()->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && 'ok' === $request->request->get('acceptCi')) {
            $participant = $this->getUser()?->getParticipant($gn);
            $participant->setValideCiLe(new \DateTime('NOW'));

            $this->entityManager->persist($participant);
            $this->entityManager->flush();

            $this->addFlash('success', 'Vous avez validé les condition d\'inscription pour '.$gn->getLabel().' !');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('gn/validation_ci.twig', [
            'gn' => $gn,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/like', name: 'user.like')]
    public function likeAction(User $user): RedirectResponse
    {
        if ($user === $this->getUser()) {
            $this->addFlash(
                'error',
                'Désolé ... Avez vous vraiment cru que cela allait fonctionner ? un peu de patience !',
            );
        } else {
            $user->addCoeur();
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            // NOTIFY $app['notify']->coeur($this->getUser(), $User);
            $this->addFlash('success', 'Votre coeur a été envoyé !');
        }

        return $this->redirectToRoute('User.view', ['id' => $User->getId()]);
    }

    public function loginAction(EntityManagerInterface $entityManager, Request $request): Response
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

    #[Route('/user/new/step1', name: 'user.new-step1')]
    public function newUserStep1Action(EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()?->getEtatCivil()) {
            $repoAnnonce = $entityManager->getRepository(Annonce::class);
            $annonces = $repoAnnonce->findBy(['archive' => false, 'gn' => null], ['update_date' => 'DESC']);

            return $this->render('homepage/index.twig', [
                'annonces' => $annonces,
                'user' => $this->getUser(),
            ]);
        }

        // Todo : in homepage/new-user/step1 or in user/new/step
        return $this->render('newUser/step1.twig', []);
    }

    /**
     * Seconde étape pour un nouvel utilisateur : enregistrer les informations administratives.
     */
    #[Route('/user/new/step2', name: 'user.new-step2')]
    #[IsGranted(Role::USER->value)]
    public function newUserStep2Action(Request $request): RedirectResponse|Response
    {
        $etatCivil = $this->getUser()?->getEtatCivil();
        if (!$etatCivil) {
            $etatCivil = new EtatCivil();
        }

        $form = $this->createForm(EtatCivilForm::class, $etatCivil)
            ->add(
                'valider',
                SubmitType::class,
                ['label' => 'Étape suivante', 'attr' => ['class' => 'btn btn-secondary']],
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $etatCivil = $form->getData();
            $this->getUser()?->setEtatCivil($etatCivil);

            $this->entityManager->persist($this->getUser());
            $this->entityManager->flush();

            return $this->redirectToRoute('user.new-step3', [], 303);
        }

        return $this->render('newUser/step2.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/new/step3', name: 'user.new-step3')]
    public function newUserStep3Action(
        Request $request,
    ): RedirectResponse|Response {
        $form = $this->createForm(UserRestrictionForm::class, $this->getUser())
            ->add(
                'valider',
                SubmitType::class,
                ['label' => 'Étape suivante', 'attr' => ['class' => 'btn btn-secondary']],
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $User = $form->getData();
            $newRestriction = $form->get('new_restriction')->getData();
            if ($newRestriction) {
                $restriction = new Restriction();
                $restriction->setUserRelatedByAuteurId($this->getUser());
                $restriction->setLabel($newRestriction);

                $this->entityManager->persist($restriction);
                $User->addRestriction($restriction);
            }

            $this->entityManager->persist($User);
            $this->entityManager->flush();

            return $this->redirectToRoute('user.new-step4', [], 303);
        }

        return $this->render('newUser/step3.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Quatrième étape pour un nouvel utilisateur : choisir un GN.
     */
    #[Route('/user/new/step4', name: 'user.new-step4')]
    public function newUserStep4Action(): Response
    {
        return $this->render('newUser/step4.twig');
    }

    /**
     * Choix du personnage par défaut de l'utilisateur.
     */
    #[Route('/user/{user}/personage/default', name: 'user.personnageDefault')]
    public function personnageDefaultAction(Request $request, #[MapEntity] User $user): RedirectResponse|Response
    {
        $this->hasAccess($user, [Role::ORGA, Role::ADMIN]);
        $form = $this->createForm(UserPersonnageDefaultForm::class, $user, ['user_id' => $user->getId(), 'secondaire_id' => (int) $user->getPersonnageSecondaire()?->getId()])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été enregistrées.');

            // return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('user/personnageDefault.twig', [
            'form' => $form->createView(),
            'User' => $user,
        ]);
    }

    #[Route('/user/{user}/personage/secondaire', name: 'user.personnageSecondaire')]
    public function personnageSecondaireAction(Request $request, #[MapEntity] User $user): RedirectResponse|Response
    {
        $this->hasAccess($user, [Role::ORGA, Role::ADMIN]);

        if ($user->getPersonnageSecondaire() && !$this->can(self::IS_ADMIN)) {
            $this->addFlash('error', 'Vous avez déjà un personnage secondaire');

            return $this->redirectToRoute('user.detail', ['user' => $user->getId()], 303);
        }

        $principalIds = null;
        foreach ($user->getParticipants() as $participant) {
            $principalIds[] = $participant->getId();
        }

        $form = $this->createForm(UserPersonnageSecondaireForm::class, $user, ['user_id' => $user->getId(), 'principal_ids' => $principalIds])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été enregistrées.');
        }

        return $this->render('user/personnageSecondary.twig', [
            'form' => $form->createView(),
            'User' => $user,
        ]);
    }

    #[Route('/user/{user}/personage/list', name: 'user.personnage.list')]
    public function personnagesListAction(
        #[MapEntity] User $user,
        Request $request,
        PagerService $pagerService,
        PersonnageRepository $personnageRepository,
    ): Response {
        $this->hasAccess($user, [Role::ORGA, Role::ADMIN]);
        $this->setCan(static::CAN_WRITE, true);

        $pagerService->setRequest($request)->setLimit(25)->setRepository($personnageRepository);

        $queryBuilder = $personnageRepository->user(
            $personnageRepository->createQueryBuilder($personnageRepository->getAlias()),
            $user
        );

        return $this->render('user/personnages.twig', [
            'pagerService' => $pagerService,
            'paginator' => $personnageRepository->searchPaginated($pagerService, $queryBuilder),
            'user' => $user,
            'canAdd' => $this->can(static::CAN_WRITE),
        ]);
    }

    #[Route('/user/{user}/personnage/add', name: 'user.personnage.add')]
    public function personnagesAddAction(
        Request $request,
        #[MapEntity] User $user,
        PersonnageService $personnageService,
        PersonnageRepository $personnageRepository,
    ): RedirectResponse|Response {
        $this->hasAccess($user, [Role::ORGA, Role::ADMIN]);

        $form = $this->createForm(PersonnageForm::class, new Personnage());

        // Check if user can create new personnage, admin always can
        if (!$this->can(self::IS_ADMIN) && PersonnageService::MAX_PER_USER <= $personnageRepository->countUser($user)) {
            $form->addError(
                new FormError(
                    'Vous avez atteind le nombre maximum de personnage possible',
                ),
            );
            $this->addFlash('error', 'Vous avez atteind le nombre maximum de personnage possible');

            return $this->redirectToRoute('user.self', [], 303);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $personnageService->createNewPersonnage($form, $user);
            if ($personnage->getId() > 0) {
                return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
            } else {
                $this->addFlash($form->getErrors());
            }
        }

        return $this->render('personnage/add.twig', [
            'form' => $form->createView(),
        ]);
        // $this->redirectToRoute('personnage.add');
    }

    #[Route('/user/register ', name: 'user.register')]
    public function registerAction(
        EntityManagerInterface $entityManager,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
    ): RedirectResponse|Response {
        $form = $this->createForm(UserRegisterForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var User $user */
                $user = $form->getData();

                // Validate the password
                $password = $user->getPassword();
                if ($password !== $form->get('confirm_password')->getData()) {
                    $form->get('confirm_password')
                        ->addError(new FormError("Passwords don't match."));
                }

                if ($error = $user->validatePasswordStrength($password)) {
                    $form->get('pwd')
                        ->addError(new FormError($error));
                }

                if ($form->isValid()) {
                    $hashedPassword = $passwordHasher->hashPassword(
                        $user,
                        $password,
                    );
                    $user->setPassword($hashedPassword);

                    if ($this->isEmailConfirmationRequired) {
                        $user->setEnabled(false);
                        $user->setConfirmationToken($user->generateToken());
                    }

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    if ($this->isEmailConfirmationRequired) {
                        $this->mailer->sendConfirmEmail($user);

                        // Render the "go check your email" page.
                        return $this->render('user/register-confirmation-sent.twig', [
                            'email' => $user->getEmail(),
                        ]);
                    }

                    $security->login(
                        $user,
                        'form_login',
                        'main',
                        [(new RememberMeBadge())->enable()],
                    );
                    $this->addFlash(
                        'success',
                        'Votre compte a été créé ! vous pouvez maintenant rejoindre un groupe et créer votre personnage',
                    );

                    return $this->redirectToRoute('homepage');
                }
            } catch (\InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('user/register.twig', [
            'error' => $error ?? null,
            'form' => $form,
            'name' => $request->request->get('name'),
            'email' => $request->request->get('email'),
            'username' => $request->request->get('username'),
        ]);
    }

    /**
     * Renvoyer un email de confirmation.
     *
     * @throws NotFoundHttpException
     */
    public function resendConfirmationAction(EntityManagerInterface $entityManager, Request $request): Response
    {
        $email = $request->request->get('email');

        $repo = $entityManager->getRepository(User::class);
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

    #[Route('/user/reset-password/{token}', name: 'user.reset-password')]
    public function resetPasswordAction(
        UserRepository $userRepository,
        Request $request,
        string $token,
        ContainerBagInterface $params,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
    ): Response {
        if (!$this->isPasswordResetEnabled) {
            throw new NotFoundHttpException('Password resetting is not enabled.');
        }

        $form = $this->createForm(UserNewPasswordForm::class, [])
            ->add(
                'save',
                SubmitType::class,
                [
                    'label' => 'Modifier',
                    'attr' => [
                        'class' => 'btn btn-secondary',
                    ],
                ],
            );

        $form->handleRequest($request);

        $user = $userRepository->findOneByConfirmationToken($token);

        if (!$user || $user->isPasswordResetRequestExpired((int) $params->get('passwordTokenTTL'))) {
            $this->addFlash('alert', 'Sorry, your password reset link has expired.');

            // throw new GoneHttpException('This action is expired');

            return $this->redirectToRoute('app_login');
        }

        $error = '';
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Validate the password
            $password = $data['password'];
            if ($password !== $data['confirm_password']) {
                $error = "Passwords don't match.";
            }

            $error ??= $user->validatePasswordStrength($password);

            if (!$error) {
                // Set the password and log in.
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $password,
                );

                // First set : importing from VA
                $roles = $user->getRoles();
                if (empty($user->getPwd()) && $rights = explode(',', $user->getRights())) {
                    // Get role from V1
                    foreach ($rights as $right) {
                        if (empty($right)) {
                            continue;
                        }

                        if (Role::tryFrom($right)) {
                            $roles[] = $right;
                        }
                    }
                }
                $user->setRoles($roles);

                $user->setPassword($hashedPassword);
                $user->setConfirmationToken(null);
                $user->setEnabled(true);

                $this->entityManager->persist($user);
                $this->entityManager->flush();
                $security->login(
                    $user,
                    'form_login',
                    'main',
                    [(new RememberMeBadge())->enable()],
                );
                $this->addFlash('alert', 'Your password has been reset and you are now signed in.');

                return $this->redirectToRoute('user.view', ['user' => $user->getId()]);
            }
        }

        return $this->render('user/reset-password.twig', [
            'user' => $user,
            'token' => $token,
            'error' => $error,
            'form' => $form,
        ]);
    }

    /**
     * Choix des restrictions alimentaires par l'utilisateur.
     */
    #[Route('/user/restriction', name: 'user.restriction')]
    public function restrictionAction(Request $request): RedirectResponse|Response
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

                $this->entityManager->persist($restriction);
                $User->addRestriction($restriction);
            }

            $this->entityManager->persist($User);
            $this->entityManager->flush();

            $this->addFlash('success', 'Vos informations ont été enregistrées.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('user/restriction.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * TODO
     * Affiche la liste des billets de l'utilisateur.
     */
    public function userHasBilletListAction(EntityManagerInterface $entityManager, Request $request): Response
    {
        $UserHasBillets = $this->getUser()->getUserHasBillets();

        return $this->render('UserHasBillet/list.twig', [
            'UserHasBillets' => $UserHasBillets,
        ]);
    }

    /**
     * View User action.
     *
     * @throws NotFoundHttpException if no User is found with that ID
     */
    #[Route('/user/{user}', name: 'user.view', requirements: ['user' => Requirement::DIGITS])]
    #[Route('/user/{user}', name: 'user.detail', requirements: ['user' => Requirement::DIGITS])]
    public function viewAction(#[MapEntity] User $user): Response
    {
        if (!$user->isEnabled() && !$this->isGranted('ROLE_ADMIN')) {
            throw new NotFoundHttpException('That User is disabled (pending email confirmation).');
        }

        return $this->render('user/detail.twig', ['user' => $user]);
    }

    /**
     * Affiche le détail de l'utilisateur courant.
     */
    #[Route('/self', name: 'user.self')]
    public function viewSelfAction(): Response|RedirectResponse
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
            ],
        );
    }
}
