<?php

namespace App\Controller;

use App\Entity\Background;
use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\GroupeAllie;
use App\Entity\GroupeEnemy;
use App\Entity\GroupeGn;
use App\Entity\GroupeHasIngredient;
use App\Entity\GroupeHasRessource;
use App\Entity\Ingredient;
use App\Entity\Participant;
use App\Entity\Ressource;
use App\Entity\Territoire;
use App\Enum\Role;
use App\Form\BackgroundForm;
use App\Form\Groupe\GroupeCompositionForm;
use App\Form\Groupe\GroupeDescriptionForm;
use App\Form\Groupe\GroupeDocumentForm;
use App\Form\Groupe\GroupeEnvelopeForm;
use App\Form\Groupe\GroupeForm;
use App\Form\Groupe\GroupeIngredientForm;
use App\Form\Groupe\GroupeItemForm;
use App\Form\Groupe\GroupeRessourceForm;
use App\Form\Groupe\GroupeRichesseForm;
use App\Form\Groupe\GroupeScenaristeForm;
use App\Manager\GroupeManager;
use App\Repository\GroupeRepository;
use App\Repository\RessourceRepository;
use App\Repository\TerritoireRepository;
use App\Security\MultiRolesExpression;
use App\Service\PagerService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[IsGranted('ROLE_USER')]
#[Route('/groupe', name: 'groupe.')]
class GroupeController extends AbstractController
{
    /**
     * Ajout d'un groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/add', name: 'add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Groupe(),

            GroupeForm::class,
        );
    }

    protected function handleCreateOrUpdate(
        Request   $request,
                  $entity,
        string    $formClass,
        array     $breadcrumb = [],
        array     $routes = [],
        array     $msg = [],
        ?callable $entityCallback = null,
    ): RedirectResponse|Response
    {
        $routes['root'] = 'groupe.';

        return parent::handleCreateOrUpdate(
            request: $request,
            entity: $entity,
            formClass: $formClass,
            breadcrumb: $breadcrumb,
            routes: $routes,
            msg: [
                ...$msg,
                'entity' => $this->translator->trans('groupe'),
                'entity_added' => $this->translator->trans('Le groupe a été ajoutée'),
                'entity_updated' => $this->translator->trans('Le groupe a été mise à jour'),
                'entity_deleted' => $this->translator->trans('Le groupe a été supprimé'),
                'entity_list' => $this->translator->trans('Liste des groupes'),
                'title_add' => $this->translator->trans('Ajouter un groupe'),
                'title_update' => $this->translator->trans('Modifier un groupe'),
            ],
            entityCallback: $entityCallback,
        );
    }

    /**
     * Ajout d'un background à un groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    public function addBackgroundAction(
        Request $request,
    ): RedirectResponse|Response
    {
        $id = $request->get('index');
        $groupe = $this->entityManager->find(Groupe::class, $id);

        $background = new Background();
        $background->setGroupe($groupe);

        $form = $this->createForm(BackgroundForm::class, $background)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', 'Le background du groupe a été créé');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()], 303);
        }

        return $this->render('groupe/background/add.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un document dans le matériel du groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/documents', name: 'documents')]
    public function adminDocumentAction(
        Request             $request,

        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response
    {
        if ($r = $this->checkGroupeLocked($groupe)) {
            return $r;
        }

        $form = $this->createForm(GroupeDocumentForm::class, $groupe)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupe = $form->getData();
            $this->entityManager->persist($groupe);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le document a été ajouté au groupe.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/documents.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie les ingredients du groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/ingredients', name: 'ingredients')]
    public function adminIngredientAction(
        Request             $request,

        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response
    {
        if ($r = $this->checkGroupeLocked($groupe)) {
            return $r;
        }
        $originalGroupeHasIngredients = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets GroupeHasIngredient du groupe
         */
        foreach ($groupe->getGroupeHasIngredients() as $groupeHasIngredient) {
            $originalGroupeHasIngredients->add($groupeHasIngredient);
        }

        $form = $this->createForm(GroupeIngredientForm::class, $groupe)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupe = $form->getData();

            /*
             * Pour tous les ingredients
             */
            foreach ($groupe->getGroupeHasIngredients() as $groupeHasIngredient) {
                $groupeHasIngredient->setGroupe($groupe);
            }

            /*
             * supprime la relation entre groupeHasIngredient et le groupe
             */
            foreach ($originalGroupeHasIngredients as $groupeHasIngredient) {
                if (false === $groupe->getGroupeHasIngredients()->contains($groupeHasIngredient)) {
                    $this->entityManager->remove($groupeHasIngredient);
                }
            }

            $random = $form['random']->getData();

            /*
             *  Gestion des ressources communes alloués au hasard
             */
            if ($random && $random > 0) {
                $ingredients = $this->entityManager->getRepository(Ingredient::class)->findAll();
                shuffle($ingredients);
                $needs = new ArrayCollection(array_slice($ingredients, 0, $random));

                foreach ($needs as $ingredient) {
                    $ghi = new GroupeHasIngredient();
                    $ghi->setIngredient($ingredient);
                    $ghi->setQuantite(1);
                    $ghi->setGroupe($groupe);
                    $this->entityManager->persist($ghi);
                }
            }

            $this->entityManager->persist($groupe);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre groupe a été sauvegardé.');

            return $this->redirectToRoute('groupe.detail.tab', ['groupe' => $groupe->getId(), 'tab' => 'enveloppe']);
        }

        return $this->render('groupe/ingredient.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gestion des objets du groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/items', name: 'items')]
    public function adminItemAction(
        Request             $request,

        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response
    {
        if ($r = $this->checkGroupeLocked($groupe)) {
            return $r;
        }

        $form = $this->createForm(GroupeItemForm::class, $groupe)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupe = $form->getData();
            $this->entityManager->persist($groupe);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'objet a été ajouté au groupe.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/items.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajouter un participant dans un groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    // TODO
    public function adminParticipantAddAction(
        Request $request,
    ): RedirectResponse|Response
    {
        $groupe = $request->get('groupe');

        $repo = $this->entityManager->getRepository(Participant::class);

        // trouve tous les participants n'étant pas dans un groupe
        $participants = $repo->findAllByGroupeNull();

        // creation du formulaire
        $form = $this->createForm()
            ->add('participant', 'entity', [
                'label' => 'Choisissez le nouveau membre du groupe',
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'choice_label' => 'UserIdentity',
                'class' => Participant::class,
                'choices' => $participants,
            ])
            ->add('submit', SubmitType::class, ['label' => 'Ajouter']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $participant = $data['participant'];

            $groupe->addParticipant($participant);
            $participant->setGroupe($groupe);
            // le personnage du joueur doit aussi changer de groupe
            if ($participant->getPersonnage()) {
                $personnage = $participant->getPersonnage();
                $personnage->setGroupe($groupe);
                $this->entityManager->persist($personnage);
            }

            $this->entityManager->persist($groupe);
            $this->entityManager->persist($participant);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le participant a été ajouté au groupe.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/addParticipant.twig', [
            'groupe' => $groupe,
            'participants' => $participants,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retirer un participant du groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    // TODO
    public function adminParticipantRemoveAction(
        Request $request,
    ): RedirectResponse|Response
    {
        $participantId = $request->get('participant');
        $groupe = $request->get('groupe');

        $participant = $this->entityManager->find(Participant::class, $participantId);

        if ($request->isMethod('POST')) {
            $personnage = $participant->getPersonnage();
            if ($personnage) {
                if ($personnage->getGroupe() == $groupe) {
                    $personnage->removeGroupe($groupe);
                }

                $this->entityManager->persist($personnage);
            }

            $participant->removeGroupe($groupe);
            $this->entityManager->persist($participant);
            $this->entityManager->persist($groupe);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le participant a été retiré du groupe.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/removeParticipant.twig', [
            'groupe' => $groupe,
            'participant' => $participant,
        ]);
    }

    /**
     * AModifie la richesse du groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/richesse', name: 'richesse')]
    public function adminRichesseAction(
        Request             $request,

        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response
    {
        if ($r = $this->checkGroupeLocked($groupe)) {
            return $r;
        }

        $form = $this->createForm(GroupeRichesseForm::class, $groupe)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupe = $form->getData();
            $this->entityManager->persist($groupe);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre groupe a été sauvegardé.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/richesse.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * rendre disponible un groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    // TODO
    public function availableAction(
        Request $request,

        Groupe  $groupe,
    ): RedirectResponse
    {
        $groupe->setFree(true);
        $this->entityManager->persist($groupe);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le groupe est maintenant disponible. Il pourra être réservé par un joueur');

        return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
    }

    /**
     * Modifier la composition du groupe.
     */
    #[Route('/{groupe}/composition', name: 'composition')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE), message: 'You are not allowed to access to this.')]
    public function compositionAction(
        Request             $request,

        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response
    {
        if ($r = $this->checkGroupeLocked($groupe)) {
            return $r;
        }

        $originalGroupeClasses = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets GroupeClasse du groupe
         */
        foreach ($groupe->getGroupeClasses() as $groupeClasse) {
            $originalGroupeClasses->add($groupeClasse);
        }

        $form = $this->createForm(GroupeCompositionForm::class, $groupe)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupe = $form->getData();

            /*
             * Pour toutes les classes du groupe
             */
            foreach ($groupe->getGroupeClasses() as $groupeClasses) {
                $groupeClasses->setGroupe($groupe);
            }

            /*
             *  supprime la relation entre le groupeClasse et le groupe
             */
            foreach ($originalGroupeClasses as $groupeClasse) {
                if (false == $groupe->getGroupeClasses()->contains($groupeClasse)) {
                    $this->entityManager->remove($groupeClasse);
                }
            }

            $this->entityManager->persist($groupe);
            $this->entityManager->flush();

            $this->addFlash('success', 'La composition du groupe a été sauvegardé.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/composition.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/delete', name: 'delete', requirements: ['groupe' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    public function deleteAction(#[MapEntity] Groupe $groupe): RedirectResponse|Response
    {
        return $this->genericDelete(
            $groupe,
            'Supprimer un groupe',
            'Le groupe a été supprimé',
            'groupe.list',
            [
                ['route' => $this->generateUrl('groupe.list'), 'name' => 'Liste des groupes'],
                [
                    'route' => $this->generateUrl('groupe.detail', ['groupe' => $groupe->getId()]),
                    'groupe' => $groupe->getId(),
                    'name' => $groupe->getNom(),
                    'content' => $groupe->getDescription(),
                ],
                ['name' => 'Supprimer un groupe'],
            ],
        );
    }

    /**
     * Modification de la description du groupe.
     */
    #[Route('/{groupe}/description', name: 'description')]
    #[IsGranted('ROLE_SCENARISTE')]
    public function descriptionAction(
        Request             $request,

        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response
    {
        $form = $this->createForm(GroupeDescriptionForm::class, $groupe)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupe = $form->getData();
            $this->entityManager->persist($groupe);
            $this->entityManager->flush();

            $this->addFlash('success', 'La description du groupe a été sauvegardé.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/description.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{groupe}/description-membres', name: 'description.membres')]
    #[IsGranted('ROLE_USER')]
    public function descriptionMembresAction(
        Request             $request,
        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response
    {
        $this->checkHasAccess(
            [Role::ORGA, Role::SCENARISTE],
            fn() => $this->personnageService->isUserIsGroupeResponsable($groupe),
        );

        $form = $this->createForm(GroupeDescriptionForm::class, $groupe)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupe = $form->getData();
            $this->entityManager->persist($groupe);
            $this->entityManager->flush();

            $this->addFlash('success', 'La description du groupe a été sauvegardé.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/description.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche le détail d'un groupe.
     */
    #[Route('/{groupe}', name: 'detail')]
    #[Route('/{groupe}/detail/{tab}', name: 'detail.tab')]
    #[Route('/{groupe}/gn/{gn}', name: 'detail.gn')]
    #[Route('/{groupe}/gn/{gn}/{groupeGn}', name: 'groupeGn')]
    #[Route('/{groupe}/detail/{tab}/gn/{gn}/groupeGn/{groupeGn}', name: 'detail.groupeGn')]
    public function detailAction(
        #[MapEntity] ?Groupe   $groupe,
        #[MapEntity] ?Gn       $gn = null,
        #[MapEntity] ?GroupeGn $groupeGn = null,
        string                 $tab = 'detail',
    ): RedirectResponse|Response
    {
        /*
         * Si le groupe existe, on affiche son détail
         * Sinon on envoie une erreur
         */
        if (!$groupe) {
            $this->addFlash('error', 'Le groupe n\'a pas été trouvé.');

            return $this->redirectToRoute('groupe.list');
        }

        $this->hasAccess($groupe, $gn, $groupeGn, [Role::WARGAME]);

        if (('domaine' === $tab) && $this->getPersonnage() && $this->getPersonnage()->getId() === $groupeGn?->getSuzerain(false)?->getId()) {
            $this->setCan(self::CAN_WRITE, true);
        }

        return $this->render(
            'groupe/detail.twig',
            [
                'groupe' => $groupe,
                'gn' => $gn,
                'groupeGn' => $groupeGn,
                'tab' => $tab,
            ],
        );
    }

    protected function hasAccess(Groupe $groupe, ?Gn $gn = null, ?GroupeGn $groupeGn = null, array $roles = []): void
    {
        if ($isResponsable = $this->groupeService->isUserIsGroupeResponsable($groupe)) {
            $isMembre = true;
        } else {
            $isMembre = $this->groupeService->isUserIsGroupeMember($groupe);
        }

        /*
        $groupeGn ??= $groupe->getGroupeGns()->last();
        if (!$groupeGn && $gn) {
            foreach ($groupe->getGroupeGns() as $grpGn) {
                if ($grpGn?->getGn()?->getId() === $gn->getId()) {
                    $groupeGn = $grpGn;
                }
            }
        }*/

        // TODO check if membre can read secret
        // TODO limit WARGAME to .. WARGAME ... ADD a GROUPE_ROLE
        $this->setCan(self::IS_ADMIN, $this->isGranted(Role::WARGAME->value));
        $this->setCan(self::CAN_MANAGE, $isResponsable);
        $this->setCan(self::CAN_READ_PRIVATE, $isResponsable || $isMembre);
        $this->setCan(self::CAN_READ_SECRET, $isResponsable);
        $this->setCan(self::CAN_WRITE, $isResponsable);
        $this->setCan(self::CAN_READ, $isMembre);

        $this->checkHasAccess(
            $roles,
            fn() => $this->can(self::CAN_READ),
        );
    }

    /**
     * Visualisation des liens entre groupes.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/diplomatie', name: 'diplomatie')]
    public function diplomatieAction(Request $request): Response
    {
        $repo = $this->entityManager->getRepository(Groupe::class);
        $groupes = $repo->findBy(['pj' => true], ['nom' => 'ASC']);

        return $this->render('diplomatie.twig', [
            'groupes' => $groupes,
        ]);
    }

    /**
     * Impression des liens entre groupes.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/diplomatie/print', name: 'diplomatie.print')]
    public function diplomatiePrintAction(Request $request): Response
    {
        $repo = $this->entityManager->getRepository(GroupeAllie::class);
        $alliances = $repo->findByAlliances();
        $demandeAlliances = $repo->findByDemandeAlliances();

        $repo = $this->entityManager->getRepository(GroupeEnemy::class);
        $guerres = $repo->findByWar();
        $demandePaix = $repo->findByRequestPeace();

        return $this->render('diplomatiePrint.twig', [
            'alliances' => $alliances,
            'demandeAlliances' => $demandeAlliances,
            'guerres' => $guerres,
            'demandePaix' => $demandePaix,
        ]);
    }

    /**
     * Gestion de l'enveloppe de groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/envelope', name: 'envelope')]
    public function envelopeAction(
        Request             $request,

        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response
    {
        $form = $this->createForm(GroupeEnvelopeForm::class, $groupe)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupe = $form->getData();
            $this->entityManager->persist($groupe);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le groupe a été sauvegardé.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/envelope.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Exportation de la liste des groupes au format CSV.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    public function exportAction(Request $request): void
    {
        $repo = $this->entityManager->getRepository(Groupe::class);
        $groupes = $repo->findAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_groupe_' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv(
            $output,
            [
                'nom',
                'description',
                'code',
                'creation_date',
            ],
            ',',
        );

        foreach ($groupes as $groupe) {
            fputcsv($output, $groupe->getExportValue(), ',');
        }

        fclose($output);
        exit;
    }

    /**
     * Liste des groupes.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('', name: 'list')]
    public function listAction(
        Request          $request,
        PagerService     $pagerService,
        GroupeRepository $groupeRepository,
    ): Response
    {
        $pagerService->setRequest($request)->setRepository($groupeRepository);

        return $this->render('groupe/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $groupeRepository->searchPaginated($pagerService),
        ]);
        /* OLD
        $order_by = $request->get('order_by') ?: 'numero';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int)($request->get('limit') ?: 50);
        $page = (int)($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $type = null;
        $value = null;

        $form = $this->createForm(GroupeFindForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['search'];
        }

        $repo = $this->entityManager->getRepository(Groupe::class);

        $groupes = $repo->findList(
            $type,
            $value,
            $limit,
            $offset,
            ['by' => $order_by, 'dir' => $order_dir]
        );

        $paginator = $repo->findPaginatedQuery(
            $groupes,
            $this->getRequestLimit(),
            $this->getRequestPage()
        );

        return $this->render('groupe/list.twig', [
            'form' => $form->createView(),
            'paginator' => $paginator,
        ]);
        */
    }

    /**
     * vérouillage d'un groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/lock', name: 'lock')]
    public function lockAction(
        #[MapEntity] Groupe $groupe,
    ): RedirectResponse
    {
        $groupe->setLock(true);
        $this->entityManager->persist($groupe);
        $this->entityManager->flush();

        $this->addFlash(
            'success',
            'Le groupe est verrouillé. Cela bloque la création et la modification des personnages membres de ce groupe',
        );

        return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
    }

    /**
     * Lier un pays à un groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/pays', name: 'pays')]
    public function paysAction(
        Request             $request,

        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response
    {
        if ($r = $this->checkGroupeLocked($groupe)) {
            return $r;
        }

        $form = $this->createFormBuilder()
            ->add('territoire', EntityType::class, [
                'required' => true,
                'class' => Territoire::class,
                'choice_label' => 'nomComplet',
                'label' => 'choisissez le territoire',
                'expanded' => true,
                'query_builder' => static function (TerritoireRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->andWhere($qb->expr()->isNull('t.territoire'));
                    $qb->orderBy('t.nom', 'ASC');

                    return $qb;
                },
            ])
            ->add('add', SubmitType::class, ['label' => 'Ajouter le territoire'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $territoire = $data['territoire'];

            $groupe->setTerritoire($territoire);

            $this->entityManager->persist($groupe);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le groupe est lié au pays');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/pays.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Imprimmer toutes les enveloppes de tous les groupes.
     */

    /**
     * Modification du nombre de place disponibles dans un groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    // TODO
    public function placeAction(
        Request $request,
    ): RedirectResponse|Response
    {
        $id = $request->get('index');
        $groupe = $this->entityManager->find(Groupe::class, $id);

        if ('POST' == $request->getMethod()) {
            $newPlaces = $request->get('place');

            /*
             * Met à jour uniquement si la valeur à changé
             */
            if ($groupe->getClasseOpen() != $newPlaces) {
                $groupe->setClasseOpen($newPlaces);
                $this->entityManager->persist($groupe);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Le nombre de place disponible a été mis à jour');

            return $this->redirectToRoute('groupe.list', [], 303);
        }

        return $this->render('groupe/place.twig', [
            'groupe' => $groupe,
        ]);
    }

    /** @deprecated:  see GnController* */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/print', name: 'print')]
    public function printAllAction(Request $request): Response
    {
        $gn = GroupeManager::getGnActif($this->entityManager);
        $groupeGns = $gn->getGroupeGns();

        $ressourceRares = new ArrayCollection($this->entityManager->getRepository(Ressource::class)->findRare());
        $ressourceCommunes = new ArrayCollection($this->entityManager->getRepository(Ressource::class)->findCommun());

        $groupes = new ArrayCollection();
        foreach ($groupeGns as $groupeGn) {
            $groupe = $groupeGn->getGroupe();
            $quete = GroupeManager::generateQuete($groupe, $ressourceCommunes, $ressourceRares);
            $groupes[] = [
                'groupe' => $groupe,
                'quete' => $quete,
            ];
        }

        return $this->render('groupe/printAll.twig', [
            'groupes' => $groupes,
        ]);
    }

    /**
     * Impression background pour les personnages du groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/print/background', name: 'print.background')]
    public function printBackgroundAction(
        #[MapEntity] Groupe $groupe,
    ): Response
    {
        return $this->render('groupe/printBackground.twig', [
            'groupe' => $groupe,
        ]);
    }

    /**
     * Impression matériel pour les personnages du groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/print/materiel', name: 'print.materiel')]
    public function printMaterielAction(#[MapEntity] Groupe $groupe): Response
    {
        // recherche les personnages du prochain GN membre du groupe
        $session = $groupe->getNextSession();
        $participants = $session->getParticipants();

        return $this->render('groupe/printMateriel.twig', [
            'groupe' => $groupe,
            'participants' => $participants,
        ]);
    }

    /**
     * Impression matériel pour le groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/print/materiel/groupe', name: 'print.materiel.groupe')]
    public function printMaterielGroupeAction(
        #[MapEntity] Groupe $groupe,
        RessourceRepository $ressourceRepository,
    ): Response
    {
        // recherche les personnages du prochain GN membre du groupe
        $session = $groupe->getNextSession();
        $participants = $session?->getParticipants();

        $ressourceRares = new ArrayCollection($ressourceRepository->findRare());
        $ressourceCommunes = new ArrayCollection($ressourceRepository->findCommun());
        $quete = GroupeManager::generateQuete($groupe, $ressourceCommunes, $ressourceRares);

        return $this->render('groupe/printMaterielGroupe.twig', [
            'groupe' => $groupe,
            'participants' => $participants,
            'quete' => $quete,
            'session' => $session,
        ]);
    }

    /**
     * Impression fiche de perso pour le groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/print/perso', name: 'print.perso')]
    public function printPersoAction(
        Request             $request,

        #[MapEntity] Groupe $groupe,
    ): Response
    {
        // recherche les personnages du prochains GN membre du groupe
        $session = $groupe->getNextSession();
        $participants = $session->getParticipants();
        $quetes = new ArrayCollection();

        $ressourceRares = new ArrayCollection($this->entityManager->getRepository(Ressource::class)->findRare());
        $ressourceCommunes = new ArrayCollection($this->entityManager->getRepository(Ressource::class)->findCommun());

        foreach ($participants as $participant) {
            $personnage = $participant->getPersonnage();
            if ($personnage && $personnage->hasCompetence('Commerce')) {
                $niveau = $personnage->getCompetenceNiveau('Commerce');
                if ($niveau >= 2) {
                    $quetes[] = [
                        'quete' => GroupeManager::generateQuete($groupe, $ressourceCommunes, $ressourceRares),
                        'personnage' => $personnage,
                    ];
                }
            }
        }

        return $this->render(
            'groupe/printPerso.twig',
            [
                'groupe' => $groupe,
                'participants' => $participants,
                'quetes' => $quetes,
            ],
        );
    }

    /**
     * Générateur de quêtes commerciales.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/quete', name: 'quete')]
    public function queteAction(
        #[MapEntity] Groupe $groupe,
    ): Response
    {
        $ressourceRares = new ArrayCollection($this->entityManager->getRepository(Ressource::class)->findRare());
        $ressourceCommunes = new ArrayCollection($this->entityManager->getRepository(Ressource::class)->findCommun());
        $quete = GroupeManager::generateQuete($groupe, $ressourceCommunes, $ressourceRares);

        return $this->render('groupe/quete.twig', [
            'groupe' => $groupe,
            'needs' => $quete['needs'],
            'valeur' => $quete['valeur'],
            'cible' => $quete['cible'],
            'recompenses' => $quete['recompenses'],
        ]);
    }

    /**
     * fourni le tableau de quête pour tous les groupes.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/quetes', name: 'quetes')]
    public function quetesAction(
        Request             $request,

        SerializerInterface $serializer,
    )
    {
        $repo = $this->entityManager->getRepository(Groupe::class);
        $groupes = $repo->findAllOrderByNumero();
        $ressourceRares = new ArrayCollection($this->entityManager->getRepository(Ressource::class)->findRare());
        $ressourceCommunes = new ArrayCollection($this->entityManager->getRepository(Ressource::class)->findCommun());

        $quetes = new ArrayCollection();
        $stats = [];

        foreach ($groupes as $groupe) {
            $quete = GroupeManager::generateQuete($groupe, $ressourceCommunes, $ressourceRares);
            $quetes[] = [
                'quete' => $quete,
                'groupe' => $groupe,
            ];
            foreach ($quete['needs'] as $ressources) {
                if (isset($stats[$ressources->getLabel()])) {
                    ++$stats[$ressources->getLabel()];
                } else {
                    $stats[$ressources->getLabel()] = 1;
                }
            }
        }

        if ($request->get('csv')) {
            $header = [
                'nom',
                'pays',
                'res 1',
                'res 2',
                'res 3',
                'res 4',
                'res 5',
                'res 6',
                'res 7',
                'Départ',
                'Arrivée',
                'recompense 1',
                'recompense 2',
                'recompense 3',
                'recompense 4',
                'recompense 5',
                'description',
            ];

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename=eveoniris_quetes_' . date('Ymd') . '.csv');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'wb');

            fputcsv($output, $header, ';');

            foreach ($quetes as $quete) {
                $line = [];
                $line[] = mb_convert_encoding(
                    '#' . $quete['groupe']->getNumero() . ' ' . $quete['groupe']->getNom(),
                    'ISO-8859-1',
                );
                $line[] = $quete['groupe']->getTerritoire() ? mb_convert_encoding(
                    (string)$quete['groupe']->getTerritoire()->getNom(),
                    'ISO-8859-1',
                ) : '';

                foreach ($quete['quete']['needs'] as $ressources) {
                    $line[] = mb_convert_encoding((string)$ressources->getLabel(), 'ISO-8859-1');
                }

                $line[] = '';
                $line[] = '';
                foreach ($quete['quete']['recompenses'] as $recompense) {
                    $line[] = mb_convert_encoding((string)$recompense, 'ISO-8859-1');
                }

                $line[] = '';
                fputcsv($output, $line, ';');
            }

            fclose($output);
            exit;
        }

        return $this->render('groupe/quetes.twig', [
            'quetes' => $quetes,
            'stats' => $stats,
        ]);
    }

    /**
     * Modifie les ressources du groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/ressources', name: 'ressources')]
    public function ressourceAction(
        Request             $request,
        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response
    {
        if ($r = $this->checkGroupeLocked($groupe)) {
            return $r;
        }

        $originalGroupeHasRessources = new ArrayCollection();

        /*
         * Crée un tableau contenant les objets GroupeHasRessource du groupe
         */
        foreach ($groupe->getGroupeHasRessources() as $groupeHasRessource) {
            $originalGroupeHasRessources->add($groupeHasRessource);
        }

        $form = $this->createForm(GroupeRessourceForm::class, $groupe)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupe = $form->getData();

            /*
             * Pour toutes les ressources du groupe
             */
            foreach ($groupe->getGroupeHasRessources() as $groupeHasRessource) {
                $groupeHasRessource->setGroupe($groupe);
            }

            /*
             *  supprime la relation entre groupeHasRessource et le groupe
             */
            foreach ($originalGroupeHasRessources as $groupeHasRessource) {
                if (false === $groupe->getGroupeHasRessources()->contains($groupeHasRessource)) {
                    $this->entityManager->remove($groupeHasRessource);
                }
            }

            $randomCommun = $form['randomCommun']->getData();

            /*
             *  Gestion des ressources communes alloués au hasard
             */
            if ($randomCommun && $randomCommun > 0) {
                $ressourceCommune = $this->entityManager->getRepository(Ressource::class)->findCommun();
                shuffle($ressourceCommune);
                $needs = new ArrayCollection(array_slice($ressourceCommune, 0, $randomCommun));

                foreach ($needs as $ressource) {
                    $ghr = new GroupeHasRessource();
                    $ghr->setRessource($ressource);
                    $ghr->setQuantite(1);
                    $ghr->setGroupe($groupe);
                    $this->entityManager->persist($ghr);
                }
            }

            $randomRare = $form['randomRare']->getData();

            /*
             *  Gestion des ressources rares alloués au hasard
             */
            if ($randomRare && $randomRare > 0) {
                $ressourceRare = $this->entityManager->getRepository(Ressource::class)->findRare();
                shuffle($ressourceRare);
                $needs = new ArrayCollection(array_slice($ressourceRare, 0, $randomRare));

                foreach ($needs as $ressource) {
                    $ghr = new GroupeHasRessource();
                    $ghr->setRessource($ressource);
                    $ghr->setQuantite(1);
                    $ghr->setGroupe($groupe);
                    $this->entityManager->persist($ghr);
                }
            }

            $this->entityManager->persist($groupe);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre groupe a été sauvegardé.');

            return $this->redirectToRoute('groupe.detail.tab', ['groupe' => $groupe->getId(), 'tab' => 'enveloppe']);
        }

        return $this->render('groupe/ressource.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gestion de la restauration d'un groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/restauration', name: 'restauration')]
    public function restaurationAction(
        Request $request,

        Groupe  $groupe,
    ): RedirectResponse|Response
    {
        $availableTaverns = GroupeManager::getAvailableTaverns();

        $formBuilder = $this->createForm();

        $participants = $groupe->getParticipants();

        $iterator = $participants->getIterator();
        $iterator->uasort(static function ($first, $second): int {
            if ($first === $second) {
                return 0;
            }

            return $first->getUser()->getEtatCivil()->getNom() < $second->getUser()->getEtatCivil()->getNom() ? -1 : 1;
        });
        $participants = new ArrayCollection(iterator_to_array($iterator));

        foreach ($participants as $participant) {
            $formBuilder->add($participant->getId(), 'choice', [
                'label' => $participant->getUser()->getEtatCivil()->getNom() . ' ' . $participant->getUser()->getEtatCivil()->getPrenom() . ' ' . $participant->getUser()->getEmail(),
                'choices' => $availableTaverns,
                'data' => $participant->getTavernId(),
                'multiple' => false,
                'expanded' => false,
            ]);
        }

        $form = $formBuilder->add('save', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $form->getData();
            foreach ($result as $joueur => $tavern) {
                $j = $this->entityManager->getRepository(Participant::class)->find($joueur);
                if ($j && $j->getTavernId() != $tavern) {
                    $j->setTavernId($tavern);
                    $this->entityManager->persist($j);
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Les options de restauration sont enregistrés.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/restauration.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Choix du scenariste.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/scenariste', name: 'scenariste')]
    public function scenaristeAction(
        Request             $request,

        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response
    {
        $form = $this->createForm(GroupeScenaristeForm::class, $groupe)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupe = $form->getData();
            $this->entityManager->persist($groupe);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le groupe a été sauvegardé.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/scenariste.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Recherche d'un groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    public function searchAction(Request $request): RedirectResponse|Response
    {
        $form = $this->createForm(FindGroupeForm::class, [])
            ->add('submit', SubmitType::class, ['label' => 'Rechercher']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $type = $data['type'];
            $search = $data['search'];

            $repo = $this->entityManager->getRepository(Groupe::class);

            $groupes = null;

            switch ($type) {
                case 'label':
                    $groupes = $repo->findByName($search);
                    break;
                case 'numero':
                    $groupes = $repo->findByNumero($search);
                    break;
            }

            if (null != $joueurs) {
                if (1 == count($joueurs)) {
                    $this->addFlash('success', 'Le joueur a été trouvé.');

                    return $this->redirectToRoute('joueur.detail', ['index' => $joueurs[0]]);
                } else {
                    $this->addFlash('success', 'Il y a plusieurs résultats à votre recherche.');

                    return $this->render('joueur/search_result.twig', [
                        'joueurs' => $joueurs,
                    ]);
                }
            }

            $this->addFlash('error', 'Désolé, le joueur n\'a pas été trouvé.');
        }

        return $this->render('joueur/search.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajout d'un territoire sous le controle du groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/territoire/add', name: 'territoire.add')]
    public function territoireAddAction(
        Request             $request,

        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response
    {
        if ($r = $this->checkGroupeLocked($groupe)) {
            return $r;
        }

        $form = $this->createFormBuilder()
            ->add('territoire', EntityType::class, [
                'required' => true,
                'class' => Territoire::class,
                'choice_label' => 'nomComplet',
                'label' => 'choisissez le territoire',
                'expanded' => true,
                'query_builder' => static function (TerritoireRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->orderBy('t.nom', 'ASC');

                    return $qb;
                },
            ])
            ->add('add', SubmitType::class, ['label' => 'Ajouter le territoire'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $territoire = $data['territoire'];

            $territoire->setGroupe($groupe);

            $this->entityManager->persist($territoire);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le territoire est contrôlé par le groupe');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/addTerritoire.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retirer un territoire du controle du groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/territoire/{territoire}/remove', name: 'territoire.remove')]
    public function territoireRemoveAction(
        Request    $request,
        Groupe     $groupe,
        Territoire $territoire,
    ): RedirectResponse|Response
    {
        if ($r = $this->checkGroupeLocked($groupe)) {
            return $r;
        }

        $form = $this->createFormBuilder()
            ->add('remove', SubmitType::class, ['label' => 'Retirer le territoire'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire->setGroupeNull();

            $this->entityManager->persist($territoire);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le territoire n\'est plus controlé par le groupe');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        return $this->render('groupe/removeTerritoire.twig', [
            'groupe' => $groupe,
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * devérouillage d'un groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/{groupe}/unlock', name: 'unlock')]
    public function unlockAction(#[MapEntity] Groupe $groupe): RedirectResponse
    {
        $tempLock = true; // only admin can unlock ?
        if ($tempLock && !$this->isGranted(Role::ADMIN->value)) {
            $this->addFlash('error', "Il n'est plus possible de déverrouiller les groupes");

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
        }

        $groupe->setLock(false);
        $this->entityManager->persist($groupe);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le groupe est dévérouillé');

        return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
    }

    /**
     * rendre indisponible un groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    // TODO
    public function unvailableAction(
        Groupe $groupe,
    ): RedirectResponse
    {
        $groupe->setFree(false);
        $this->entityManager->persist($groupe);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le groupe est maintenant réservé. Il ne pourra plus être réservé par un joueur');

        return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
    }

    #[IsGranted('ROLE_SCENARISTE')]
    #[Route('/update/{groupe}', name: 'update')]
    public function updateAction(
        Request             $request,
        #[MapEntity] Groupe $groupe,
    ): RedirectResponse|Response
    {
        if ($r = $this->checkGroupeLocked($groupe)) {
            return $r;
        }

        $originalGroupeClasses = new ArrayCollection();
        $originalTerritoires = new ArrayCollection();

        /*
         * Créer un tableau contenant les objets GroupeClasse du groupe
         */
        foreach ($groupe->getGroupeClasses() as $groupeClasse) {
            $originalGroupeClasses->add($groupeClasse);
        }

        /*
         * Crée un tableau contenant les territoires que ce groupe posséde
         */
        foreach ($groupe->getTerritoires() as $territoire) {
            $originalTerritoires->add($territoire);
        }

        $form = $this->createForm(GroupeForm::class, $groupe)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupe = $form->getData();

            /*
             * Pour toutes les classes du groupe
             */
            foreach ($groupe->getGroupeClasses() as $groupeClasses) {
                $groupeClasses->setGroupe($groupe);
            }

            /*
             * supprime la relation entre le groupeClasse et le groupe
             */
            foreach ($originalGroupeClasses as $groupeClasse) {
                if (false == $groupe->getGroupeClasses()->contains($groupeClasse)) {
                    $this->entityManager->remove($groupeClasse);
                }
            }

            /*
             * Pour tous les territoires du groupe
             */
            foreach ($groupe->getTerritoires() as $territoire) {
                $territoire->setGroupe($groupe);
            }

            foreach ($originalTerritoires as $territoire) {
                if (false == $groupe->getTerritoires()->contains($territoire)) {
                    $territoire->setGroupe(null);
                }
            }

            /*
             * Si l'utilisateur a cliqué sur "update", on met à jour le groupe
             * Si l'utilisateur a cliqué sur "delete", on supprime le groupe
             */
            if ($form->get('update')->isClicked()) {
                $this->entityManager->persist($groupe);
                $this->entityManager->flush();
                $this->addFlash('success', 'Le groupe a été mis à jour.');

                return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()]);
            } elseif ($form->get('delete')->isClicked()) {
                // supprime le lien entre les personnages et le groupe
                foreach ($groupe->getPersonnages() as $personnage) {
                    $personnage->setGroupe(null);
                    $this->entityManager->persist($personnage);
                }
                // supprime le lien entre les participants et le groupe
                foreach ($groupe->getParticipants() as $participant) {
                    $participant->setGroupe(null);
                    $this->entityManager->persist($participant);
                }
                // supprime la relation entre le groupeClasse et le groupe
                foreach ($groupe->getGroupeClasses() as $groupeClasse) {
                    $this->entityManager->remove($groupeClasse);
                }
                // supprime la relation entre les territoires et le groupe
                foreach ($groupe->getTerritoires() as $territoire) {
                    $territoire->setGroupe(null);
                    $this->entityManager->persist($territoire);
                }
                // supprime la relation entre un background et le groupe
                foreach ($groupe->getBackgrounds() as $background) {
                    $this->entityManager->remove($background);
                }
                $this->entityManager->remove($groupe);
                $this->entityManager->flush();
                $this->addFlash('success', 'Le groupe a été supprimé.');

                return $this->redirectToRoute('groupe.list');
            }
        }

        return $this->render('groupe/update.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gestion des membres du groupe.
     */
    // TODO (no route ? Deprecated ?)
    /**
     * Mise à jour du background d'un groupe.
     */
    #[IsGranted('ROLE_SCENARISTE')]
    public function updateBackgroundAction(
        Request $request,
    ): RedirectResponse|Response
    {
        $id = $request->get('index');
        $groupe = $this->entityManager->find(Groupe::class, $id);

        $form = $this->createForm(BackgroundForm::class, $groupe->getBackground())
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();

            $this->entityManager->persist($background);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le background du groupe a été mis à jour');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $groupe->getId()], 303);
        }

        return $this->render('groupe/background/update.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    public function usersAction(Request $request, Groupe $groupe): Response
    {
        return $this->render('groupe/users.twig', [
            'groupe' => $groupe,
        ]);
    }
}
