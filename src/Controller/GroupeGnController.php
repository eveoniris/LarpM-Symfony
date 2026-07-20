<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\GroupeGnDemande;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\GroupeGnDemandeType;
use App\Enum\Role;
use App\Form\GroupeGn\GroupeGnOrdreType;
use App\Form\GroupeGn\GroupeGnPlaceAvailableType;
use App\Form\GroupeGn\GroupeGnResponsableType;
use App\Form\GroupeGn\GroupeGnType;
use App\Repository\GroupeGnDemandeRepository;
use App\Repository\GroupeGnRepository;
use App\Repository\ParticipantRepository;
use App\Security\MultiRolesExpression;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    public function addAction(Request $request, #[MapEntity] Groupe $groupe): RedirectResponse|Response
    {
        $groupeGn = new GroupeGn();
        $groupeGn->setGroupe($groupe);

        // Si ce groupe a déjà une participation à un jeu, reprendre le code/jeuStrategique/jeuMaritime/placeAvailable
        if ($groupe->getGroupeGns()->count() > 0) {
            $jeu = $groupe->getGroupeGns()->last();
            assert($jeu instanceof GroupeGn);
            $groupeGn->setCode($jeu->getCode());
            $groupeGn->setPlaceAvailable($jeu->getPlaceAvailable());
            $groupeGn->setJeuStrategique($jeu->getJeuStrategique());
            $groupeGn->setJeuMaritime($jeu->getJeuMaritime());
        }

        $form = $this->createForm(GroupeGnType::class, $groupeGn)->add('submit', SubmitType::class, [
            'label' => 'Enregistrer',
            'attr' => ['class' => 'btn btn-secondary'],
        ]);

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
    public function groupeAction(Request $request, #[MapEntity] GroupeGn $groupeGn): Response
    {
        $participant = $this->getUser()?->getParticipant($groupeGn->getGn());

        if (!$participant) {
            $this->redirectToRoute('app_login');
        }

        $canManage = $this->canManageGroup($groupeGn);
        $canSeePrivateDetail = $canManage || $this->canSeeGroup($groupeGn);

        return $this->render('groupe/detail.twig', [
            'groupe' => $groupeGn->getGroupe(),
            'tab' => $request->query->get('tab', 'detail'),
            'participant' => $participant,
            'groupeGn' => $groupeGn,
            'canSeePrivateDetail' => $canSeePrivateDetail,
            'canManage' => $canManage,
            'isAdmin' => $this->isGranted(Role::SCENARISTE, Role::ORGA),
            'gn' => $groupeGn->getGn(),
        ]);
    }

    /**
     * Responsable d'une session : responsable de la session (groupe_gn), sinon responsable du groupe.
     */
    protected function getResponsableUser(?GroupeGn $groupeGn = null): ?User
    {
        return $groupeGn?->getResponsable()?->getUser() ?? $groupeGn?->getGroupe()?->getUserRelatedByResponsableId();
    }

    protected function canManageGroup(?GroupeGn $groupeGn = null, bool $throw = false): bool
    {
        // Admin
        if ($this->isGranted(Role::SCENARISTE->value) || $this->isGranted(Role::ORGA->value)) {
            return true;
        }

        /** @var User $user */
        $user = $this->getUser();

        // Est le responsable de la session, sinon du groupe ?
        if ($this->getResponsableUser($groupeGn)?->getId() === $user->getId()) {
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
        $form = $this->createForm(GroupeGnOrdreType::class, $groupeGn, ['groupeGnId' => $groupeGn->getId()])->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

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
     * Invite un joueur à rejoindre le groupe (pour les chefs de groupe).
     *
     * Crée une invitation (GroupeGnDemande) que le joueur devra confirmer ; il n'est pas
     * ajouté directement afin de garantir son consentement.
     */
    #[Route('/groupeGn/{groupeGn}/joueur/add/', name: 'groupeGn.joueur.add')]
    public function joueurAddAction(
        Request $request,
        EntityManagerInterface $entityManager,
        GroupeGn $groupeGn,
    ): RedirectResponse|Response {
        $this->canManageGroup($groupeGn, throw: true);

        $participant = $this->getUser()->getParticipant($groupeGn->getGn());

        $form = $this
            ->createFormBuilder()
            ->add('participant', EntityType::class, [
                'label' => 'Choisissez le joueur à inviter dans votre groupe',
                'required' => true,
                'class' => Participant::class,
                'choice_label' => 'user.username',
                'query_builder' => static function (ParticipantRepository $er) use ($groupeGn) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->join('p.user', 'u');
                    $qb->join('p.gn', 'gn');
                    $qb->where($qb->expr()->isNull('p.groupeGn'));
                    $qb->andWhere('gn.id = :gnId');
                    $qb->setParameter('gnId', $groupeGn->getGn()->getId());
                    $qb->orderBy('u.username', 'ASC');

                    return $qb;
                },
                'attr' => [
                    'data-live-search' => 'true',
                    'placeholder' => 'Participant',
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message (facultatif)',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Inviter le joueur choisi'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            /** @var Participant|null $invite */
            $invite = $data['participant'];

            if ($invite) {
                /** @var GroupeGnDemandeRepository $demandeRepository */
                $demandeRepository = $entityManager->getRepository(GroupeGnDemande::class);

                if ($demandeRepository->findOneByParticipantAndGroupeGn($invite, $groupeGn)) {
                    $this->addFlash('info', 'Une demande est déjà en cours pour ce joueur.');
                } else {
                    $demande = (new GroupeGnDemande())
                        ->setType(GroupeGnDemandeType::INVITATION)
                        ->setParticipant($invite)
                        ->setGroupeGn($groupeGn)
                        ->setMessage($data['message'] ?: null);
                    $entityManager->persist($demande);
                    $entityManager->flush();

                    $this->notifyInvitation($demande);

                    $this->addFlash('success', 'Une invitation a été envoyée au joueur.');
                }
            }

            return $this->redirectToRoute('groupe.detail', [
                'groupeGn' => $groupeGn->getId(),
                'groupe' => $groupeGn->getGroupe()->getId(),
            ]);
        }

        return $this->render('groupeGn/invite.twig', [
            'groupeGn' => $groupeGn,
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Envoie un message de messagerie au joueur invité.
     */
    private function notifyInvitation(GroupeGnDemande $demande): void
    {
        $groupeGn = $demande->getGroupeGn();
        $destinataire = $demande->getParticipant()->getUser();
        if (!$destinataire) {
            return;
        }

        $texte = \sprintf('Le groupe %s vous invite à le rejoindre pour la session %s.', $groupeGn->getGroupe()->getNom(), $groupeGn->getGn()->getLabel());
        if ($demande->getMessage()) {
            $texte .= "\n\n" . $demande->getMessage();
        }
        $texte .= "\n\nRendez-vous sur votre page de participation pour accepter ou refuser cette invitation.";

        $this->mailer->newMessage($destinataire, $texte, 'Invitation à rejoindre un groupe', $this->getUser());
    }

    /**
     * Liste des sessions de jeu pour un groupe.
     */
    #[Route('/groupeGn/{groupe}/list/', name: 'groupeGn.list')]
    public function listAction(Groupe $groupe): Response
    {
        $canManage = $this->canManageGroupe($groupe, throw: true);

        /** @var GroupeGnDemandeRepository $demandeRepository */
        $demandeRepository = $this->entityManager->getRepository(GroupeGnDemande::class);

        $candidatures = [];
        foreach ($groupe->getGroupeGns() as $groupeGn) {
            $candidatures[$groupeGn->getId()] = $demandeRepository->findCandidaturesByGroupeGn($groupeGn);
        }

        return $this->render('groupeGn/list.twig', [
            'groupe' => $groupe,
            'canManage' => $canManage,
            'candidatures' => $candidatures,
        ]);
    }

    /**
     * Peut gérer le groupe : staff, responsable du groupe, ou responsable de l'une de ses sessions.
     */
    protected function canManageGroupe(Groupe $groupe, bool $throw = false): bool
    {
        if ($this->isGranted(Role::SCENARISTE->value) || $this->isGranted(Role::ORGA->value)) {
            return true;
        }

        $userId = $this->getUser()?->getId();

        if ($groupe->getUserRelatedByResponsableId()?->getId() === $userId) {
            return true;
        }

        foreach ($groupe->getGroupeGns() as $groupeGn) {
            if ($groupeGn->getResponsable()?->getUser()?->getId() === $userId) {
                return true;
            }
        }

        return $throw ? throw new AccessDeniedException() : false;
    }

    /**
     * Ajoute un participant à un groupe.
     */
    #[Route('/groupeGn/{groupeGn}/participants/add/', name: 'groupeGn.participants.add')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE), message: 'You are not allowed to access to this.')]
    public function participantAddAction(Request $request, GroupeGn $groupeGn): RedirectResponse|Response
    {
        // $form = $this->createForm(GroupeGnType::class, $groupeGn)
        //    ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);
        // TODO Migrate to V2
        $form = $this
            ->createFormBuilder()
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
        $this->canManageGroup($groupeGn, throw: true);

        $form = $this->createFormBuilder()->add('submit', SubmitType::class, ['label' => 'Retirer'])->getForm();

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
     * Détermine qui peut répondre (accepter/refuser) à une demande.
     *
     * - Invitation (chef -> joueur) : le joueur concerné (ou le staff).
     * - Candidature (joueur -> groupe) : le responsable du groupe (ou le staff).
     */
    protected function canRespondToDemande(GroupeGnDemande $demande, bool $throw = false): bool
    {
        if ($demande->isInvitation()) {
            if ($this->isGranted(Role::SCENARISTE->value) || $this->isGranted(Role::ORGA->value)) {
                return true;
            }

            $owner = $demande->getParticipant()->getUser();
            if ($owner && $owner->getId() === $this->getUser()?->getId()) {
                return true;
            }

            return $throw ? throw new AccessDeniedException() : false;
        }

        return $this->canManageGroup($demande->getGroupeGn(), $throw);
    }

    /**
     * Accepte une demande (invitation acceptée par le joueur, ou candidature acceptée par le responsable).
     */
    #[Route('/groupeGn/demande/{demande}/accept', name: 'groupeGn.demande.accept')]
    public function demandeAcceptAction(EntityManagerInterface $entityManager, GroupeGnDemande $demande): RedirectResponse
    {
        $this->canRespondToDemande($demande, throw: true);

        $participant = $demande->getParticipant();
        $groupeGn = $demande->getGroupeGn();
        $wasInvitation = $demande->isInvitation();

        if (!$participant->getBillet()) {
            $this->addFlash('error', "Le joueur n'a pas de billet, il ne peut pas rejoindre un groupe.");

            return $this->redirectAfterDemande($demande);
        }

        if ($participant->getGroupeGn()) {
            $this->addFlash('info', 'Le joueur appartient déjà à un groupe pour cette session.');
            $entityManager->remove($demande);
            $entityManager->flush();

            return $this->redirectAfterDemande($demande, $groupeGn);
        }

        $participant->setGroupeGn($groupeGn);
        $entityManager->persist($participant);

        // Le joueur a désormais un groupe : on purge toutes ses autres demandes en attente.
        /** @var GroupeGnDemandeRepository $demandeRepository */
        $demandeRepository = $entityManager->getRepository(GroupeGnDemande::class);
        foreach ($demandeRepository->findBy(['participant' => $participant]) as $autre) {
            $entityManager->remove($autre);
        }
        $entityManager->flush();

        $this->notifyDemandeResponse($participant, $groupeGn, $wasInvitation, accepted: true);

        $this->addFlash('success', 'Le joueur a rejoint le groupe.');

        return $this->redirectAfterDemande($demande, $groupeGn);
    }

    /**
     * Refuse une demande (invitation déclinée par le joueur, ou candidature refusée par le responsable).
     */
    #[Route('/groupeGn/demande/{demande}/refuse', name: 'groupeGn.demande.refuse')]
    public function demandeRefuseAction(EntityManagerInterface $entityManager, GroupeGnDemande $demande): RedirectResponse
    {
        $this->canRespondToDemande($demande, throw: true);

        $participant = $demande->getParticipant();
        $groupeGn = $demande->getGroupeGn();
        $wasInvitation = $demande->isInvitation();

        $entityManager->remove($demande);
        $entityManager->flush();

        $this->notifyDemandeResponse($participant, $groupeGn, $wasInvitation, accepted: false);

        $this->addFlash('success', 'La demande a été refusée.');

        return $this->redirectAfterDemande($demande, $groupeGn);
    }

    /**
     * Redirige selon le type de demande : invitation -> page du joueur, candidature -> gestion du groupe.
     */
    private function redirectAfterDemande(GroupeGnDemande $demande, ?GroupeGn $groupeGn = null): RedirectResponse
    {
        $groupeGn ??= $demande->getGroupeGn();

        if ($demande->isInvitation()) {
            return $this->redirectToRoute('participant.index', ['participant' => $demande->getParticipant()->getId()]);
        }

        return $this->redirectToRoute('groupeGn.list', ['groupe' => $groupeGn->getGroupe()->getId()]);
    }

    /**
     * Notifie l'autre partie du résultat de la demande via la messagerie.
     */
    private function notifyDemandeResponse(Participant $participant, GroupeGn $groupeGn, bool $wasInvitation, bool $accepted): void
    {
        $groupeNom = $groupeGn->getGroupe()->getNom();
        $gnLabel = $groupeGn->getGn()->getLabel();

        if ($wasInvitation) {
            // Le joueur a répondu -> on prévient le responsable.
            $destinataire = $this->getResponsableUser($groupeGn);
            $texte = $accepted
                ? \sprintf('%s a accepté votre invitation à rejoindre le groupe %s pour la session %s.', $participant->getUser()?->getDisplayName(), $groupeNom, $gnLabel)
                : \sprintf('%s a décliné votre invitation à rejoindre le groupe %s pour la session %s.', $participant->getUser()?->getDisplayName(), $groupeNom, $gnLabel);
            $sujet = 'Réponse à votre invitation';
        } else {
            // Le responsable a répondu -> on prévient le joueur.
            $destinataire = $participant->getUser();
            $texte = $accepted
                ? \sprintf('Votre candidature pour rejoindre le groupe %s (session %s) a été acceptée.', $groupeNom, $gnLabel)
                : \sprintf('Votre candidature pour rejoindre le groupe %s (session %s) a été refusée.', $groupeNom, $gnLabel);
            $sujet = 'Réponse à votre candidature';
        }

        if ($destinataire) {
            $this->mailer->newMessage($destinataire, $texte, $sujet, $this->getUser());
        }
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

        $form = $this->createForm(GroupeGnPlaceAvailableType::class, $groupeGn)->add('submit', SubmitType::class, [
            'label' => 'Enregistrer',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeGn = $form->getData();
            $entityManager->persist($groupeGn);
            $entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistré.');

            return $this->redirectToRoute('groupe.groupe.detail', [
                'groupeGn' => $groupeGn->getId(),
                'groupe' => $groupeGn->getGroupe()->getId(),
            ]);
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
        $form = $this->createForm(GroupeGnResponsableType::class, $groupeGn)->add('responsable', EntityType::class, [
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
        ])->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

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
    public function updateAction(Request $request, #[MapEntity] GroupeGn $groupeGn): RedirectResponse|Response
    {
        $this->checkGroupeLocked($groupeGn->getGroupe());
        if ($r = $this->checkGroupeLocked($groupeGn->getGroupe())) {
            return $r;
        }

        $redirect = $request->query->get('redirect');

        $this->hasAccess($groupeGn, [Role::WARGAME]);

        $form = $this->createForm(GroupeGnType::class, $groupeGn, [
            'allow_extra_fields' => true,
        ])->add('submit', SubmitType::class, ['label' => 'Enregistrer', 'attr' => ['class' => 'btn btn-secondary']]);

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
                if (!$id) {
                    continue;
                }

                if (isset($ids[$id])) {
                    ++$ids[$id];
                    $form->get($child)->addError(new FormError("Un personnage ne peut avoir qu'un seul titre"));
                } else {
                    $ids[$id] = 1;
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var GroupeGn $groupeGn */
            $groupeGn = $form->getData();

            // Titre si territoire
            if (null === $groupeGn->getGroupe()->getTerritoire()) {
                if ($groupeGn->getSuzerain() || $groupeGn->getIntendant() || $groupeGn->getCamarilla() || $groupeGn->getConnetable() || $groupeGn->getNavigateur() || $groupeGn->getDiplomate()) {
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

            return $this->redirectToRoute('groupe.detail', [
                'groupe' => $groupeGn->getGroupe()->getId(),
                'gn' => $groupeGn->getGn()->getId(),
                'groupeGn' => $groupeGn->getId(),
                'tab' => 'domaine',
            ]);
        }

        return $this->render(
            'groupeGn/update.twig',
            [
                'groupe' => $groupeGn->getGroupe(),
                'groupeGn' => $groupeGn,
                'form' => $form->createView(),
            ],
            new Response(null, !$form->isSubmitted() || $form->isValid() ? 200 : 422),
        );
    }

    /** @param array<int, Role> $roles */
    protected function hasAccess(GroupeGn $groupeGn, array $roles = []): void
    {
        $isResponsable = $this->getUser()?->getId() === $groupeGn->getParticipant()?->getUser()?->getId();

        $isMembre = $isResponsable;
        if (!$isMembre) {
            /** @var Participant $participant */
            foreach ($groupeGn->getParticipants() as $participant) {
                if ($participant->getUser()?->getId() !== $this->getUser()?->getId()) {
                    continue;
                }

                $isMembre = true;
            }
        }

        $isSuzerain = false;
        if ($this->getPersonnage() && $this->getPersonnage()->getId() === $groupeGn->getSuzerain()?->getId()) {
            $isSuzerain = true;
        }

        // TODO check if membre can read secret

        $this->setCan(self::IS_ADMIN, $this->isGranted(Role::WARGAME->value));
        $this->setCan(self::CAN_MANAGE, $isResponsable);
        $this->setCan(self::CAN_READ_PRIVATE, $isResponsable || $isMembre);
        $this->setCan(self::CAN_READ_SECRET, $isResponsable);
        $this->setCan(self::CAN_WRITE, $isResponsable || $isSuzerain);
        $this->setCan(self::CAN_READ, $isMembre || $isSuzerain);

        $this->checkHasAccess($roles, fn () => $this->can(self::CAN_READ));
    }
}
