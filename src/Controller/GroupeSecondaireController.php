<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Entity\Membre;
use App\Entity\SecondaryGroup;
use App\Entity\Topic;
use App\Entity\User;
use App\Enum\Role;
use App\Form\GroupeSecondaire\GroupeSecondaireForm;
use App\Form\GroupeSecondaire\GroupeSecondaireMaterielForm;
use App\Form\GroupeSecondaire\GroupeSecondaireNewMembreForm;
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

class GroupeSecondaireController extends AbstractController
{
    /**
     * Liste des groupes secondaires (pour les orgas).
     */
    #[Route('/groupeSecondaire', name: 'groupeSecondaire.admin.list')]
    #[Route('/groupeSecondaire', name: 'groupeSecondaire.list')]
    #[IsGranted('ROLE_USER')]
    public function adminListAction(
        Request $request,
        PagerService $pagerService,
        SecondaryGroupRepository $secondaryGroupRepository,
    ): Response {
        $alias = $secondaryGroupRepository->getAlias();
        $queryBuilder = $secondaryGroupRepository->createQueryBuilder($alias);
        $pagerService->setRequest($request)->setRepository($secondaryGroupRepository)->setLimit(25);

        // If not admin, only groupe where user's personnage are.
        $isAdmin = $this->isGranted(Role::SCENARISTE->value);
        if (!$isAdmin) {
            $queryBuilder = $secondaryGroupRepository->visibleForPersonnage(
                $queryBuilder,
                array_column(
                    $this->entityManager->getRepository(User::class)->getPersonnagesIds($this->getUser()),
                    'id'
                )
            );
        }

        return $this->render('groupeSecondaire/list.twig', [
            'pagerService' => $pagerService,
            'isAdmin' => $isAdmin,
            'paginator' => $secondaryGroupRepository->searchPaginated($pagerService, $queryBuilder),
        ]);
    }

    /**
     * Ajoute un groupe secondaire.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/groupeSecondaire/add', name: 'groupeSecondaire.admin.add')]
    public function adminAddAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $groupeSecondaire = new SecondaryGroup();

        $form = $this->createForm(GroupeSecondaireForm::class, $groupeSecondaire)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add(
                'save_continue',
                SubmitType::class,
                ['label' => 'Sauvegarder & continuer']
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeSecondaire = $form->getData();

            /**
             * Création des topics associés à ce groupe
             * un topic doit être créé par GN auquel ce groupe est inscrit.
             *
             * @var Topic $topic
             */
            $topic = new Topic();
            $topic->setTitle($groupeSecondaire->getLabel());
            $topic->setDescription($groupeSecondaire->getDescription());
            $topic->setUser($this->getUser());

            // $topic->setTopic($app['larp.manager']->findTopic('TOPIC_GROUPE_SECONDAIRE'));
            $topicRepo = $entityManager->getRepository('\\'.Topic::class);
            $topic->setTopic($topicRepo->findOneByKey('TOPIC_GROUPE_SECONDAIRE'));

            $entityManager->persist($topic);

            $groupeSecondaire->setTopic($topic);
            $entityManager->persist($groupeSecondaire);
            $entityManager->flush();

            // défini les droits d'accés à ce forum
            // (les membres du groupe ont le droit d'accéder à ce forum)
            $topic->setObjectId($groupeSecondaire->getId());
            $topic->setRight('GROUPE_SECONDAIRE_MEMBER');

            /**
             * Ajoute le responsable du groupe dans le groupe si il n'y est pas déjà.
             */
            $personnage = $groupeSecondaire->getResponsable();
            if ($personnage && !$groupeSecondaire->isMembre($personnage)) {
                $membre = new Membre();
                $membre->setPersonnage($personnage);
                $membre->setSecondaryGroup($groupeSecondaire);
                $membre->setSecret(false);
                $entityManager->persist($membre);
                $entityManager->flush();
                $groupeSecondaire->addMembre($membre);
            }

            $entityManager->persist($topic);
            $entityManager->persist($groupeSecondaire);
            $entityManager->flush();

            $this->addFlash('success', 'Le groupe secondaire a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('groupeSecondaire.admin.list', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('groupeSecondaire.admin.add', [], 303);
            }
        }

        return $this->render('groupeSecondaire/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour du matériel necessaire à un groupe secondaire.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/groupeSecondaire/{groupeSecondaire}/materielUpdate', name: 'groupeSecondaire.materiel.update')]
    public function materielUpdateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
    ): RedirectResponse|Response {
        $form = $this->createForm(GroupeSecondaireMaterielForm::class, $groupeSecondaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeSecondaire = $form->getData();
            $entityManager->persist($groupeSecondaire);
            $entityManager->flush();
            $this->addFlash('success', 'Le groupe secondaire a été mis à jour.');

            return $this->redirectToRoute(
                'groupeSecondaire.admin.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303
            );
        }

        return $this->render('groupeSecondaire/materiel.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Impression de l'enveloppe du groupe secondaire.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/groupeSecondaire/{groupeSecondaire}/print', name: 'groupeSecondaire.materiel.print')]
    public function materielPrintAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
    ): Response {
        return $this->render('groupeSecondaire/print.twig', [
            'groupeSecondaire' => $groupeSecondaire,
        ]);
    }

    /**
     * Impression de toutes les enveloppes groupe secondaire.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/groupeSecondaire/printAll', name: 'groupeSecondaire.materiel.printAll')]
    public function materielPrintAllAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $groupeSecondaires = $entityManager->getRepository(SecondaryGroup::class)->findAll();

        return $this->render('groupeSecondaire/printAll.twig', [
            'groupeSecondaires' => $groupeSecondaires,
        ]);
    }

    /**
     * Met à jour un de groupe secondaire.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/groupeSecondaire/{groupeSecondaire}/update', name: 'groupeSecondaire.admin.update')]
    public function adminUpdateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
    ): RedirectResponse|Response {
        $this->canManageGroup($groupeSecondaire);

        $form = $this->createForm(GroupeSecondaireForm::class, $groupeSecondaire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

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
                    if ($postulant->getPersonnage() == $personnage) {
                        $entityManager->remove($postulant);
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

            return $this->redirectToRoute('groupeSecondaire.admin.list');
        }

        return $this->render('groupeSecondaire/update.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'form' => $form->createView(),
        ]);
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
        $gnActif = GroupeManager::getGnActif($this->entityManager);
        if (empty($extraParameters['isAdmin'])) {
            try {
                $this->canManageGroup($groupeSecondaire);
                $extraParameters['isAdmin'] = true;
            } catch (\Exception $e) {
                $extraParameters['isAdmin'] = false;
            }
        }
        $result = [
            'groupeSecondaire' => $groupeSecondaire,
            'gn' => $gnActif,
        ];

        if (null === $extraParameters) {
            return $result;
        }

        return [...$result, ...$extraParameters];
    }

    /**
     * Détail d'un groupe secondaire (pour les orgas).
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/groupeSecondaire/{groupeSecondaire}/detail', name: 'groupeSecondaire.admin.detail')]
    public function adminDetailAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
    ): Response {
        $this->canSeeGroup($groupeSecondaire);

        // Todo send to twig with an isAdmin
        try {
            $this->canManageGroup($groupeSecondaire);
            $canLead = true;
        } catch (\Exception $e) {
            $canLead = false;
        }

        return $this->render(
            'groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($groupeSecondaire,  ['isAdmin' => $canLead])
        );
    }

    /**
     * Ajoute un nouveau membre au groupe secondaire.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/groupeSecondaire/{groupeSecondaire}/addMember', name: 'groupeSecondaire.admin.newMembre')]
    public function adminNewMembreAction(
        Request $request,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
    ): RedirectResponse|Response {
        $this->canManageGroup($groupeSecondaire);
        $form = $this->createForm(GroupeSecondaireNewMembreForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form['personnage']->getData();
            // $personnage = $data['personnage'];

            $membre = new Membre();

            if ($groupeSecondaire->isMembre($personnage)) {
                $this->addFlash('warning', 'le personnage est déjà membre du groupe secondaire.');

                return $this->redirectToRoute(
                    'groupeSecondaire.admin.detail',
                    ['groupeSecondaire' => $groupeSecondaire->getId()],
                    303
                );
            }

            $membre->setPersonnage($personnage);
            $membre->setSecondaryGroup($groupeSecondaire);
            $membre->setSecret(false);

            $this->entityManager->persist($membre);
            $this->entityManager->flush();

            $this->addFlash('success', 'le personnage a été ajouté au groupe secondaire.');

            return $this->redirectToRoute(
                'groupeSecondaire.admin.detail',
                ['groupeSecondaire' => $groupeSecondaire->getId()],
                303
            );
        }

        return $this->render(
            'groupeSecondaire/newMembre.twig',
            $this->buildContextDetailTwig($groupeSecondaire, ['form' => $form->createView(), 'isAdmin' => true])
        );
    }

    /**
     * Retire un postulant du groupe.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/groupeSecondaire/{groupeSecondaire}/removePostulant', name: 'groupeSecondaire.removePostulant')]
    public function adminRemovePostulantAction(
        Request $request,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
    ): Response {
        $this->canManageGroup($groupeSecondaire);

        $postulant = $request->get('postulant');

        $this->entityManager->remove($postulant);
        $this->entityManager->flush();

        $this->addFlash('success', 'la candidature a été supprimée.');

        return $this->render(
            'groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($groupeSecondaire, ['isAdmin' => true])
        );
    }

    /**
     * Accepte un postulant dans le groupe.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/groupeSecondaire/{groupeSecondaire}/acceptPostulant', name: 'groupeSecondaire.acceptPostulant')]
    public function adminAcceptPostulantAction(
        Request $request,
        #[MapEntity] SecondaryGroup $groupeSecondaire,
    ): Response {
        $this->canManageGroup($groupeSecondaire);

        $postulant = $request->get('postulant');
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

            // $app['User.mailer']->sendGroupeSecondaireAcceptMessage($personnage->getUser(), $groupeSecondaire);

            $this->addFlash('success', 'la candidature a été accepté.');
        }

        return $this->render(
            'groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($groupeSecondaire, ['isAdmin' => true])
        );
    }

    /**
     * Retire un membre du groupe.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/groupeSecondaire/{groupeSecondaire}/removeMember/{membre}', name: 'groupeSecondaire.admin.member.remove')]
    public function adminRemoveMembreAction(
        #[MapEntity] SecondaryGroup $groupeSecondaire,
        #[MapEntity] Membre $membre,
    ): Response {
        $this->canManageGroup($groupeSecondaire, $membre);

        $this->entityManager->remove($membre);
        $this->entityManager->flush();

        $this->addFlash('success', 'le membre a été retiré.');

        return $this->render(
            'groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($groupeSecondaire, ['isAdmin' => true])
        );
    }

    /**
     * Retirer le droit de lire les secrets à un utilisateur.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/groupeSecondaire/{groupeSecondaire}/secretOff/{membre}', name: 'groupeSecondaire.admin.secret.off')]
    public function adminSecretOffAction(
        #[MapEntity] SecondaryGroup $groupeSecondaire,
        #[MapEntity] Membre $membre,
    ): Response {
        $this->canManageGroup($groupeSecondaire, $membre);

        $membre->setSecret(false);
        $this->entityManager->persist($membre);
        $this->entityManager->flush();

        return $this->render(
            'groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($groupeSecondaire, ['isAdmin' => true])
        );
    }

    /**
     * Active le droit de lire les secrets à un utilisateur.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/groupeSecondaire/{groupeSecondaire}/secretOn/{membre}', name: 'groupeSecondaire.admin.secret.on')]
    public function adminSecretOnAction(
        #[MapEntity] SecondaryGroup $groupeSecondaire,
        #[MapEntity] Membre $membre,
    ): Response {
        $this->canManageGroup($groupeSecondaire, $membre);

        $membre->setSecret(true);
        $this->entityManager->persist($membre);
        $this->entityManager->flush();

        return $this->render(
            'groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($groupeSecondaire, ['isAdmin' => true])
        );
    }

    protected function canManageGroup(?SecondaryGroup $secondaryGroup = null, ?Membre $membre = null): void
    {
        if ($this->isGranted('ROLE_SCENARISTE')) {
            return;
        }

        if ($membre?->getPersonnage()?->getId() === $secondaryGroup?->getPersonnage()?->getId()) {
            return;
        }

        if (!$membre && $secondaryGroup) {
            /** @var SecondaryGroupRepository $secondaryGroupRepository */
            $secondaryGroupRepository = $this->entityManager->getRepository(SecondaryGroup::class);
            if ($secondaryGroupRepository->userIsGroupLeader($this->getUser(), $secondaryGroup)) {
                return;
            }
        }

        throw new AccessDeniedException();
    }

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
}
