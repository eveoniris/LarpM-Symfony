<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\Personnage;
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
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;


class GroupeGnController extends AbstractController
{
    /**
     * Ajout d'un groupe à un jeu.
     */
    #[Route('/groupeGn/{groupe}/add/', name: 'groupeGn.add')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA), message: 'You are not allowed to access to this.')]
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
    #[Route('/groupeGn/{groupeGn}/detail', name: 'groupeGn.detail')]
    #[Route('/groupeGn/{groupeGn}', name: 'groupeGn.groupe')]
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
    #[Route('/groupeGn/{groupe}/jeudedomaine/{groupeGn}', name: 'groupeGn.jeudedomaine')]
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

            return $this->redirectToRoute('groupe.detail', ['index' => $groupe->getId()]);
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

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
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
    public function listAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Groupe $groupe,
    ): Response {
        return $this->render('groupeGn/list.twig', [
            'groupe' => $groupe,
        ]);
    }

    /**
     * Ajoute un participant à un groupe.
     */
    #[Route('/groupeGn/{groupeGn}/participants/add/', name: 'groupeGn.participants.add')]
    public function participantAddAction(
        Request $request,
        EntityManagerInterface $entityManager,
        GroupeGn $groupeGn,
    ): RedirectResponse|Response {
        $form = $this->createForm(GroupeGnForm::class, $groupeGn)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

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
            $entityManager->persist($data['participant']);
            $entityManager->flush();

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

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
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
    #[Route('/groupeGn/{groupe}/update/{groupeGn}/', name: 'groupeGn.update')]
    public function updateAction(
        Request $request,
        #[MapEntity] Groupe $groupe,
        #[MapEntity] GroupeGn $groupeGn,
    ): RedirectResponse|Response {
        $redirect = $request->get('redirect');


        $groupeGnRepository = $this->entityManager->getRepository(GroupeGn::class);
        $personnage = $this->entityManager->getRepository(Personnage::class);


        // Seul un admin ou le suzerin peu changer cela
        if (!$this->isGranted(Role::WARGAME->value) || !$this->getUser()?->getId() === $groupeGn->getSuzerin()?->getId(
        )) {
            $this->addFlash('success', "Vous n'êtes pas autorisé à accéder à cette page");

            if ($redirect) {
                return $this->redirect($redirect.'&tab=domaine&gn='.$groupeGn->getGn()->getId());
            }

            return $this->redirectToRoute(
                'groupeGn.list',
                ['groupe' => $groupe->getId(), 'tab' => 'domaine', 'gn' => $groupeGn->getGn()->getId()]
            );
        }

        $form = $this->createForm(
            GroupeGnForm::class,
            $groupeGn,
            ['allow_extra_fields' => true, 'gn' => $groupeGn->getGn()]
        )
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer', 'attr' => ['class' => 'btn btn-secondary']]);

        if ($redirect) {
            $form->add('redirect', HiddenType::class, ['data' => $redirect, 'mapped' => false]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var GroupeGn $groupeGn */
            $groupeGn = $form->getData();

            // Titre si territoire
            if (null === $groupeGn->getGroupe()->getTerritoire()) {
                if ($groupeGn->getSuzerin() || $groupeGn->getIntendant() || $groupeGn->getCamarilla(
                ) || $groupeGn->getConnetable() || $groupeGn->getNavigateur()) {
                    $this->addFlash('error', 'Les titres ne sont possibles que si le groupe à un territoire');
                }
                $groupeGn->setSuzerin(null);
                $groupeGn->setIntendant(null);
                $groupeGn->setCamarilla(null);
                $groupeGn->setConnetable(null);
                $groupeGn->setNavigateur(null);
            }

            $this->entityManager->persist($groupeGn);
            $this->entityManager->flush();
            $redirect = $form->getExtraData()['redirect'] ?? null;

            $this->addFlash('success', 'La participation au jeu a été enregistré.');

            if ($redirect) {
                return $this->redirect($redirect.'&tab=domaine&gn='.$groupeGn->getGn()->getId());
            }

            return $this->redirectToRoute(
                'groupeGn.list',
                ['groupe' => $groupe->getId(), 'tab' => 'domaine', 'gn' => $groupeGn->getGn()->getId()]
            );
        }

        return $this->render('groupeGn/update.twig', [
            'groupe' => $groupe,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ], new Response(null, !$form->isSubmitted() || $form->isValid() ? 200 : 422));
    }
}
