<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\Role;
use App\Form\GroupeGn\GroupeGnForm;
use App\Form\GroupeGn\GroupeGnOrdreForm;
use App\Form\GroupeGn\GroupeGnPlaceAvailableForm;
use App\Form\GroupeGn\GroupeGnResponsableForm;
use App\Repository\GroupeGnRepository;
use App\Repository\ParticipantRepository;
use App\Security\MultiRolesExpression;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class GroupeGnController extends AbstractController
{
    /**
     * Ajout d'un groupe à un jeu.
     */
    #[Route('/groupeGn/{groupe}/add/', name: 'groupeGn.add')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA), message: 'You are not allowed to access to this.')]
    // TODO maybe not work : test migration to V2
    public function addAction(
        Request $request,
        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response {
        $groupeGn = new GroupeGn();
        $groupeGn->setGroupe($groupe);

        // Si ce groupe a déjà une participation à un jeu, reprendre le code/jeuStrategique/jeuMaritime/placeAvailable
        if ($groupe->getGroupeGns()->count() > 0) {
            $jeu = $groupe->getGroupeGns()->last();
            $groupeGn->setCode($jeu->getCode());
            $groupeGn->setPlaceAvailable($jeu->getPlaceAvailable());
            $groupeGn->setJeuStrategique($jeu->getJeuStrategique());
            $groupeGn->setJeuMaritime($jeu->getJeuMaritime());
        }

        $form = $this->createForm(GroupeGnForm::class, $groupeGn)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer', 'attr' => ['class' => 'btn btn-secondary']]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeGn = $form->getData();
            $this->entityManager->persist($groupeGn);
            $this->entityManager->flush();

            $this->addFlash('success', 'La participation au jeu a été enregistré.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupeGn/add.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'un groupe.
     */
    // #[Route('/groupeGn/{groupeGn}/detail', name: 'groupeGn.detail')]
    // #[Route('/groupeGn/{groupeGn}', name: 'groupeGn.groupe')]
    #[Deprecated]
    public function groupeAction(
        Request $request,
        #[MapEntity] GroupeGn $groupeGn,
    ): Response {
        $participant = $this->getUser()?->getParticipant($groupeGn->getGn());

        if (!$participant) {
            $this->redirectToRoute('app_login');
        }

        $canManage = $this->canManageGroup($groupeGn);
        $canSeePrivateDetail = $canManage || $this->canSeeGroup($groupeGn);

        return $this->render('groupe/detail.twig', [
            'groupe' => $groupeGn->getGroupe(),
            'tab' => $request->get('tab', 'detail'),
            'participant' => $participant,
            'groupeGn' => $groupeGn,
            'canSeePrivateDetail' => $canSeePrivateDetail,
            'canManage' => $canManage,
            'isAdmin' => $this->isGranted(Role::SCENARISTE, Role::ORGA),
            'gn' => $groupeGn->getGn(),
        ]);
    }

    protected function canManageGroup(?GroupeGn $groupeGn = null, bool $throw = false): bool
    {
        // Admin
        if ($this->isGranted(Role::SCENARISTE->value) || $this->isGranted(Role::ORGA->value)) {
            return true;
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $throw ? throw new AccessDeniedException() : false;
        }

        $groupe = $groupeGn?->getGroupe();

        // Est le responsable ?
        if ($groupe && $groupe->getUserRelatedByResponsableId()?->getId() === $user->getId()) {
            return true;
        }

        return $throw ? throw new AccessDeniedException() : false;
    }

    protected function canSeeGroup(?GroupeGn $groupeGn = null, bool $throw = false): bool
    {
        if ($this->canManageGroup($groupeGn, false)) {
            return true;
        }
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $throw ? throw new AccessDeniedException() : false;
        }

        if (!$groupeGn) {
            return $throw ? throw new AccessDeniedException() : false;
        }

        /** @var GroupeGnRepository $groupeGnRepository */
        $groupeGnRepository = $this->entityManager->getRepository(GroupeGn::class);

        if ($groupeGnRepository->userIsMemberOfGroupe($user, $groupeGn)) {
            return true;
        }

        return $throw ? throw new AccessDeniedException() : false;
    }

    /**
     * Modification du jeu de domaine du groupe.
     */
    #[Route('/groupeGn/{groupeGn}/wargame', name: 'groupeGn.wargame')]
    public function jeudedomaineAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Groupe $groupe,
        GroupeGn $groupeGn,
    ): RedirectResponse|Response {
        $form = $this->createForm(GroupeGnOrdreForm::class, $groupeGn, ['groupeGnId' => $groupeGn->getId()])
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeGn = $form->getData();
            $entityManager->persist($groupeGn);
            $entityManager->flush();

            $this->addFlash('success', 'Le jeu de domaine a été enregistré.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupeGn/jeudedomaine.twig', [
            'groupe' => $groupe,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un participant à un groupe (pour les chefs de groupe).
     */
    #[Route('/groupeGn/{groupeGn}/joueur/add/', name: 'groupeGn.joueur.add')]
    public function joueurAddAction(
        Request $request,
        EntityManagerInterface $entityManager,
        GroupeGn $groupeGn,
    ): RedirectResponse|Response {
        $participant = $this->getUser()->getParticipant($groupeGn->getGn());

        $form = $this->createFormBuilder()
            ->add('participant', EntityType::class, [
                'label' => 'Choisissez le nouveau membre de votre groupe',
                'required' => false,
                'class' => Participant::class,
                'choice_label' => 'user.Username',
                'query_builder' => static function (ParticipantRepository $er) use ($groupeGn) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->join('p.user', 'u');
                    $qb->join('p.gn', 'gn');
                    $qb->join('u.etatCivil', 'ec');
                    $qb->where($qb->expr()->isNull('p.groupeGn'));
                    $qb->andWhere('gn.id = :gnId');
                    $qb->setParameter('gnId', $groupeGn->getGn()->getId());
                    $qb->orderBy('u.Username', 'ASC');

                    return $qb;
                },
                'attr' => [
                    // //'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Participant',
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Ajouter le joueur choisi'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($data['participant']) {
                $data['participant']->setGroupeGn($groupeGn);
                $entityManager->persist($data['participant']);
                $entityManager->flush();

                // NOTIFY $app['notify']->newMembre($data['participant']->getUser(), $groupeGn);

                $this->addFlash('success', 'Le joueur a été ajouté à votre groupe.');
            }

            return $this->redirectToRoute(
                'groupe.detail',
                ['groupeGn' => $groupeGn->getId(), 'groupe' => $groupeGn->getGroupe()->getId()],
            );
        }

        return $this->render('groupeGn/add.twig', [
            'groupeGn' => $groupeGn,
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des sessions de jeu pour un groupe.
     */
    #[Route('/groupeGn/{groupe}/list/', name: 'groupeGn.list')]
    public function listAction(Groupe $groupe): Response
    {
        return $this->render('groupeGn/list.twig', [
            'groupe' => $groupe,
        ]);
    }

    /**
     * Ajoute un participant à un groupe.
     */
    #[Route('/groupeGn/{groupeGn}/participants/add/', name: 'groupeGn.participants.add')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA), message: 'You are not allowed to access to this.')]
    public function participantAddAction(
        Request $request,
        GroupeGn $groupeGn,
    ): RedirectResponse|Response {
        // $form = $this->createForm(GroupeGnForm::class, $groupeGn)
        //    ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);
        // TODO Migrate to V2
        $form = $this->createFormBuilder()
            ->add('participant', EntityType::class, [
                'label' => 'Nouveau participant',
                'required' => true,
                'class' => Participant::class,
                'choice_label' => 'user.etatCivil',
                'query_builder' => static function (ParticipantRepository $er) use ($groupeGn) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->join('p.user', 'u');
                    $qb->join('p.gn', 'gn');
                    $qb->join('u.etatCivil', 'ec');
                    $qb->where($qb->expr()->isNull('p.groupeGn'));
                    $qb->andWhere('gn.id = :gnId');
                    $qb->setParameter('gnId', $groupeGn->getGn()->getId());
                    $qb->orderBy('ec.nom', 'ASC');

                    return $qb;
                },
                'attr' => [
                    // //'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Participant',
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $data['participant']->setGroupeGn($groupeGn);
            $this->entityManager->persist($data['participant']);
            $this->entityManager->flush();

            // NOTIFY $app['notify']->newMembre($data['participant']->getUser(), $groupeGn);

            $this->addFlash('success', 'Le joueur a été ajouté à cette session.');

            return $this->redirectToRoute('groupeGn.list', ['groupe' => $groupeGn->getGroupe()->getId()]);
        }

        return $this->render('groupeGn/participantAdd.twig', [
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retire un participant d'un groupe.
     */
    #[Route('/groupeGn/{groupeGn}/participant/remove/{participant}', name: 'groupeGn.participants.remove')]
    public function participantRemoveAction(
        Request $request,
        EntityManagerInterface $entityManager,
        GroupeGn $groupeGn,
        Participant $participant,
    ): RedirectResponse|Response {
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Retirer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // si le participant est le chef de groupe
            if ($groupeGn->getResponsable() == $participant) {
                $groupeGn->setResponsableNull();
            }

            $participant->setGroupeGnNull();
            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Le joueur a été retiré de cette session.');

            return $this->redirectToRoute('groupeGn.list', ['groupe' => $groupeGn->getGroupe()->getId()]);
        }

        return $this->render('groupeGn/participantRemove.twig', [
            'groupeGn' => $groupeGn,
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Permet au chef de groupe de modifier le nombre de place disponible.
     */
    #[Route('/groupeGn/{groupeGn}/placeAvailable', name: 'groupeGn.placeAvailable')]
    public function placeAvailableAction(
        Request $request,
        EntityManagerInterface $entityManager,
        GroupeGn $groupeGn,
    ): RedirectResponse|Response {
        $participant = $this->getUser()->getParticipant($groupeGn->getGn());

        $form = $this->createForm(GroupeGnPlaceAvailableForm::class, $groupeGn)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeGn = $form->getData();
            $entityManager->persist($groupeGn);
            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistré.');

            return $this->redirectToRoute(
                'groupe.groupe.detail',
                ['groupeGn' => $groupeGn->getId(), 'groupe' => $groupeGn->getGroupe()->getId()],
            );
        }

        return $this->render('groupeGn/placeAvailable.twig', [
            'form' => $form->createView(),
            'groupe' => $groupeGn->getGroupe(),
            'participant' => $participant,
            'groupeGn' => $groupeGn,
        ]);
    }

    /**
     * Choisir le responsable.
     */
    #[Route('/groupeGn/{groupe}/responsable/{groupeGn}/', name: 'groupeGn.responsable')]
    public function responsableAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Groupe $groupe,
        GroupeGn $groupeGn,
    ): RedirectResponse|Response {
        $form = $this->createForm(GroupeGnResponsableForm::class, $groupeGn)
            ->add('responsable', EntityType::class, [
                'label' => 'Responsable',
                'required' => false,
                'class' => Participant::class,
                'choice_label' => 'user.etatCivil',
                'query_builder' => static function (ParticipantRepository $er) use ($groupeGn) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->join('p.user', 'u');
                    $qb->join('p.groupeGn', 'gg');
                    $qb->join('u.etatCivil', 'ec');
                    $qb->orderBy('ec.nom', 'ASC');
                    $qb->where('gg.id = :groupeGnId');
                    $qb->setParameter('groupeGnId', $groupeGn->getId());

                    return $qb;
                },
                'attr' => [
                    // 'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Responsable',
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeGn = $form->getData();
            $entityManager->persist($groupeGn);
            $entityManager->flush();

            // NOTIFY $app['notify']->newResponsable($groupeGn->getResponsable()->getUser(), $groupeGn);

            $this->addFlash('success', 'Le responsable du groupe a été enregistré.');

            return $this->redirectToRoute('groupeGn.list', ['groupe' => $groupeGn->getGroupe()->getId()]);
        }

        return $this->render('groupeGn/responsable.twig', [
            'groupe' => $groupe,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modification de la participation à un jeu du groupe.
     */
    #[Route('/groupeGn/{groupeGn}/update', name: 'groupeGn.update')]
    public function updateAction(
        Request $request,
        #[MapEntity] GroupeGn $groupeGn,
    ): RedirectResponse|Response {
        $this->checkGroupeLocked($groupeGn->getGroupe());
        if ($r = $this->checkGroupeLocked($groupeGn->getGroupe())) {
            return $r;
        }

        $redirect = $request->get('redirect');

        $this->hasAccess($groupeGn, [Role::WARGAME]);

        $form = $this->createForm(
            GroupeGnForm::class,
            $groupeGn,
            ['allow_extra_fields' => true],
        )
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer', 'attr' => ['class' => 'btn btn-secondary']]);

        if ($redirect) {
            $form->add('redirect', HiddenType::class, ['data' => $redirect, 'mapped' => false]);
        }

        $form->handleRequest($request);

        // ONLY ONE per personnage
        if ($form->isSubmitted()) {
            $ids = [];

            $idsArray = [
                'suzerin' => $groupeGn->getSuzerain()?->getId(),
                'intendant' => $groupeGn->getIntendant()?->getId(),
                'camarilla' => $groupeGn->getCamarilla()?->getId(),
                'connetable' => $groupeGn->getConnetable()?->getId(),
                'navigateur' => $groupeGn->getNavigateur()?->getId(),
                'diplomate' => $groupeGn->getDiplomate()?->getId(),
            ];

            foreach ($idsArray as $child => $id) {
                if ($id) {
                    if (isset($ids[$id])) {
                        ++$ids[$id];
                        $form->get($child)->addError(new FormError("Un personnage ne peut avoir qu'un seul titre"));
                    } else {
                        $ids[$id] = 1;
                    }
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var GroupeGn $groupeGn */
            $groupeGn = $form->getData();

            // Titre si territoire
            if (null === $groupeGn->getGroupe()->getTerritoire()) {
                if ($groupeGn->getSuzerain() || $groupeGn->getIntendant() || $groupeGn->getCamarilla(
                ) || $groupeGn->getConnetable() || $groupeGn->getNavigateur() || $groupeGn->getDiplomate()) {
                    $this->addFlash('error', 'Les titres ne sont possibles que si le groupe à un territoire');
                }
                $groupeGn->setSuzerain(null);
                $groupeGn->setIntendant(null);
                $groupeGn->setCamarilla(null);
                $groupeGn->setConnetable(null);
                $groupeGn->setNavigateur(null);
                $groupeGn->setDiplomate(null);
            }

            $this->entityManager->persist($groupeGn);
            $this->entityManager->flush();
            $redirect = $form->getExtraData()['redirect'] ?? null;

            $this->addFlash('success', 'La participation au jeu a été enregistré.');

            if ($redirect) {
                return $this->redirect($redirect);
            }

            return $this->redirectToRoute(
                'groupe.detail',
                [
                    'groupe' => $groupeGn->getGroupe()->getId(),
                    'gn' => $groupeGn->getGn()->getId(),
                    'groupeGn' => $groupeGn->getId(),
                    'tab' => 'domaine',
                ],
            );
        }

        return $this->render('groupeGn/update.twig', [
            'groupe' => $groupeGn->getGroupe(),
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ], new Response(null, !$form->isSubmitted() || $form->isValid() ? 200 : 422));
    }

    protected function hasAccess(GroupeGn $groupeGn, array $roles = []): void
    {
        $isResponsable = $this->getUser()?->getId() === $groupeGn->getParticipant()?->getUser()?->getId();

        $isMembre = $isResponsable;
        if (!$isMembre) {
            /** @var Participant $participant */
            foreach ($groupeGn->getParticipants() as $participant) {
                if ($participant->getUser()?->getId() === $this->getUser()?->getId()) {
                    $isMembre = true;
                }
            }
        }

        $isSuzerain = false;
        if ($this->getPersonnage() && $this->getPersonnage()->getId() === $groupeGn?->getSuzerain()?->getId()) {
            $isSuzerain = true;
        }

        // TODO check if membre can read secret

        $this->setCan(self::IS_ADMIN, $this->isGranted(Role::WARGAME->value));
        $this->setCan(self::CAN_MANAGE, $isResponsable);
        $this->setCan(self::CAN_READ_PRIVATE, $isResponsable || $isMembre);
        $this->setCan(self::CAN_READ_SECRET, $isResponsable);
        $this->setCan(self::CAN_WRITE, $isResponsable || $isSuzerain);
        $this->setCan(self::CAN_READ, $isMembre || $isSuzerain);

        $this->checkHasAccess(
            $roles,
            fn () => $this->can(self::CAN_READ),
        );
    }
}
