<?php

namespace App\Controller;

use App\Entity\Membre;
use App\Entity\Message;
use App\Entity\Postulant;
use App\Entity\SecondaryGroup;
use App\Enum\Role;
use App\Form\GroupeSecondaire\GroupeSecondaireForm;
use App\Form\GroupeSecondaire\GroupeSecondaireMaterielForm;
use App\Form\GroupeSecondaire\GroupeSecondaireNewMembreForm;
use App\Form\GroupeSecondaire\GroupeSecondairePostulerForm;
use App\Form\MessageForm;
use App\Manager\GroupeManager;
use App\Repository\SecondaryGroupRepository;
use App\Service\PagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class GroupeSecondaireController extends AbstractController
{
    #[Route('/groupeSecondaire/{groupeSecondaire}/postulant/{postulant}/accept', name: 'groupeSecondaire.postulant.accept')]
    public function acceptPostulantAction(
        #[MapEntity] SecondaryGroup $groupeSecondaire,
        #[MapEntity] Postulant $postulant,
    ): Response {
        $this->canManageGroup($groupeSecondaire);

        $personnage = $postulant->getPersonnage();

        $membre = new Membre();
        $membre->setPersonnage($personnage);
        $membre->setSecondaryGroup($groupeSecondaire);
        $membre->setSecret(false);

        if ($groupeSecondaire->isMembre($personnage)) {
            $this->addFlash('warning', 'le personnage est déjà membre du groupe secondaire.');
        } else {
            $this->entityManager->persist($membre);
            $this->entityManager->remove($postulant);
            $this->entityManager->flush();

            $this->mailer->newMessage(
                $postulant->getPersonnage()->getUser(),
                sprintf(
                    'Votre candidature pour rejoindre le groupe %s a été acceptée',
                    $groupeSecondaire->getLabel(),
                ),
                'Acceptation de votre candidature',
                $this->getUser(),
            );

            $this->addFlash('success', 'la candidature a été accepté.');
        }

        return $this->render(
            'groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($groupeSecondaire, ['isAdmin' => true]),
        );
    }

    protected function canManageGroup(?SecondaryGroup $secondaryGroup = null): void
    {
        $this->hasAccess($secondaryGroup, lowestCan: self::CAN_MANAGE);
    }

    protected function hasAccess(
        ?SecondaryGroup $secondaryGroup = null,
        array $roles = [Role::ROLE_GROUPE_TRANSVERSE],
        string $lowestCan = self::CAN_READ,
    ): void {
        $this->loadAccess($secondaryGroup, $roles);
        $this->checkHasAccess($roles, fn() => $this->can($lowestCan));
    }

    protected function loadAccess(
        ?SecondaryGroup $secondaryGroup = null,
        array $roles = [Role::ROLE_GROUPE_TRANSVERSE],
    ): void {
        $isAdmin = false;
        foreach ($roles as $role) {
            if ($this->isGranted($role->value)) {
                $isAdmin = true;
            }
        }

        $isResponsable = false;
        $isMembre = false;
        $canReadSecret = false;
        $canReadPrivate = false;
        if ($secondaryGroup) {
            $isResponsable = $this->getPersonnage()?->getId() === $secondaryGroup->getPersonnage()?->getId();

            if (!$isResponsable && !$isAdmin) {
                /** @var Membre $membre */
                foreach ($secondaryGroup->getMembres() as $membre) {
                    if ($membre->getPersonnage()->getId() === $this->getPersonnage()?->getId()) {
                        $isMembre = true;
                        $canReadSecret = $membre->getSecret();
                    }
                    // No usage for now :
                    if ($membre->isPrivate()) {
                        $canReadPrivate = true;
                    }
                }
            }
        }

        $this->setCan(self::IS_ADMIN, $isAdmin);
        $this->setCan(self::IS_MEMBRE, $isMembre);
        $this->setCan(self::CAN_MANAGE, $isResponsable || $isAdmin);
        $this->setCan(self::CAN_READ_PRIVATE, $isResponsable || $canReadSecret || $isAdmin || $canReadPrivate);
        $this->setCan(self::CAN_READ_SECRET, $isResponsable || $isAdmin || $canReadSecret);
        $this->setCan(self::CAN_WRITE, $isResponsable || $isAdmin);
        $this->setCan(self::CAN_READ, $isMembre || $this->can(self::CAN_READ));
    }

    /**
     * Construit le contexte pour la page détail de groupe secondaire (pour les orgas).
     *
     * @return array of
     */
    public function buildContextDetailTwig(
        SecondaryGroup $groupeSecondaire,
        ?array $extraParameters = null,
    ): array {
        $result = [
            'groupeSecondaire' => $groupeSecondaire,
            'gn' => GroupeManager::getGnActif($this->entityManager),
        ];

        if (null === $extraParameters) {
            return $result;
        }

        return [...$result, ...$extraParameters];
    }

    /**
     * Ajoute un groupe secondaire.
     */
    #[IsGranted(Role::ROLE_GROUPE_TRANSVERSE->value)]
    #[Route('/groupeSecondaire/add', name: 'groupeSecondaire.add')]
    public function adminAddAction(Request $request): RedirectResponse|Response
    {
        $groupeSecondaire = new SecondaryGroup();

        $form = $this->createForm(GroupeSecondaireForm::class, $groupeSecondaire)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add(
                'save_continue',
                SubmitType::class,
                ['label' => 'Sauvegarder & continuer'],
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeSecondaire = $form->getData();
            $this->entityManager->persist($groupeSecondaire);
            $this->entityManager->flush();

            /**
             * Ajoute le responsable du groupe dans le groupe s'il n'y est pas déjà.
             */
            $personnage = $groupeSecondaire->getResponsable();
            if ($personnage && !$groupeSecondaire->isMembre($personnage)) {
                $membre = new Membre();
                $membre->setPersonnage($personnage);
                $membre->setSecondaryGroup($groupeSecondaire);
                $membre->setSecret(false);
                $this->entityManager->persist($membre);
                $this->entityManager->flush();
                $groupeSecondaire->addMembre($membre);
            }

            $this->entityManager->persist($groupeSecondaire);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le groupe secondaire a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('groupeSecondaire.list', [], 303);
            }
            if ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('groupeSecondaire.add', [], 303);
            }
        }

        return $this->render('groupeSecondaire/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/groupeSecondaire/{groupeSecondaire}/contact', name: 'groupeSecondaire.contact')]
    public function contactLeaderAction(Request $request, #[MapEntity] SecondaryGroup $groupeSecondaire): Response
    {
        $this->loadAccess($groupeSecondaire);

        if ($groupeSecondaire->isSecret() && !$this->can(self::IS_MEMBRE)) {
            $this->addFlash('error', 'Ce groupe ne peut pas être contacté.');

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        $responsable = $groupeSecondaire->getPersonnage();
        if (!$responsable) {
            $this->addFlash('error', 'Ce groupe ne peut pas être contacté.');

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        $personnage = $this->getPersonnage();
        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir un personnage actif pour contacter le groupe.');

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        $message = new Message();
        $message->setTitle('Message pour le responsable du groupe '.$groupeSecondaire->getLabel());
        $message->setUserRelatedByAuteur($this->getUser());
        $message->setUserRelatedByDestinataire($responsable->getUser());
        $message->setCreationDate(new \DateTime('NOW'));
        $message->setUpdateDate(new \DateTime('NOW'));

        $form = $this->createForm(MessageForm::class, $message)
            ->add('envoyer', SubmitType::class, ['label' => 'Envoyer votre message']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->getData();

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            $this->mailer->notify($message);

            $this->addFlash('success', 'Votre message a été envoyé au joueur concerné.');

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        return $this->render('groupeSecondaire/contact.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/groupeSecondaire/{groupeSecondaire}/contact/membre/{membre}', name: 'groupeSecondaire.contact.membre')]
    public function contactMembreAction(
        Request $request,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
        #[MapEntity] $membre,
    ): Response {
        $this->loadAccess($groupeSecondaire);

        $personnage = $this->getPersonnage();
        if (!$personnage) {
            $this->addFlash('error', 'Vous devez avoir un personnage actif pour contacter ce membre.');

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        if (!$groupeSecondaire->isMembre($personnage) && !$this->can(self::CAN_READ)) {
            $this->addFlash('error', 'Vous devez être membre pour contacter un autre membre.');

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        $message = new Message();
        $message->setTitle('Message du membre du groupe '.$groupeSecondaire->getLabel());
        $message->setUserRelatedByAuteur($this->getUser());
        $message->setUserRelatedByDestinataire($membre->getUser());
        $message->setCreationDate(new \DateTime('NOW'));
        $message->setUpdateDate(new \DateTime('NOW'));

        $form = $this->createForm(MessageForm::class, $message)
            ->add('envoyer', SubmitType::class, ['label' => 'Envoyer votre message']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->getData();

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            $this->mailer->notify($message);

            $this->addFlash('success', 'Votre message a été envoyé au membre concerné.');

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        return $this->render('groupeSecondaire/contact.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/groupeSecondaire/{groupeSecondaire}/detail', name: 'groupeSecondaire.detail')]
    public function detailAction(#[MapEntity] SecondaryGroup $groupeSecondaire): Response
    {
        $this->hasAccess($groupeSecondaire);

        if ($groupeSecondaire->isSecret() && !$this->can(self::IS_MEMBRE)) {
            $this->addFlash('error', "Vous n'êtes pas membres");

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        return $this->render(
            'groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($groupeSecondaire),
        );
    }

    #[Route('/groupeSecondaire/{groupeSecondaire}/postuler', name: 'groupeSecondaire.postuler')]
    public function groupeSecondairePostulerAction(
        Request $request,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
    ): RedirectResponse|Response {
        if ($groupeSecondaire->isSecret()) {
            $this->addFlash(
                'error',
                'Par Crom! On ne postule pas ici ! On est uniquement convié par qui de droit !',
            );

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        $personnage = $this->getPersonnage();

        if (!$personnage) {
            $this->addFlash(
                'error',
                'Vous devez avoir créé un personnage et le choisir comme personnage actif avant de postuler à un groupe secondaire!',
            );

            return $this->redirectToRoute('user.detail', ['user' => $this->getUser()?->getId()], 303);
        }

        /*
         * Si le joueur est déjà postulant dans ce groupe, refuser la demande
         */
        if ($groupeSecondaire->isPostulant($personnage)) {
            $this->addFlash('error', 'Vous avez déjà postulé dans ce groupe. Inutile d\'en refaire la demande.');

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        /*
         * Si le joueur est déjà membre de ce groupe, refUser la demande
         */
        if ($groupeSecondaire->isMembre($personnage)) {
            $this->addFlash('error', 'Vous êtes déjà membre de ce groupe. Inutile d\'en refaire la demande.');

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        $form = $this->createForm(GroupeSecondairePostulerForm::class)
            ->add('postuler', SubmitType::class, ['label' => 'Postuler']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if (empty($data['explanation'])) {
                $this->addFlash('error', 'Vos devez remplir le champ Explication.');
            } else {
                $postulant = new Postulant();
                $postulant->setPersonnage($personnage);
                $postulant->setSecondaryGroup($groupeSecondaire);
                $postulant->setExplanation($data['explanation']);
                $postulant->setWaiting(false);

                $this->entityManager->persist($postulant);

                // envoi d'un mail au chef du groupe secondaire
                if ($responsable = $groupeSecondaire->getResponsable()) {
                    $message = new Message();
                    $message->setTitle('Candidature pour le groupe '.$groupeSecondaire->getLabel());
                    $message->setUserRelatedByAuteur($this->getUser());
                    $message->setUserRelatedByDestinataire($responsable->getUser());
                    $message->setCreationDate(new \DateTime('NOW'));
                    $message->setUpdateDate(new \DateTime('NOW'));
                    $message->setText($data['explanation']);

                    $this->entityManager->persist($message);

                    $this->mailer->notify($message);
                }

                $this->entityManager->flush();

                $this->addFlash('success', 'Votre candidature a été enregistrée, et transmise au chef de groupe.');

                return $this->redirectToRoute(
                    'groupeSecondaire.detail',
                    ['groupeSecondaire' => $groupeSecondaire->getId()],
                    303,
                );
            }
        }

        return $this->render('groupeSecondaire/postuler.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des groupes secondaires (pour les orgas).
     */
    #[Route('/groupeSecondaire', name: 'groupeSecondaire.list')]
    public function listAction(
        Request $request,
        PagerService $pagerService,
        SecondaryGroupRepository $secondaryGroupRepository,
    ): Response {
        $alias = $secondaryGroupRepository->getAlias();
        $queryBuilder = $secondaryGroupRepository->createQueryBuilder($alias);
        $pagerService->setRequest($request)
            ->setRepository($secondaryGroupRepository)
            ->setLimit(25);

        // If not admin, only groupe where user's personnage are.
        $isAdmin = $this->hasRoles([Role::ROLE_GROUPE_TRANSVERSE]);
        $fetchCollection = false;
        if (!$isAdmin && $personnage = $this->getPersonnage()) {
            $fetchCollection = true;  // may have issue with OrderBy but paginator will load result without member in (due to leftjoin(member))
            $queryBuilder = $secondaryGroupRepository->visibleForPersonnage(
                $queryBuilder,
                $personnage->getId(),
            );
        }

        return $this->render('groupeSecondaire/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $secondaryGroupRepository->searchPaginated($pagerService, $queryBuilder, $fetchCollection),
        ]);
    }

    /**
     * Impression de l'enveloppe du groupe secondaire.
     */
    #[IsGranted(Role::ROLE_GROUPE_TRANSVERSE->value)]
    #[Route('/groupeSecondaire/{groupeSecondaire}/print', name: 'groupeSecondaire.materiel.print')]
    public function materielPrintAction(
        #[MapEntity] SecondaryGroup $groupeSecondaire,
    ): Response {
        return $this->render('groupeSecondaire/print.twig', [
            'groupeSecondaire' => $groupeSecondaire,
        ]);
    }

    /**
     * Impression de toutes les enveloppes groupe secondaire.
     */
    #[IsGranted(Role::ROLE_GROUPE_TRANSVERSE->value)]
    #[Route('/groupeSecondaire/printAll', name: 'groupeSecondaire.materiel.printAll')]
    public function materielPrintAllAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $groupeSecondaires = $entityManager->getRepository(SecondaryGroup::class)->findAll();

        return $this->render('groupeSecondaire/printAll.twig', [
            'groupeSecondaires' => $groupeSecondaires,
        ]);
    }

    /**
     * Mise à jour du matériel necessaire à un groupe secondaire.
     */
    #[IsGranted(Role::ROLE_GROUPE_TRANSVERSE->value)]
    #[Route('/groupeSecondaire/{groupeSecondaire}/materielUpdate', name: 'groupeSecondaire.materiel.update')]
    public function materielUpdateAction(
        Request $request,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
    ): RedirectResponse|Response {
        $form = $this->createForm(GroupeSecondaireMaterielForm::class, $groupeSecondaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeSecondaire = $form->getData();
            $this->entityManager->persist($groupeSecondaire);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le groupe secondaire a été mis à jour.');

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        return $this->render('groupeSecondaire/materiel.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un nouveau membre au groupe secondaire.
     */
    #[Route('/groupeSecondaire/{groupeSecondaire}/addMember', name: 'groupeSecondaire.newMembre')]
    public function newMembreAction(
        Request $request,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
    ): RedirectResponse|Response {
        $this->canManageGroup($groupeSecondaire);
        $form = $this->createForm(GroupeSecondaireNewMembreForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form['personnage']->getData();

            if (!$personnage) {
                $this->addFlash('warning', "Vous n'avez pas de personnage");

                return $this->redirectToRoute(
                    'groupeSecondaire.detail',
                    ['groupeSecondaire' => $groupeSecondaire->getId()],
                    303,
                );
            }

            $membre = new Membre();

            if ($groupeSecondaire->isMembre($personnage)) {
                $this->addFlash('warning', 'le personnage est déjà membre du groupe secondaire.');

                return $this->redirectToRoute(
                    'groupeSecondaire.detail',
                    ['groupeSecondaire' => $groupeSecondaire->getId()],
                    303,
                );
            }

            $membre->setPersonnage($personnage);
            $membre->setSecondaryGroup($groupeSecondaire);
            $membre->setSecret(false);

            $this->entityManager->persist($membre);
            $this->entityManager->flush();

            $this->addFlash('success', 'le personnage a été ajouté au groupe secondaire.');

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        return $this->render(
            'groupeSecondaire/newMembre.twig',
            $this->buildContextDetailTwig($groupeSecondaire, ['form' => $form->createView(), 'isAdmin' => true]),
        );
    }

    #[Route('/groupeSecondaire/{groupeSecondaire}/postulant/{postulant}/response', name: 'groupeSecondaire.postulant.response')]
    public function postulantResponseAction(
        Request $request,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
        #[MapEntity] Postulant $postulant,
    ): RedirectResponse|Response {
        $this->canManageGroup($groupeSecondaire);

        if (!$destinataire = $postulant->getPersonnage()->getUser()) {
            $this->addFlash('error', "le personnage n'est pas lié à un utilisateur joignable.");

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        $message = new Message();

        $message->setTitle('Réponse à votre candidature');
        $message->setUserRelatedByAuteur($this->getUser());
        $message->setUserRelatedByDestinataire($destinataire);
        $message->setCreationDate(new \DateTime('NOW'));
        $message->setUpdateDate(new \DateTime('NOW'));

        $form = $this->createForm(MessageForm::class, $message)
            ->add('envoyer', SubmitType::class, ['label' => 'Envoyer votre réponse']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Message $message */
            $message = $form->getData();
            $message->setText(html_entity_decode($message->getText()));

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            $this->mailer->notify($message);

            $this->addFlash('success', 'Votre message a été envoyé au joueur concerné.');

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        return $this->render('groupeSecondaire/gestion_response.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'postulant' => $postulant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('groupeSecondaire/{groupeSecondaire}/postulant/{postulant}/wait', name: 'groupeSecondaire.postulant.wait')]
    public function postulantWaitAction(
        Request $request,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
        #[MapEntity] Postulant $postulant,
    ): RedirectResponse|Response {
        $this->canManageGroup($groupeSecondaire);

        $form = $this->createFormBuilder($postulant)
            ->add('envoyer', SubmitType::class, ['label' => 'Laisser en attente'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $postulant->setWaiting(true);
            $this->entityManager->persist($postulant);
            $this->entityManager->flush();

            // NOTIFY $app['notify']->waitGroupeSecondaire($personnage->getUser(), $groupeSecondaire);

            $this->addFlash('success', 'La candidature reste en attente. Un message a été envoyé au joueur concerné.');

            return $this->redirectToRoute(
                'groupeSecondaire.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303,
            );
        }

        return $this->render('groupeSecondaire/gestion_wait.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'postulant' => $postulant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/groupeSecondaire/{groupeSecondaire}/membre/{membre}/remove', name: 'groupeSecondaire.member.remove')]
    public function removeMembreAction(
        #[MapEntity] SecondaryGroup $groupeSecondaire,
        #[MapEntity] Membre $membre,
    ): Response {
        $this->canManageGroup($groupeSecondaire);

        return $this->genericDelete(
            $membre,
            'Retirer un membre',
            'Le membre a été retiré',
            ['route' => 'groupeSecondaire.detail', 'params' => ['groupeSecondaire' => $groupeSecondaire->getId()]],
            [
                ['route' => $this->generateUrl('groupeSecondaire.list'), 'name' => 'Liste des groupes secondaires'],
                [
                    'route' => $this->generateUrl(
                        'groupeSecondaire.detail',
                        ['groupeSecondaire' => $groupeSecondaire->getId()],
                    ),
                    'membre' => $membre->getId(),
                    'name' => $membre->getPersonnage()->getPublicName(),
                ],
                ['name' => 'Supprimer un membre'],
            ],
            content: $membre->getPersonnage()->getPublicName(),
        );
    }

    #[Route('/groupeSecondaire/{groupeSecondaire}/postulant/{postulant}/remove', name: 'groupeSecondaire.postulant.remove')]
    public function removePostulantAction(
        Request $request,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
        #[MapEntity] Postulant $postulant,
    ): Response {
        $this->canManageGroup($groupeSecondaire);

        $this->entityManager->remove($postulant);
        $this->entityManager->flush();

        $this->addFlash('success', 'la candidature a été supprimée.');

        return $this->render(
            'groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($groupeSecondaire, ['isAdmin' => true]),
        );
    }

    #[Route('/groupeSecondaire/{groupeSecondaire}/secretOff/{membre}', name: 'groupeSecondaire.secret.off')]
    public function secretOffAction(
        #[MapEntity] SecondaryGroup $groupeSecondaire,
        #[MapEntity] Membre $membre,
    ): Response {
        $this->canManageGroup($groupeSecondaire);

        $membre->setSecret(false);
        $this->entityManager->persist($membre);
        $this->entityManager->flush();

        return $this->render(
            'groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($groupeSecondaire),
        );
    }

    #[Route('/groupeSecondaire/{groupeSecondaire}/secretOn/{membre}', name: 'groupeSecondaire.secret.on')]
    public function secretOnAction(
        #[MapEntity] SecondaryGroup $groupeSecondaire,
        #[MapEntity] Membre $membre,
    ): Response {
        $this->canManageGroup($groupeSecondaire);

        $membre->setSecret(true);
        $this->entityManager->persist($membre);
        $this->entityManager->flush();

        return $this->render(
            'groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($groupeSecondaire, ['isAdmin' => true]),
        );
    }

    #[Route('/groupeSecondaire/{groupeSecondaire}/update', name: 'groupeSecondaire.update')]
    public function updateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
    ): RedirectResponse|Response {
        $this->canManageGroup($groupeSecondaire);

        $form = $this->createForm(GroupeSecondaireForm::class, $groupeSecondaire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder', 'attr' => ['class' => 'btn btn-secondary']])
            ->add('delete', SubmitType::class, ['label' => 'Supprimer', 'attr' => ['class' => 'btn btn-secondary']]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeSecondaire = $form->getData();

            if ($form->get('update')->isClicked()) {
                /**
                 * Ajoute le responsable du groupe dans le groupe si il n'y est pas déjà.
                 */
                $personnage = $groupeSecondaire->getResponsable();
                if (!$groupeSecondaire->isMembre($personnage)) {
                    $membre = new Membre();
                    $membre->setPersonnage($personnage);
                    $membre->setSecondaryGroup($groupeSecondaire);
                    $membre->setSecret(false);

                    $entityManager->persist($membre);
                    $entityManager->flush();

                    $groupeSecondaire->addMembre($membre);
                }
                /*
                 * Retire la candidature du responsable si elle existe
                 */
                foreach ($groupeSecondaire->getPostulants() as $postulant) {
                    if ($postulant->getPersonnage()?->getId() === $personnage->getId()) {
                        $this->entityManager->remove($postulant);
                    }
                }
                $entityManager->persist($groupeSecondaire);
                $entityManager->flush();
                $this->addFlash('success', 'Le groupe secondaire a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($groupeSecondaire);
                $entityManager->flush();
                $this->addFlash('success', 'Le groupe secondaire a été supprimé.');
            }

            return $this->redirectToRoute('groupeSecondaire.list');
        }

        return $this->render('groupeSecondaire/update.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'form' => $form->createView(),
        ]);
    }

    #[\Deprecated]
    protected function canSeeGroup(?SecondaryGroup $secondaryGroup = null, ?Membre $membre = null): void
    {
        try {
            $this->canManageGroup($secondaryGroup, $membre);

            return;
        } catch (AccessDeniedException $e) {
            // may not able to see it.
        }

        if ($secondaryGroup) {
            /** @var SecondaryGroupRepository $sgRepository */
            $sgRepository = $this->entityManager->getRepository(SecondaryGroup::class);
            $sgRepository->isMember($secondaryGroup, $membre);
        }

        throw new AccessDeniedException();
    }

    #[\Deprecated]
    protected function canSeeSecret(SecondaryGroup $secondaryGroup, ?Membre $membre = null): bool
    {
        if ($membre && $membre->getSecret()) {
            return true;
        }

        try {
            $this->canManageGroup($secondaryGroup, $membre);

            return true;
        } catch (AccessDeniedException $e) {
        }

        /** @var SecondaryGroupRepository $sgRepository */
        $sgRepository = $this->entityManager->getRepository(SecondaryGroup::class);
        $sgRepository->userCanSeeSecret($this->getUser(), $secondaryGroup);

        return false;
    }
}
