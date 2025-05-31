<?php

namespace App\Controller;

use App\Entity\Age;
use App\Entity\Classe;
use App\Entity\Competence;
use App\Entity\Connaissance;
use App\Entity\Domaine;
use App\Entity\Espece;
use App\Entity\ExperienceGain;
use App\Entity\Gn;
use App\Entity\HeroismeHistory;
use App\Entity\Ingredient;
use App\Entity\Item;
use App\Entity\Langue;
use App\Entity\Level;
use App\Entity\LogAction;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\PersonnageApprentissage;
use App\Entity\PersonnageBackground;
use App\Entity\PersonnageChronologie;
use App\Entity\PersonnageHasToken;
use App\Entity\PersonnageIngredient;
use App\Entity\PersonnageLangues;
use App\Entity\PersonnageLignee;
use App\Entity\PersonnageRessource;
use App\Entity\PersonnagesReligions;
use App\Entity\PersonnageTrigger;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\PugilatHistory;
use App\Entity\RenommeHistory;
use App\Entity\Ressource;
use App\Entity\Sort;
use App\Entity\Technologie;
use App\Entity\Token;
use App\Entity\User;
use App\Enum\CompetenceFamilyType;
use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Enum\LevelType;
use App\Enum\Role;
use App\Enum\TriggerType;
use App\Form\Personnage\PersonnageChronologieForm;
use App\Form\Personnage\PersonnageDocumentForm;
use App\Form\Personnage\PersonnageIngredientForm;
use App\Form\Personnage\PersonnageItemForm;
use App\Form\Personnage\PersonnageLigneeForm;
use App\Form\Personnage\PersonnageOriginForm;
use App\Form\Personnage\PersonnageReligionForm;
use App\Form\Personnage\PersonnageRessourceForm;
use App\Form\Personnage\PersonnageRichesseForm;
use App\Form\Personnage\PersonnageUpdateHeroismeForm;
use App\Form\Personnage\PersonnageUpdatePugilatForm;
use App\Form\Personnage\PersonnageUpdateRenommeForm;
use App\Form\PersonnageBackgroundForm;
use App\Form\PersonnageDeleteForm;
use App\Form\PersonnageFindForm;
use App\Form\PersonnageForm;
use App\Form\PersonnageStatutForm;
use App\Form\PersonnageUpdateAgeForm;
use App\Form\PersonnageUpdateDomaineForm;
use App\Form\PersonnageUpdateForm;
use App\Form\PersonnageXpForm;
use App\Form\TriggerDeleteForm;
use App\Form\TriggerForm;
use App\Form\TrombineForm;
use App\Manager\GroupeManager;
use App\Manager\PersonnageManager;
use App\Repository\CompetenceRepository;
use App\Repository\ConnaissanceRepository;
use App\Repository\DomaineRepository;
use App\Repository\GnRepository;
use App\Repository\ParticipantRepository;
use App\Repository\PersonnageApprentissageRepository;
use App\Repository\PersonnageRepository;
use App\Repository\PotionRepository;
use App\Repository\PriereRepository;
use App\Repository\SortRepository;
use App\Repository\TechnologieRepository;
use App\Repository\TerritoireRepository;
use App\Security\MultiRolesExpression;
use App\Service\CompetenceService;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/personnage', name: 'personnage.')]
class PersonnageController extends AbstractController
{
    // contient la liste des colonnes
    protected $columnDefinitions = [
        'colId' => ['label' => '#', 'fieldName' => 'id', 'sortFieldName' => 'id', 'tooltip' => 'Numéro d\'identifiant'],
        'colStatut' => [
            'label' => 'S',
            'fieldName' => 'status',
            'canOrder' => false,
            'sortFieldName' => 'status',
            'tooltip' => 'Statut',
        ],
        'colNom' => [
            'label' => 'Nom',
            'fieldName' => 'nom',
            'sortFieldName' => 'nom',
            'tooltip' => 'Nom et surnom du personnage',
        ],
        'colClasse' => [
            'label' => 'Classe',
            'fieldName' => 'classe',
            'sortFieldName' => 'classe',
            'tooltip' => 'Classe du personnage',
        ],
        'colGroupe' => [
            'label' => 'Groupe',
            'fieldName' => 'groupe',
            'sortFieldName' => 'groupe',
            'tooltip' => 'Dernier GN - Groupe participant',
        ],
        'colRenommee' => [
            'label' => 'Renommée',
            'fieldName' => 'renomme',
            'sortFieldName' => 'renomme',
            'tooltip' => 'Points de renommée',
            'canOrder' => false,
        ],
        'colPugilat' => [
            'label' => 'Pugilat',
            'fieldName' => 'pugilat',
            'sortFieldName' => 'pugilat',
            'tooltip' => 'Points de pugilat',
            'canOrder' => false,
        ],
        'colHeroisme' => [
            'label' => 'Héroïsme',
            'fieldName' => 'heroisme',
            'sortFieldName' => 'heroisme',
            'tooltip' => 'Points d\'héroisme',
            'canOrder' => false,
        ],
        'colUser' => [
            'label' => 'Utilisateur',
            'fieldName' => 'user',
            'sortFieldName' => 'user',
            'tooltip' => 'Liste des utilisateurs (Nom et prénom) par GN',
            'canOrder' => false,
        ],
        'colXp' => [
            'label' => 'Points d\'expérience',
            'fieldName' => 'xp',
            'sortFieldName' => 'xp',
            'tooltip' => 'Points d\'expérience actuels sur le total max possible',
        ],
        'colHasAnomalie' => [
            'label' => 'Ano.',
            'fieldName' => 'hasAnomalie',
            'sortFieldName' => 'hasAnomalie',
            'tooltip' => 'Une pastille orange indique une anomalie',
            'canOrder' => false,
        ],
    ];

    /**
     * Page d'accueil de gestion des personnage.
     */
    public function accueilAction(Request $request): Response
    {
        return $this->render('personnage/accueil.twig', []);
    }

    #[Deprecated]
    #[Route('/admin/{personnage}/detail', name: 'legacy.detail', requirements: ['personnage' => Requirement::DIGITS])]
    #[IsGranted(Role::USER->value)]
    public function actionLegacyDetail(#[MapEntity] Personnage $personnage): RedirectResponse
    {
        return $this->redirectToRoute(
            'personnage.detail.tab',
            ['personnage' => $personnage->getId(), 'tab' => 'general'],
            301,
        );
    }

    #[Route('/{personnage}/competence/add', name: 'add.competence')]
    #[IsGranted(Role::USER->value)]
    public function addCompetenceAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        PersonnageService $personnageService,
    ): RedirectResponse|Response {
        $this->hasAccess($personnage, [Role::SCENARISTE, Role::ORGA]);

        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $availableCompetences = $personnageService->getAvailableCompetences($personnage);
        $referer = $request->headers->get('referer');

        if (0 === $availableCompetences->count()) {
            $this->addFlash('error', 'Désolé, il n\'y a plus de compétence disponible.');

            if (!$referer || str_starts_with($referer, $request->getUri())) {
                $this->redirect($referer, 303);
            }

            return $this->redirectToRoute('homepage', [], 303);
        }

        // construit le tableau de choix
        $choices = [];
        foreach ($availableCompetences as $competence) {
            $choices[$competence->getLabel().' (cout : '.$personnageService->getCompetenceCout(
                $personnage,
                $competence,
            ).' xp)'] = $competence->getId();
        }

        $competence = new Competence();
        $form = $this->createFormBuilder($competence)
            ->add('id', ChoiceType::class, [
                'label' => 'Choisissez une nouvelle compétence',
                'choices' => $choices,
            ])
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Ajouter la compétence', 'attr' => ['class' => 'btn btn-secondary']],
            )->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $competenceId = $data->getId();
            $competence = $this->entityManager->find(Competence::class, $competenceId);

            $service = null;

            if (!$competence) {
                $form->addError('Competence not found');
            } else {
                $service = $personnageService->addCompetence($personnage, $competence, false);
            }

            if (!$service->hasErrors()) {
                $this->addFlash('success', 'Votre personnage a été sauvegardé.');

                $this->log($competence, 'competence_add', true);

                return $this->redirectToRoute(
                    'personnage.detail.tab',
                    ['personnage' => $personnage->getId(), 'tab' => 'competences'],
                    303,
                );
            }

            $form->get('id')->addError(new FormError($service->getErrorsAsString()));
        }

        return $this->render('personnage/competence.twig', [
            'form' => $form->createView(),
            'isAdmin' => $this->isGranted(Role::ADMIN->value) || $this->isGranted(Role::SCENARISTE->value),
            'personnage' => $personnage,
            'participant' => $participant,
            'competences' => $availableCompetences,
        ]);
    }

    protected function hasAccess(Personnage $personnage, array $roles = []): void
    {
        $isAdmin = false;
        foreach ($roles as $role) {
            if ($this->isGranted($role->value)) {
                $isAdmin = true;
            }
        }

        $isPersonnage = false;
        foreach ($this->getUser()?->getPersonnages() as $personnageUser) {
            if ($personnageUser->getId() === $personnage->getId()) {
                $isPersonnage = true;
            }
        }

        $this->setCan(self::IS_ADMIN, $isAdmin);
        $this->setCan(self::IS_MEMBRE, $isPersonnage);
        $this->setCan(self::CAN_MANAGE, $isAdmin);
        $this->setCan(self::CAN_READ_PRIVATE, $isPersonnage || $isAdmin);
        $this->setCan(self::CAN_READ_SECRET, $isPersonnage || $isAdmin);
        $this->setCan(self::CAN_WRITE, $isPersonnage || $isAdmin);
        $this->setCan(self::CAN_READ, $isPersonnage || $this->can(self::CAN_READ));

        /** @var User $user */
        $user = $this->getUser();
        // Doit être connecté
        if (!$user || !$this->isGranted(Role::USER->value)) {
            throw new AccessDeniedException();
        }

        // Est l'interprète du personnage
        if ($personnage->getUser()?->getId() === $user->getId()) {
            return;
        }

        // Est un niveau admin suffisant
        if ($roles) {
            /** @var Role $role */
            foreach ($roles as $role) {
                if ($this->isGranted($role->value)) {
                    return;
                }
            }
        }

        throw new AccessDeniedException();
    }

    protected function getParticipant(Personnage $personnage, Request $request): ?Participant
    {
        if ($id = $request->get('participant')) {
            $participant = $this->entityManager->getRepository(Participant::class)->findOneBy(['id' => $id]);
            if ($participant) {
                return $participant;
            }
        }

        return $personnage->getLastParticipant();
    }

    protected function checkPersonnageGroupeLock(
        Personnage $personnage,
        ?Participant $participant,
        ?string $route = null,
        ?array $routeParams = null,
        ?string $msg = null,
    ): ?Response {
        if (!$participant) {
            $participant = $personnage->getLastParticipant();
        }

        return $this->checkGroupeLocked(
            $personnage->getLastParticipantGnGroupe(),
            $route ?? 'personnage.detail',
            $routeParams ?? ['personnage' => $personnage->getId(), 'participant' => $participant->getId()],
            $msg ?? "Désolé, il n'est plus possible de modifier ce personnage.",
        );
    }

    /**
     * Ajout d'un personnage (orga seulement).
     */
    #[Route('/admin/add', name: 'admin.add')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function adminAddAction(
        Request $request,

        PersonnageService $personnageService,
    ): Response {
        $personnage = new Personnage();
        $gnActif = GroupeManager::getGnActif($this->entityManager);

        $participant = $request->get('participant');
        if (!$participant) {
            // essaye de récupérer le participant du gn actif

            if ($gnActif) {
                $participant = $this->getUser()?->getParticipant($gnActif);
            }

            if (!$participant) {
                // sinon récupère le dernier dans la liste
                $participant = $this->getUser()?->getLastParticipant();
            }
        } else {
            $participant = $this->entityManager->getRepository(Participant::class)->find($participant);
        }

        $form = $this->createForm(PersonnageForm::class, $personnage)
            ->add('classe', EntityType::class, [
                'label' => 'Classes disponibles',
                'choice_label' => 'label',
                'class' => Classe::class,
            ])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            if ($participant) {
                $participant->setPersonnage($personnage);

                if ($participant->getGroupe()) {
                    $personnage->setGroupe($participant->getGroupe());
                }
            }

            $personnage->setXp($gnActif->getXpCreation());

            // historique
            $historique = new ExperienceGain();
            $historique->setExplanation('Création de votre personnage');
            $historique->setOperationDate(new \DateTime('NOW'));
            $historique->setPersonnage($personnage);
            $historique->setXpGain($gnActif->getXpCreation());
            $this->entityManager->persist($historique);

            // ajout des compétences acquises à la création
            $competenceHandler = $personnageService->addClasseCompetencesFamilyCreation($personnage);
            if ($competenceHandler?->hasErrors()) {
                $this->addFlash('success', $competenceHandler?->getErrorsAsString());

                return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
            }
            /*foreach ($personnage->getClasse()->getCompetenceFamilyCreations() as $competenceFamily) {
                $firstCompetence = $competenceFamily->getFirstCompetence();
                if ($firstCompetence) {
                    $personnage->addCompetence($firstCompetence);
                    $firstCompetence->addPersonnage($personnage);
                    $this->entityManager->persist($firstCompetence);
                }
            }*/

            // Ajout des points d'expérience gagné grace à l'age
            $xpAgeBonus = $personnage->getAge()->getBonus();
            if ($xpAgeBonus) {
                $personnage->addXp($xpAgeBonus);
                $historique = new ExperienceGain();
                $historique->setExplanation("Bonus lié à l'age");
                $historique->setOperationDate(new \DateTime('NOW'));
                $historique->setPersonnage($personnage);
                $historique->setXpGain($xpAgeBonus);
                $this->entityManager->persist($historique);
            }

            $this->entityManager->persist($personnage);
            if ($participant) {
                $this->entityManager->persist($participant);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Votre personnage a été sauvegardé.');
            if ($participant && $participant->getGroupe()) {
                return $this->redirectToRoute('groupe.detail', ['groupe' => $participant->getGroupe()->getId()], 303);
            }

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('personnage/add.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
        ]);
    }

    /**
     * Ajoute un background au personnage.
     */
    #[Route('/{personnage}/addBackground', name: 'add.background')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminAddBackgroundAction(
        Request $request,

        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $background = new PersonnageBackground();

        $background->setPersonnage($personnage);
        $background->setUser($this->getUser());

        $form = $this->createForm(PersonnageBackgroundForm::class, $background);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();

            $this->entityManager->persist($background);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le background a été sauvegardé.');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'biographie'],
                303,
            );
        }

        return $this->render('personnage/addBackground.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'background' => $background,
        ]);
    }

    /**
     * Ajoute un evenement de chronologie au personnage.
     */
    #[Route('/{personnage}/addChronologie', name: 'add.chronologie')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminAddChronologieAction(
        Request $request,

        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $personnageChronologie = new PersonnageChronologie();
        $personnageChronologie->setPersonnage($personnage);

        $form = $this->createForm(PersonnageChronologieForm::class, $personnageChronologie)
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider l\'évènement', 'attr' => ['class' => 'btn btn-secondary']],
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $anneeGN = $form->get('annee')->getData();
            $evenement = $form->get('evenement')->getData();

            $personnageChronologie = new PersonnageChronologie();

            $personnageChronologie->setAnnee($anneeGN);
            $personnageChronologie->setEvenement($evenement);
            $personnageChronologie->setPersonnage($personnage);

            $this->entityManager->persist($personnageChronologie);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'évènement a été ajouté à la chronologie.');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'biographie'],
                303,
            );
        }

        return $this->render('personnage/updateChronologie.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'personnageChronologie' => $personnageChronologie,
        ]);
    }

    /**
     * Ajoute une connaissance à un personnage.
     */
    #[Route('/{personnage}/connaissance/{connaissance}/add', name: 'add.connaissance')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminAddConnaissanceAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Connaissance $connaissance,
    ): RedirectResponse {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $personnage->addConnaissance($connaissance);
        $this->entityManager->flush();

        $this->addFlash('success', $connaissance->getLabel().' '.$connaissance->getNiveau().' a été ajouté.');

        return $this->redirectToRoute(
            'personnage.update.connaissance',
            ['personnage' => $personnage->getId(), 303],
        );
    }

    /**
     * Ajoute une lignée au personnage.
     */
    #[Route('/{personnage}/addLignee', name: 'add.lignee')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminAddLigneeAction(
        Request $request,

        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $personnageLignee = new PersonnageLignee();
        $personnageLignee->setPersonnage($personnage);

        $form = $this->createForm(PersonnageLigneeForm::class, $personnageLignee)
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider les modifications', 'attr' => ['class' => 'btn btn-secondary']],
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parent1 = $form->get('parent1')->getData();
            $parent2 = $form->get('parent2')->getData();
            $lignee = $form->get('lignee')->getData();

            $personnageLignee->setParent1($parent1);
            $personnageLignee->setParent2($parent2);
            $personnageLignee->setLignee($lignee);

            $this->entityManager->persist($personnageLignee);
            $this->entityManager->flush();

            $this->addFlash('success', 'La lignée a été ajoutée.');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'biographie'],
                303,
            );
        }

        return $this->render('personnage/updateLignee.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'lignee' => $personnageLignee,
        ]);
    }

    /**
     * Ajoute une potion à un personnage.
     */
    #[Route('/{personnage}/addPotion', name: 'add.potion')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    // TODO check
    public function adminAddPotionAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $potionID = $request->get('potion');

        $potion = $this->entityManager->getRepository(Potion::class)
            ->find($potionID);

        $nomPotion = $potion->getLabel();

        $personnage->addPotion($potion);

        $this->entityManager->flush();

        $this->addFlash('success', $nomPotion.' a été ajoutée.');

        return $this->redirectToRoute('personnage.update.potion', ['personnage' => $personnage->getId(), 303]);
    }

    /**
     * Ajoute une priere à un personnage.
     */
    #[Route('/{personnage}/priere/{priere}/add', name: 'add.priere')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminAddPriereAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Priere $priere,
    ): RedirectResponse {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $priere->addPersonnage($personnage);

        $this->entityManager->flush();

        $this->addFlash('success', $priere->getLabel().' a été ajoutée.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.update.priere',
            ['personnage' => $personnage->getId(), 303],
        );
    }

    /**
     * Ajoute une religion à un personnage.
     */
    #[Route('/{personnage}/addReligion', name: 'add.religion')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    #[Deprecated] // TODO import from Participant::religionAddAction()
    public function adminAddReligionAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        PersonnageService $personnageService,
    ): RedirectResponse|Response {
        // refUser la demande si le personnage est Fanatique
        if ($personnage->isFanatique()) {
            $this->addFlash(
                'error',
                'Désolé, le personnage êtes un Fanatique, il vous est impossible de choisir une nouvelle religion. (supprimer la religion fanatique qu\'il possède avant)',
            );

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $personnageReligion = new PersonnagesReligions();
        $personnageReligion->setPersonnage($personnage);

        // ne proposer que les religions que le personnage ne pratique pas déjà ...
        $availableReligions = $personnageService->getAdminAvailableReligions($personnage);

        if (0 === $availableReligions->count()) {
            $this->addFlash('error', 'Désolé, il n\'y a plus de religion disponibles');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        // construit le tableau de choix
        $choices = [];
        foreach ($availableReligions as $religion) {
            $choices[] = $religion;
        }

        $form = $this->createForm(PersonnageReligionForm::class, $personnageReligion)
            ->add('religion', ChoiceType::class, [
                'required' => true,
                'label' => 'Votre religion',
                'choices' => $availableReligions,
                'choice_label' => 'label',
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider votre religion']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnageReligion = $form->getData();

            // supprimer toutes les autres religions si l'utilisateur à choisi fanatique
            // n'autoriser que un Fervent que si l'utilisateur n'a pas encore Fervent.
            if (3 === $personnageReligion->getReligionLevel()->getIndex()) {
                $personnagesReligions = $personnage->getPersonnagesReligions();
                foreach ($personnagesReligions as $oldReligion) {
                    $this->entityManager->remove($oldReligion);
                }
            } elseif (2 === $personnageReligion->getReligionLevel()->getIndex()) {
                if ($personnage->isFervent()) {
                    $this->addFlash(
                        'error',
                        'Désolé, vous êtes déjà Fervent d\'une autre religion, il vous est impossible de choisir une nouvelle religion en tant que Fervent. Veuillez contacter votre orga en cas de problème.',
                    );

                    return $this->redirectToRoute('homepage', [], 303);
                }
            }

            $this->entityManager->persist($personnageReligion);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/religion_add.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Ajoute un sort à un personnage.
     */
    #[Route('/{personnage}/sort/{sort}/add', name: 'add.sort')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminAddSortAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Sort $sort,
    ): RedirectResponse {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $personnage->addSort($sort);

        $this->entityManager->flush();

        $this->addFlash('success', $sort->getLabel().' '.$sort->getNiveau().' a été ajouté.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.update.sort',
            ['personnage' => $personnage->getId(), 303],
        );
    }

    /**
     * Ajoute une technologie à un personnage.
     */
    #[Route('/{personnage}/technologie/{technologie}/add', name: 'add.technologie')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminAddTechnologieAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Technologie $technologie,
    ): RedirectResponse {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $personnage->addTechnologie($technologie);

        $this->entityManager->flush();

        $this->addFlash('success', $technologie->getLabel().' a été ajoutée.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.update.technologie',
            ['personnage' => $personnage->getId(), 303],
        );
    }

    /**
     * Supression d'un personnage (orga seulement).
     */
    #[Route('/{personnage}/delete', name: 'delete')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminDeleteAction(
        Request $request,

        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createForm(PersonnageDeleteForm::class, $personnage)
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            foreach ($personnage->getExperienceGains() as $xp) {
                $personnage->removeExperienceGain($xp);
                $this->entityManager->remove($xp);
            }

            foreach ($personnage->getExperienceUsages() as $xp) {
                $personnage->removeExperienceUsage($xp);
                $this->entityManager->remove($xp);
            }

            foreach ($personnage->getMembres() as $membre) {
                $personnage->removeMembre($membre);
                $this->entityManager->remove($membre);
            }

            foreach ($personnage->getPersonnagesReligions() as $personnagesReligions) {
                $personnage->removePersonnagesReligions($personnagesReligions);
                $this->entityManager->remove($personnagesReligions);
            }

            foreach ($personnage->getPostulants() as $postulant) {
                $personnage->removePostulant($postulant);
                $this->entityManager->remove($postulant);
            }

            foreach ($personnage->getPersonnageLangues() as $personnageLangue) {
                $personnage->removePersonnageLangues($personnageLangue);
                $this->entityManager->remove($personnageLangue);
            }

            foreach ($personnage->getPersonnageTriggers() as $trigger) {
                $personnage->removePersonnageTrigger($trigger);
                $this->entityManager->remove($trigger);
            }

            foreach ($personnage->getPersonnageBackgrounds() as $background) {
                $personnage->removePersonnageBackground($background);
                $this->entityManager->remove($background);
            }

            foreach ($personnage->getPersonnageHasTokens() as $token) {
                $personnage->removePersonnageHasToken($token);
                $this->entityManager->remove($token);
            }

            foreach ($personnage->getParticipants() as $participant) {
                $participant->setPersonnage();
                $this->entityManager->persist($participant);
            }

            $this->entityManager->remove($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été supprimé.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('personnage/delete.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    #[Route('/{personnage}/background/{background}/delete', name: 'delete.background', requirements: [
        'personnage' => Requirement::DIGITS,
        'background' => Requirement::DIGITS,
    ])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminDeleteBackgroundAction(
        Request $request,

        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnageBackground $background,
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $background,
            'Supprimer un background',
            'La background a été supprimé',
            $this->generateUrl('personnage.detail.tab', ['personnage' => $personnage->getId(), 'tab' => 'biographie']),
            [
                // it's an admin view, do not need to test role for this breadcrumb
                ['route' => $this->generateUrl('personnage.list'), 'name' => 'Liste des personnages'],
                [
                    'route' => $this->generateUrl('personnage.detail', ['personnage' => $personnage->getId()]),
                    'name' => $personnage->getPublicName(),
                ],
                ['name' => 'Supprimer un background'],
            ],
        );
    }

    /**
     * Retire un évènement d'un personnage.
     */
    #[Route('/{personnage}/chronologie/{personnageChronologie}/delete', name: 'delete.chronologie', requirements: [
        'personnage' => Requirement::DIGITS,
        'personnageChronologie' => Requirement::DIGITS,
    ])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminDeleteChronologieAction(
        Request $request,

        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnageChronologie $personnageChronologie,
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $personnageChronologie,
            'Supprimer une chronologie',
            'La chronologie a été supprimée',
            $this->generateUrl('personnage.detail.tab', ['personnage' => $personnage->getId(), 'tab' => 'biographie']),
            [
                // it's an admin view, do not need to test role for this breadcrumb
                ['route' => $this->generateUrl('personnage.list'), 'name' => 'Liste des personnages'],
                [
                    'route' => $this->generateUrl('personnage.detail', ['personnage' => $personnage->getId()]),
                    'name' => $personnage->getPublicName(),
                ],
                ['name' => 'Supprimer une chronologie'],
            ],
            content: sprintf(
                '<strong>%s</strong>: %s',
                $personnageChronologie->getAnnee(),
                $personnageChronologie->getEvenement(),
            ),
        );
    }

    /**
     * Retire une lignée d'un personnage.
     */
    #[Route('/{personnage}/lignee/{personnageLignee}/delete', name: 'delete.lignee', requirements: [
        'personnage' => Requirement::DIGITS,
        'personnageLignee' => Requirement::DIGITS,
    ])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminDeleteLigneeAction(
        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnageLignee $personnageLignee,
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $personnageLignee,
            'Supprimer une lignée',
            'La lignée a été supprimée',
            $this->generateUrl('personnage.detail.tab', ['personnage' => $personnage->getId(), 'tab' => 'biographie']),
            [
                // it's an admin view, do not need to test role for this breadcrumb
                ['route' => $this->generateUrl('personnage.list'), 'name' => 'Liste des personnages'],
                [
                    'route' => $this->generateUrl('personnage.detail', ['personnage' => $personnage->getId()]),
                    'name' => $personnage->getPublicName(),
                ],
                ['name' => 'Supprimer une lignée'],
            ],
            content: $personnageLignee->getParent1()?->getIdName().' & '.$personnageLignee->getParent2()?->getIdName(),
        );
    }

    /**
     * Télécharger la liste des personnages au format CSV.
     */
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    #[Route('/admin/download', name: 'admin.download', requirements: [])]
    public function adminDownloadAction(Request $request): void
    {
        // TODO ?
    }

    /**
     * Modifie le matériel lié à un personnage.
     */
    #[Route('/{personnage}/updateMateriel', name: 'update.materiel')]
    #[IsGranted(new MultiRolesExpression(
        Role::ORGA, Role::SCENARISTE,
    ), message: 'You are not allowed to access to this.')]
    public function adminMaterielAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createFormBuilder()
            ->add('materiel', TextareaType::class, [
                'required' => false,
                'data' => $personnage->getMateriel(),
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ])
            ->add('valider', SubmitType::class, ['label' => 'Valider', 'attr' => ['class' => 'btn btn-secondary mt-2']])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $personnage->setMateriel($data['materiel']);
            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'enveloppe'],
                303,
            );
        }

        return $this->render('personnage/materiel.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Imprimer la liste des personnages.
     */
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    #[Route('/admin.print', name: 'admin.print')]
    public function adminPrintAction(Request $request): void
    {
        // TODO ?
    }

    /**
     * Retire une connaissance à un personnage.
     */
    #[Route('/{personnage}/connaissance/{connaissance}/delete', name: 'delete.connaissance')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminRemoveConnaissanceAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Connaissance $connaissance,
    ): RedirectResponse {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $personnage->removeConnaissance($connaissance);
        $this->entityManager->flush();

        $this->addFlash('success', $connaissance->getLabel().' '.$connaissance->getNiveau().' a été retiré.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.update.connaissance',
            ['personnage' => $personnage->getId(), 303],
        );
    }

    /**
     * Affiche le détail d'un personnage (pour les orgas).
     */
    // #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]

    #[Route('/{personnage}/domaine/{domaine}/delete', name: 'delete.domaine')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminRemoveDomaineAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Domaine $domaine,
    ): RedirectResponse {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $nomDomaine = $domaine->getLabel();

        $personnage->removeDomaine($domaine);

        $this->entityManager->flush();

        $this->addFlash('success', $nomDomaine.' a été retirée.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.update.domaine',
            ['personnage' => $personnage->getId(), 303],
        );
    }

    /**
     * Retire une langue d'un personnage.
     */
    #[Route('/{personnage}/deleteLangue/{personnageLangue}', name: 'delete.langue')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminRemoveLangueAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnageLangues $personnageLangue,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createFormBuilder()
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Retirer la langue', 'attr' => ['class' => 'btn btn-secondary']],
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($personnageLangue);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/removeLangue.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'personnageLangue' => $personnageLangue,
        ]);
    }

    /**
     * Retire une potion à un personnage.
     */
    #[Route('/{personnage}/potion/{potion}/delete', name: 'delete.potion')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminRemovePotionAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Potion $potion,
    ): RedirectResponse {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $nomPotion = $potion->getLabel();
        $personnage->removePotion($potion);

        $this->entityManager->flush();

        $this->addFlash('success', $nomPotion.' a été retirée.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.update.potion',
            ['personnage' => $personnage->getId(), 303],
        );
    }

    /**
     * Retire une priere à un personnage.
     */
    #[Route('/{personnage}/priere/{priere}/delete', name: 'priere.delete')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminRemovePriereAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Priere $priere,
    ): RedirectResponse {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $priere->removePersonnage($personnage);

        $this->entityManager->flush();

        $this->addFlash('success', $priere->getLabel().' a été retirée.');

        return $this->redirectToReferer($request) ?? $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.update.priere',
            ['personnage' => $personnage->getId(), 303],
        );
    }

    /**
     * Retire une religion d'un personnage.
     */
    #[Route('/{personnage}/religion/{personnageReligion}/delete', name: 'delete.religion')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminRemoveReligionAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnagesReligions $personnageReligion,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        return $this->genericDelete(
            $personnageReligion,
            'Supprimer une religion',
            'La religion a été supprimée',
            $this->generateUrl('personnage.detail.tab', ['personnage' => $personnage->getId(), 'tab' => 'religions']),
            [
                // it's an admin view, do not need to test role for this breadcrumb
                ['route' => $this->generateUrl('personnage.list'), 'name' => 'Liste des personnages'],
                [
                    'route' => $this->generateUrl(
                        'personnage.detail.tab',
                        ['personnage' => $personnage->getId(), 'tab' => 'religions'],
                    ),
                    'name' => $personnage->getPublicName(),
                ],
                ['name' => 'Supprimer une religion'],
            ],
        );
    }

    /**
     * Retire un sort à un personnage.
     */
    #[Route('/{personnage}/sort/{sort}/delete', name: 'delete.sort')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminRemoveSortAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Sort $sort,
    ): RedirectResponse {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $personnage->removeSort($sort);

        $this->entityManager->flush();

        $this->addFlash('success', $sort->getLabel().' '.$sort->getNiveau().' a été retiré.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.update.sort',
            ['personnage' => $personnage->getId(), 303],
        );
    }

    /**
     * Retire une technologie à un personnage.
     */
    #[Route('/{personnage}/technologie/{technologie}/delete', name: 'delete.technologie')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminRemoveTechnologieAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Technologie $technologie,
    ): RedirectResponse {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $personnage->removeTechnologie($technologie);

        $this->entityManager->flush();

        $this->addFlash('success', $technologie->getLabel().' a été retirée.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.update.technologie',
            ['personnage' => $personnage->getId(), 303],
        );
    }

    /**
     * Modification du statut d'un personnage.
     */
    #[Route('/{personnage}/statut', name: 'statut')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminStatutAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createForm(PersonnageStatutForm::class, $personnage)
            ->add('submit', SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $evenement = !$personnage->getVivant() ? 'Mort violente' : 'Résurrection';

            // TODO: Trouver comment avoir la date du GN
            /*
            $personnageChronologie = new \App\Entity\PersonnageChronologie();
            $personnageChronologie->setAnnee($anneeGN);
            $personnageChronologie->setEvenement($evenement);
            $personnageChronologie->setPersonnage($personnage);
            $this->entityManager->persist($personnageChronologie);
            */

            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le statut du personnage a été modifié');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/statut.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un jeton vieillesse au personnage.
     */
    #[Route('/{personnage}/addToken', name: 'token.add')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminTokenAddAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $token = $request->get('token');
        $token = $this->entityManager->getRepository(Token::class)->findOneByTag($token);

        // donne un jeton vieillesse
        $personnageHasToken = new PersonnageHasToken();
        $personnageHasToken->setToken($token);
        $personnageHasToken->setPersonnage($personnage);

        $personnage->addPersonnageHasToken($personnageHasToken);
        $this->entityManager->persist($personnageHasToken);

        $personnage->setAgeReel($personnage->getAgeReel() + 5); // ajoute 5 ans à l'age réél

        if (0 == $personnage->getPersonnageHasTokens()->count() % 2) {
            if (5 != $personnage->getAge()->getId()) {
                $age = $this->entityManager->getRepository('\\'.Age::class)->findOneById(
                    $personnage->getAge()->getId() + 1,
                );
                $personnage->setAge($age);
            } else {
                $personnage->setVivant(false);
                foreach ($personnage->getParticipants() as $participant) {
                    if (null != $participant->getGn()) {
                        $anneeGN = $participant->getGn()->getDateJeu() + rand(1, 4);
                    }
                }

                $evenement = 'Mort de vieillesse';
                $personnageChronologie = new PersonnageChronologie();
                $personnageChronologie->setAnnee($anneeGN);
                $personnageChronologie->setEvenement($evenement);
                $personnageChronologie->setPersonnage($personnage);
                $this->entityManager->persist($personnageChronologie);
            }
        }

        $this->entityManager->persist($personnage);
        $this->entityManager->flush();
        $this->addFlash('success', 'Le jeton '.$token->getTag().' a été ajouté.');

        return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
    }

    /**
     * Retire un jeton d'un personnage.
     */
    #[Route('/{personnage}/deleteToken', name: 'token.delete')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminTokenDeleteAction(
        Request $request,

        #[MapEntity] Personnage $personnage,
        PersonnageHasToken $personnageHasToken,
    ): RedirectResponse {
        $personnage->removePersonnageHasToken($personnageHasToken);
        // $personnage->setAgeReel($personnage->getAgeReel() - 5);
        if (0 != $personnage->getPersonnageHasTokens()->count() % 2 && 5 != $personnage->getAge()->getId()) {
            $age = $this->entityManager->getRepository('\\'.Age::class)->findOneById(
                $personnage->getAge()->getId() - 1,
            );
            $personnage->setAge($age);
        }

        $this->entityManager->remove($personnageHasToken);
        $this->entityManager->persist($personnage);

        // Chronologie : Fruits & Légumes
        foreach ($personnage->getParticipants() as $participant) {
            if (null != $participant->getGn()) {
                $anneeGN = $participant->getGn()->getDateJeu() + rand(-2, 2);
            }
        }

        $evenement = 'Consommation de Fruits & Légumes';
        $personnageChronologie = new PersonnageChronologie();
        $personnageChronologie->setAnnee($anneeGN);
        $personnageChronologie->setEvenement($evenement);
        $personnageChronologie->setPersonnage($personnage);
        $this->entityManager->persist($personnageChronologie);

        $this->entityManager->flush();

        $this->addFlash('success', 'Le jeton a été retiré.');

        return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
    }

    /**
     * Ajoute un trigger.
     */
    #[Route('/{personnage}/addTrigger', name: 'trigger.add')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminTriggerAddAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $trigger = new PersonnageTrigger();
        $trigger->setPersonnage($personnage);
        $trigger->setDone(false);

        $form = $this->createForm(TriggerForm::class, $trigger)
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider les modifications', 'attr' => ['class' => 'btn btn-secondary']],
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trigger = $form->getData();

            $this->entityManager->persist($trigger);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le déclencheur a été ajouté.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/addTrigger.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modifier l'age d'un personnage.
     */
    #[Route('/{personnage}/updateAge', name: 'update.age')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdateAgeAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        // TODO update route redirect to personnage Detail
        return $this->handleCreateOrUpdate(
            $request,
            $personnage,
            PersonnageUpdateAgeForm::class,
        );
    }

    protected function handleCreateOrUpdate(
        Request $request,
        $entity,
        string $formClass,
        array $breadcrumb = [],
        array $routes = [],
        array $msg = [],
        ?callable $entityCallback = null,
    ): RedirectResponse|Response {
        return parent::handleCreateOrUpdate(
            request: $request,
            entity: $entity,
            formClass: $formClass,
            breadcrumb: $breadcrumb,
            routes: $routes,
            msg: [
                ...$msg,
                'entity' => $this->translator->trans('personnage'),
                'entity_added' => $this->translator->trans('Le personnage a été ajouté'),
                'entity_updated' => $this->translator->trans('Le personnage a été mis à jour'),
                'entity_deleted' => $this->translator->trans('Le personnage a été supprimé'),
                'entity_list' => $this->translator->trans('Liste des personnages'),
                'title_add' => $this->translator->trans('Ajouter un personnage'),
                'title_update' => $this->translator->trans('Modifier un personnage'),
            ],
            entityCallback: $entityCallback,
        );
    }

    /**
     * Modifie le background d'un personnage.
     */
    #[Route('/{personnage}/background/{background}/update', name: 'update.background', requirements: [
        'personnage' => Requirement::DIGITS,
        'background' => Requirement::DIGITS,
    ])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdateBackgroundAction(
        Request $request,

        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnageBackground $background,
    ): RedirectResponse|Response {
        $form = $this->createForm(PersonnageBackgroundForm::class, $background);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();

            $this->entityManager->persist($background);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le background a été sauvegardé.');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'biographie'],
                303,
            );
        }

        return $this->render('personnage/updateBackground.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'background' => $background,
        ]);
    }

    /**
     * Affiche la liste des connaissances pour modification.
     */
    #[Route('/{personnage}/updateConnaissance', name: 'update.connaissance')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdateConnaissanceAction(
        Request $request,
        PagerService $pagerService,
        #[MapEntity] Personnage $personnage,
        ConnaissanceRepository $connaissanceRepository,
    ): Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $pagerService->setRequest($request)->setRepository($connaissanceRepository)->setLimit(50);

        return $this->render('personnage/updateConnaissance.twig', [
            'personnage' => $personnage,
            'pagerService' => $pagerService,
            'paginator' => $connaissanceRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Modifie la liste des domaines de magie.
     */
    #[Route('/{personnage}/updateDomaine', name: 'update.domaine')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdateDomaineAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $originalDomaines = new ArrayCollection();
        foreach ($personnage->getDomaines() as $domaine) {
            $originalDomaines[] = $domaine;
        }

        $form = $this->createForm(PersonnageUpdateDomaineForm::class, $personnage)
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider les modifications', 'attr' => ['class' => 'btn btn-secondary']],
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            foreach ($personnage->getDomaines() as $domaine) {
                if (!$originalDomaines->contains($domaine)) {
                    $domaine->addPersonnage($personnage);
                }
            }

            foreach ($originalDomaines as $domaine) {
                if (!$personnage->getDomaines()->contains($domaine)) {
                    $domaine->removePersonnage($personnage);
                }
            }

            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modification de l'héroisme d'un personnage.
     */
    #[Route('/{personnage}/updateHeroisme', name: 'update.heroisme')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdateHeroismeAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createForm(PersonnageUpdateHeroismeForm::class)
            ->add('save', SubmitType::class, ['label' => 'Valider les modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $heroisme = $form->get('heroisme')->getData();
            $explication = $form->get('explication')->getData();

            $heroisme_history = new HeroismeHistory();

            $heroisme_history->setHeroisme($heroisme);
            $heroisme_history->setExplication($explication);
            $heroisme_history->setPersonnage($personnage);
            $personnage->addHeroisme($heroisme);

            $this->entityManager->persist($heroisme_history);
            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modifie la liste des ingrédients.
     */
    #[Route('/{personnage}/updateIngredient', name: 'update.ingredient')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdateIngredientAction(
        Request $request,

        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $originalPersonnageIngredients = new ArrayCollection();

        /*
         * Crée un tableau contenant les objets personnageIngredient du groupe
         */
        foreach ($personnage->getPersonnageIngredients() as $personnageIngredient) {
            $originalPersonnageIngredients->add($personnageIngredient);
        }

        $form = $this->createForm(PersonnageIngredientForm::class, $personnage);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            /*
             * Pour tous les ingredients
             */
            foreach ($personnage->getPersonnageIngredients() as $personnageIngredient) {
                if ($personnageIngredient->getNombre() < 1) {
                    $this->entityManager->remove($personnageIngredient);
                } else {
                    $personnageIngredient->setPersonnage($personnage);
                }
            }

            /*
             *  supprime la relation entre personnageIngredient et le personnage
             */
            foreach ($originalPersonnageIngredients as $personnageIngredient) {
                if (false === $personnage->getPersonnageIngredients()->contains($personnageIngredient)) {
                    $this->entityManager->remove($personnageIngredient);
                }
            }

            $random = $form['random']->getData();

            /*
             *  Gestion des ingrédients alloués au hasard
             */
            if ($random && $random > 0) {
                $ingredients = $this->entityManager->getRepository(Ingredient::class)->findAllOrderedByLabel();
                shuffle($ingredients);
                $needs = new ArrayCollection(array_slice($ingredients, 0, $random));

                foreach ($needs as $ingredient) {
                    $pi = new PersonnageIngredient();
                    $pi->setIngredient($ingredient);
                    $pi->setNombre(1);
                    $pi->setPersonnage($personnage);
                    $this->entityManager->persist($pi);
                }
            }

            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'enveloppe'],
                303,
            );
        }

        return $this->render('personnage/ingredients.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modifie l'origine d'un personnage.
     */
    #[Route('/{personnage}/updateOrigine', name: 'update.origine')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdateOriginAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createForm(PersonnageOriginForm::class, $personnage)
            ->add(
                'save',
                SubmitType::class,
                ['label' => "Valider l'origine du personnage", 'attr' => ['class' => 'btn btn-secondary']],
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            // le personnage doit perdre les langues de son ancienne origine
            // et récupérer les langues de sa nouvelle origine
            foreach ($personnage->getPersonnageLangues() as $personnageLangue) {
                if ('ORIGINE' === $personnageLangue->getSource(
                    ) || 'ORIGINE SECONDAIRE' === $personnageLangue->getSource()) {
                    $personnage->removePersonnageLangues($personnageLangue);
                    $this->entityManager->remove($personnageLangue);
                }
            }

            $newOrigine = $personnage->getTerritoire();
            foreach ($newOrigine->getLangues() as $langue) {
                $personnageLangue = new PersonnageLangues();
                $personnageLangue->setPersonnage($personnage);
                $personnageLangue->setSource('ORIGINE SECONDAIRE');
                $personnageLangue->setLangue($langue);

                $this->entityManager->persist($personnageLangue);
                $personnage->addPersonnageLangues($personnageLangue);
            }

            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Affiche la liste des potions pour modification.
     */
    #[Route('/{personnage}/updatePotion', name: 'update.potion')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdatePotionAction(
        Request $request,
        PagerService $pagerService,
        #[MapEntity] Personnage $personnage,
        PotionRepository $potionRepository,
    ): Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $pagerService->setRequest($request)->setRepository($potionRepository)->setLimit(50);

        return $this->render(
            'personnage/updatePotion.twig',
            [
                'pagerService' => $pagerService,
                'paginator' => $potionRepository->searchPaginated($pagerService),

                'personnage' => $personnage,
            ],
        );
    }

    /**
     * Affiche la liste des prières pour modifications.
     */
    #[Route('/{personnage}/priere/update', name: 'update.priere')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdatePriereAction(
        Request $request,
        PagerService $pagerService,
        #[MapEntity] Personnage $personnage,
        PriereRepository $priereRepository,
    ): Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $pagerService->setRequest($request)->setRepository($priereRepository)->setLimit(50);

        return $this->render(
            'personnage/updatePriere.twig',
            [
                'personnage' => $personnage,
                'pagerService' => $pagerService,
                'paginator' => $priereRepository->searchPaginated($pagerService),
            ],
        );
    }

    /**
     * Modification du pugilat d'un personnage.
     */
    #[Route('/{personnage}/updatePugilat', name: 'update.pugilat')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdatePugilatAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createForm(PersonnageUpdatePugilatForm::class)
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider les modifications', 'attr' => ['class' => 'btn btn-secondary']],
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pugilat = $form->get('pugilat')->getData();
            $explication = $form->get('explication')->getData();

            $pugilat_history = new PugilatHistory();

            $pugilat_history->setPugilat($pugilat);
            $pugilat_history->setExplication($explication);
            $pugilat_history->setPersonnage($personnage);
            $personnage->addPugilat($pugilat);

            $this->entityManager->persist($pugilat_history);
            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modification de la renommee du personnage.
     */
    #[Route('/{personnage}/updateRenomme', name: 'update.renomme')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdateRenommeAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createForm(PersonnageUpdateRenommeForm::class)
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider les modifications', 'attr' => ['class' => 'btn btn-secondary']],
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $renomme = $form->get('renomme')->getData();
            $explication = $form->get('explication')->getData();

            $renomme_history = new RenommeHistory();

            $renomme_history->setRenomme($renomme);
            $renomme_history->setExplication($explication);
            $renomme_history->setPersonnage($personnage);
            $personnage->addRenomme($renomme);

            $this->entityManager->persist($renomme_history);
            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modifie la liste des ressources.
     */
    #[Route('/{personnage}/updateRessource', name: 'update.ressource')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdateRessourceAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $originalPersonnageRessources = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets personnageIngredient du groupe
         */
        foreach ($personnage->getPersonnageRessources() as $personnageRessource) {
            $originalPersonnageRessources->add($personnageRessource);
        }

        $form = $this->createForm(PersonnageRessourceForm::class, $personnage);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            /*
             * Pour toutes les ressources
             */
            foreach ($personnage->getPersonnageRessources() as $personnageRessource) {
                $personnageRessource->setPersonnage($personnage);
            }

            /*
             * supprime la relation entre personnageRessource et le personnage
             */
            foreach ($originalPersonnageRessources as $personnageRessource) {
                if (false === $personnage->getPersonnageRessources()->contains($personnageRessource)) {
                    $this->entityManager->remove($personnageRessource);
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
                    $pr = new PersonnageRessource();
                    $pr->setRessource($ressource);
                    $pr->setNombre(1);
                    $pr->setPersonnage($personnage);
                    $this->entityManager->persist($pr);
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
                    $pr = new PersonnageRessource();
                    $pr->setRessource($ressource);
                    $pr->setNombre(1);
                    $pr->setPersonnage($personnage);
                    $this->entityManager->persist($pr);
                }
            }

            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'enveloppe'],
                303,
            );
        }

        return $this->render('personnage/ressources.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modifie la richesse.
     */
    #[Route('/{personnage}/updateRichesse', name: 'update.richesse')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdateRichesseAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createForm(PersonnageRichesseForm::class, $personnage);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'enveloppe'],
                303,
            );
        }

        return $this->render('personnage/richesse.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Affiche la liste des sorts pour modification.
     */
    #[Route('/{personnage}/updateSort', name: 'update.sort')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdateSortAction(
        Request $request,
        PagerService $pagerService,
        #[MapEntity] Personnage $personnage,
        SortRepository $sortRepository,
    ): Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $pagerService->setRequest($request)->setRepository($sortRepository)->setLimit(50);

        return $this->render(
            'personnage/updateSort.twig',
            [
                'personnage' => $personnage,
                'pagerService' => $pagerService,
                'paginator' => $sortRepository->searchPaginated($pagerService),
            ],
        );
    }

    /**
     * Modification des technologies d'un personnage.
     */
    #[Route('/{personnage}/updateTechnologie', name: 'update.technologie')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function adminUpdateTechnologieAction(
        Request $request,
        PagerService $pagerService,
        #[MapEntity] Personnage $personnage,
        TechnologieRepository $technologieRepository,
    ): Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $pagerService->setRequest($request)->setRepository($technologieRepository)->setLimit(50);

        $competences = $personnage->getCompetences();

        $errorLevel = 1;
        $message = $personnage->getNom()." n'est pas au moins Initié en Artisanat.";
        $limit = 1;
        foreach ($competences as $competence) {
            if (CompetenceFamilyType::CRAFTSMANSHIP->value === $competence->getCompetenceFamily(
                )?->getCompetenceFamilyType()?->value) {
                if ($competence->getLevel()?->getIndex() >= 2) {
                    $message = false;
                    $errorLevel = 0;
                }

                if (3 === $competence->getLevel()?->getIndex()) {
                    ++$limit;
                }

                if ($competence->getLevel()?->getIndex() >= 4) {
                    $limit += 1000;
                }
            }
        }

        if (count($personnage->getTechnologies()) >= $limit) {
            $errorLevel = 2;
            $message = $personnage->getNom().' connait déjà au moins '.$limit.' Technologie(s).';
        }

        return $this->render('personnage/updateTechnologie.twig', [
            'errorLevel' => $errorLevel,
            'personnage' => $personnage,
            'message' => $message,
            'pagerService' => $pagerService,
            'paginator' => $technologieRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Gestion des points d'expérience d'un personnage (pour les orgas).
     */
    #[Route('/{personnage}/xp', name: 'xp')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function adminXpAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createForm(PersonnageXpForm::class, [])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $xp = $personnage->getXp();

            $personnage->setXp($xp + $data['xp']);

            // historique
            $historique = new ExperienceGain();
            $historique->setOperationDate(new \DateTime('NOW'));
            $historique->setXpGain($data['xp']);
            $historique->setExplanation($data['explanation']);
            $historique->setPersonnage($personnage);

            $this->entityManager->persist($personnage);
            $this->entityManager->persist($historique);
            $this->entityManager->flush();

            $this->log(
                ['personnage' => $personnage->getid(), 'xp' => $data['xp'], 'explanation' => $data['explanation']],
                'xp_add',
                true,
            );

            $this->addFlash('success', 'Les points d\'expériences ont été ajoutés');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/xp.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{personnage}/apprentissage', name: 'apprentissage', requirements: ['personnage' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function apprentissageAddAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        PersonnageService $personnageService,
        PersonnageApprentissageRepository $personnageApprentissageRepository,
    ): RedirectResponse|Response {
        $availableCompetences = $personnageService->getAvailableCompetences($personnage);

        // Remove competences after Expert
        /**
         * @var Competence $competence
         */
        foreach ($availableCompetences as $key => $competence) {
            if ($competence->getLevel()?->getIndex() > Level::NIVEAU_4) {
                unset($availableCompetences[$key]);
            }
        }

        $formBuilder = $this->createFormBuilder()
            ->add('enseignant', EntityType::class, [
                'required' => true,
                'multiple' => false,
                'error_bubbling' => false,
                'autocomplete' => true,
                'label' => 'Enseignant',
                'class' => Personnage::class,
                'choice_label' => static fn(Personnage $personnage) => $personnage->getIdName(),
            ])
            ->add('competence', ChoiceType::class, [
                'required' => true,
                'multiple' => false,
                'autocomplete' => true,
                'label' => 'Compétence étudiée',
                'choices' => $availableCompetences,
                'choice_label' => static fn(Competence $competence) => $competence->getLabel(),
            ]);

        /** @var GnRepository $gnRepository */
        $gnRepository = $this->entityManager->getRepository(Gn::class);
        $gns = $gnRepository->findAll(); // ordered by date_debut

        if (empty($gns)) {
            $formBuilder->add('annee', IntegerType::class, [
                'required' => true,
                'label' => "Année de l'apprentissage",
            ]);
        } else {
            /** @var Gn $nextGn */
            $nextGn = array_shift($gns);
            /** @var ?Gn $lastGn */
            $lastGn = null;
            if (!empty($gns)) {
                /** @var Gn $gn * */
                foreach ($gns as $gn) {
                    if (!$gn->isInter()) {
                        $lastGn = $gn;
                        break;
                    }
                }
            }

            $hasApprentissage = $personnageApprentissageRepository->hasApprentissage(
                $personnage,
                $lastGn->getDateJeu(),
                $nextGn->getDateJeu(),
            );

            if ($hasApprentissage) {
                $this->addFlash(
                    'error',
                    'Le personnage a déjà fait un apprentissage sur cette période. Il doit attendre le prochain opus pour en faire un nouveau.',
                );

                return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
            }

            $endDate = $nextGn->getDateJeu();
            $startDate = $lastGn ? $lastGn->getDateJeu() : $endDate - 5;
            $dateChoices = range($startDate, $endDate);

            foreach ($dateChoices as $k => $date) {
                $valueLabel = $date;

                if ($startDate && $date === $startDate) {
                    $valueLabel = $startDate.' - '.$lastGn->getLabel();
                } elseif ($endDate && $date === $endDate) {
                    $valueLabel = $endDate.' - '.$nextGn->getLabel();
                }

                if ($valueLabel !== $k) {
                    unset($dateChoices[$k]);
                    $dateChoices[$valueLabel] = $date;
                }
            }

            $formBuilder->add('annee', ChoiceType::class, [
                'required' => true,
                'choices' => $dateChoices,
                'label' => "Année de l'apprentissage",
            ]);
        }

        $form = $formBuilder
            ->add('apprentissage', SubmitType::class, [
                'label' => 'Valider',
                'attr' => ['class' => 'btn btn-secondary'],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            /** @var Personnage $enseignant */
            $enseignant = $data['enseignant'];
            $competence = $data['competence'];
            $annee = $data['annee'];

            if (
                !$enseignant->isKnownCompetence($competence)
                || !$enseignant->hasCompetenceLevel(
                    $competence->getCompetenceFamily()?->getCompetenceFamilyType(),
                    LevelType::EXPERT,
                )
            ) {
                $form->addError(
                    new FormError(
                        sprintf(
                            "L'enseignant: %s. Ne peut pas enseigner %s. Il doit pour cela connaitre la compétence au moins au niveau expert.",
                            $enseignant->getIdName(),
                            $competence->getLabel(),
                        ),
                    ),
                );
            }

            if (!$availableCompetences->contains($competence)) {
                $form->addError(
                    new FormError(
                        sprintf(
                            'La compétence %s ne fait pas partie des compétences actuellement accessibles pour le personnage %s',
                            $competence->getLabel(),
                            $personnage->getIdName(),
                        ),
                    ),
                );
            }

            if ($form->isValid()) {
                $personnageApprentissage = new PersonnageApprentissage();
                $personnageApprentissage->setPersonnage($personnage);
                $personnageApprentissage->setEnseignant($enseignant);
                $personnageApprentissage->setCompetence($competence);
                $personnageApprentissage->setCreatedAt(new \DateTime());
                $personnageApprentissage->setDateEnseignement($annee);

                $logAction = new LogAction();
                $logAction->setUser($this->getUser());
                $logAction->setDate(new \DateTime());
                $logAction->setType('Apprentissage');
                $logAction->setData([...$personnageApprentissage->toLog(), 'competence_id' => $competence->getId()]);

                $this->entityManager->persist($logAction);
                $this->entityManager->persist($personnageApprentissage);
                $this->entityManager->flush();

                $this->addFlash('success', "l'apprentissage a été ajouté");

                return $this->redirectToRoute(
                    'personnage.detail.tab',
                    ['personnage' => $personnage->getId(), 'tab' => 'competences'],
                    303,
                );
            }
        }

        return $this->render('personnage/apprentissage.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ], new Response(null, !$form->isSubmitted() || $form->isValid() ? 200 : 422));
    }

    #[Route('/{personnage}/apprentissage/{apprentissage}/delete', name: 'apprentissage.delete', requirements: [
        'personnage' => Requirement::DIGITS,
        'apprentissage' => Requirement::DIGITS,
    ])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function apprentissageDeleteAction(
        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnageApprentissage $apprentissage,
    ): RedirectResponse|Response {
        if ($apprentissage->getDateUsage()) {
            $this->addFlash('error', 'Attention cet apprentissage a déjà été utilisé pour apprendre la compétence');
        }

        return $this->genericDelete(
            $apprentissage,
            'Supprimer un apprentissage',
            "L'apprentissage a été supprimé",
            $this->generateUrl('personnage.detail.tab', ['personnage' => $personnage->getId(), 'tab' => 'competences']),
            [
                // it's an admin view, do not need to test role for this breadcrumb
                ['route' => $this->generateUrl('personnage.list'), 'name' => 'Liste des personnages'],
                [
                    'route' => $this->generateUrl('personnage.detail', ['personnage' => $personnage->getId()]),
                    'name' => $personnage->getPublicName(),
                ],
                ['name' => 'Supprimer un apprentissage'],
            ],
        );
    }

    #[Route('/{personnage}/apprentissage/{apprentissage}/detail', name: 'apprentissage.detail', requirements: [
        'personnage' => Requirement::DIGITS,
        'apprentissage' => Requirement::DIGITS,
    ])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function apprentissageDetailAction(
        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnageApprentissage $apprentissage,
    ): RedirectResponse|Response {
        return $this->render(
            'personnage/apprentissage_detail.twig',
            [
                'personnage' => $personnage,
                'apprentissage' => $apprentissage,
            ],
        );
    }

    #[Route('/{personnage}/detail/{tab}', name: 'detail.tab', requirements: [
        'personnage' => Requirement::DIGITS,
        'tab' => Requirement::ASCII_SLUG,
    ])]
    #[Route('/{personnage}', name: 'detail', requirements: ['personnage' => Requirement::DIGITS])]
    #[Route('/{personnage}/detail', name: 'detailAlias', requirements: ['personnage' => Requirement::DIGITS])]
    #[IsGranted(Role::USER->value)]
    public function detailAction(
        #[MapEntity] Personnage $personnage,
        string $tab = 'general',
    ): Response {
        // Fiche publique: Disabled for now
        // $isAdmin = $this->isGranted(Role::SCENARISTE->value) || $this->isGranted(Role::ORGA->value);
        /*if (!$isAdmin && $personnage->getUser()?->getId() !== $this->getUser()?->getId()) {
            return $this->render(
                'personnage/publicResume.twig',
                [
                    'personnage' => $personnage,
                    'participant' => $personnage->getLastParticipant(),
                ]
            );
        }*/

        // Fiche pour le PJ ou admin
        $this->hasAccess($personnage, [Role::SCENARISTE, Role::ORGA]);

        // Ensure human
        if (!$personnage->getEspeces()) {
            $personnage->addEspece($this->personnageService->getHumanEspece());
            $this->entityManager->persist($personnage);
            $this->entityManager->flush();
        }

        $descendants = $this->entityManager->getRepository(Personnage::class)->findDescendants($personnage);
        if (!$this->twig->getLoader()->exists('personnage/fragment/tab_'.$tab.'.twig')) {
            $tab = 'general';
        }
        if (!$this->can(self::IS_ADMIN) && 'enveloppe' === $tab) {
            $tab = 'general';
        }

        return $this->render(
            'personnage/detail.twig',
            [
                'personnage' => $personnage,
                'descendants' => $descendants,
                'langueMateriel' => $personnage->getLangueMateriel(),
                'participant' => $personnage->getLastParticipant(),
                'tab' => $tab,
            ],
        );
    }

    /**
     * Gestion des documents liés à un personnage.
     */
    #[Route('/{personnage}/documents', name: 'documents')]
    #[IsGranted(Role::SCENARISTE->value)]
    public function documentAction(
        Request $request,

        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createForm(PersonnageDocumentForm::class, $personnage)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer', 'attr' => ['class' => 'btn-secondary']]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le document a été ajouté au personnage.');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'enveloppe'],
                303,
            );
        }

        return $this->render('personnage/documents.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{personnage}/enveloppe/print', name: 'enveloppe.print')]
    #[IsGranted(Role::ORGA->value)]
    public function enveloppePrintAction(#[MapEntity] Personnage $personnage): Response
    {
        return $this->render('personnage/enveloppe.twig', [
            'personnage' => $personnage,
            'langueMateriel' => $personnage->getLangueMateriel(),
        ]);
    }

    /**
     * Exporte la fiche d'un personnage.
     */
    #[Route('/{personnage}/export', name: 'export')]
    public function exportAction(#[MapEntity] Personnage $personnage,
    ): Response {
        $participant = $personnage->getParticipants()->last();
        $groupe = null;

        if ($participant && $participant->getGroupeGn()) {
            $groupe = $participant->getGroupeGn()->getGroupe();
        }

        return $this->render('personnage/print.twig', [
            'personnage' => $personnage,
            'participant' => $participant,
            'langueMateriel' => $personnage->getLangueMateriel(),
            'groupe' => $groupe,
        ]);
    }

    #[Route('/fixpriest', name: 'fix.priest')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function fixAction(
        PersonnageRepository $pr,
    ): RedirectResponse {
        $ids = [
            // List of priest with no prayer
        ];

        /** @var Personnage $personnage */
        foreach ($pr->findByIds($ids) as $personnage) {
            $religion = $personnage?->getMainReligion();

            if (!$religion) {
                continue;
            }

            foreach ($religion->getSpheres() as $sphere) {
                /** @var Priere $priere */
                foreach ($sphere->getPrieres() as $priere) {
                    if (!$personnage?->hasPriere($priere) && 1 === $priere->getNiveau()) {
                        $priere->addPersonnage($personnage);
                        $personnage?->addPriere($priere);
                        $this->entityManager->persist($personnage);
                        $this->entityManager->persist($priere);
                    }
                }
                $this->entityManager->flush();
            }
        }

        return $this->render('personnage/list.twig', []);
    }

    /**
     * Obtenir une image protégée.
     */
    #[Route('/{personnage}/trombine', name: 'trombine')]
    public function getTrombineAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): Response {
        // PROD path https://larpmanager.eveoniris.com/ => ???
        // PROD lARPV2 https://larpm.eveoniris.com/ => ???
        $miniature = $request->get('miniature');
        $projectDir = $this->fileUploader->getProjectDirectory();

        $filename = $personnage->getTrombine($projectDir);
        if (!file_exists($filename)) {
            // get old ?
            $paths = [
                'v2' => $projectDir.FolderType::Private->value.DocumentType::Image->value.'/',
                'v1' => $projectDir.'/../larpmanager/private/img/',
                'last' => $projectDir.'/../larpm/private/img/',
            ];

            foreach ($paths as $type => $path) {
                $filename = $path.$personnage->getTrombineUrl();
                if (file_exists($filename)) {
                    break;
                }
                if ('last' === $type) {
                    return $this->sendNoImageAvailable($filename);
                }
            }
        }

        if ($miniature) {
            try {
                $image = (new Imagine())->open($filename);
            } catch (\RuntimeException $e) {
                return $this->sendNoImageAvailable();
            }

            $response = new StreamedResponse();
            $response->headers->set('Content-Control', 'private');
            $response->headers->set('Content-Type', 'image/jpeg');
            $response->setCallback(static function () use ($image): void {
                echo $image->thumbnail(new Box(200, 200))->get('jpeg');
                flush();
            });
        } else {
            $response = new BinaryFileResponse($filename);
            $response->headers->set('Content-Control', 'private');

            $ext = strtolower($personnage->getTrombineUrl());
            if (str_ends_with($ext, '.png')) {
                $response->headers->set('Content-Type', 'image/png');
            }
            if (str_ends_with($ext, '.gif')) {
                $response->headers->set('Content-Type', 'image/gif');
            }
            $response->headers->set('Content-Type', 'image/jpeg');
        }

        return $response->send();
        /* OLD
        $trombine = $personnage->getTrombineUrl();
        $filename = $this->fileUploader->getDirectory(FolderType::Photos).$trombine;

        // Larpv1 temp
        if (!is_file($filename)) {
            $filename = $this->fileUploader->getDirectory(FolderType::Asset).'../../larpmanager/private/img/'.$trombine;
        }

        if (!is_file($filename)) {
            return $this->sendNoImageAvailable();
        }

        $response = new Response(file_get_contents($filename));
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('cache-control', 'private');

        return $response; */
    }

    /**
     * Gestion des objets lié à un personnage.
     */
    #[Route('/{personnage}/items', name: 'items')]
    public function itemAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createForm(PersonnageItemForm::class, $personnage)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'objet a été ajouté au personnage.');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'enveloppe'],
                303,
            );
        }

        return $this->render('personnage/items.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{personnage}/item/{item}', name: 'item.detail')]
    public function itemDetailAction(
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Item $item,
    ): RedirectResponse|Response {
        // TODO IS RITUALIST LIMIT
        $canSee = $personnage->hasCompetenceLevel(
            CompetenceFamilyType::RITUALISM,
            LevelType::INITIATED,
        );

        if ($canSee || !$personnage->isKnownItem($item)) {
            $this->addFlash('error', 'Désolé, vous ne pouvez pas consulter cet objet.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/item.twig', [
            'personnage' => $personnage,
            'item' => $item,
        ]);
    }

    /**
     * Liste des personnages.
     */
    #[Route('/list', name: 'list')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function listAction(Request $request): Response
    {
        // handle the request and return an array containing the parameters for the view
        $viewParams = $this->getSearchViewParameters($request, 'personnage.list');

        return $this->render('personnage/list.twig', $viewParams);
    }

    /**
     * Retourne le tableau de paramètres à utiliser pour l'affichage de la recherche des personnages.
     */
    public function getSearchViewParameters(
        Request $request,
        string $routeName,
        array $columnKeys = [],
    ): array {
        // récupère les filtres et tris de recherche + pagination renseignés dans le formulaire
        $orderBy = $request->get('order_by') ?: 'id';
        $orderDir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $isAsc = 'ASC' == $orderDir;
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $formData = $request->query->all('personnage_find_form');
        $religion = isset($formData['religion']) ? $this->entityManager->find(
            'App\Entity\Religion',
            $formData['religion'],
        ) : null;
        $competence = isset($formData['competence']) ? $this->entityManager->find(
            'App\Entity\Competence',
            $formData['competence'],
        ) : null;
        $classe = isset($formData['classe']) ? $this->entityManager->find(
            'App\Entity\Classe',
            $formData['classe'],
        ) : null;
        $groupe = isset($formData['groupe']) ? $this->entityManager->find(
            'App\Entity\Groupe',
            $formData['groupe'],
        ) : null;
        $optionalParameters = '';

        // construit le formulaire contenant les filtres de recherche
        $form = $this->createForm(
            PersonnageFindForm::class,
            null,
            [
                'data' => [
                    'religion' => $religion,
                    'classe' => $classe,
                    'competence' => $competence,
                    'groupe' => $groupe,
                ],
                'method' => 'get',
                'csrf_protection' => false,
            ],
        );

        $form->handleRequest($request);

        // récupère les nouveaux filtres de recherche
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            $criteria[$type] = $value;
        }
        if ($religion) {
            $criteria['religion'] = $religion->getId();
            $optionalParameters .= "&personnageFind[religion]={$religion->getId()}";
        }
        if ($competence) {
            $criteria['competence'] = $competence->getId();
            $optionalParameters .= "&personnageFind[competence]={$competence->getId()}";
        }
        if ($classe) {
            $criteria['classe'] = $classe->getId();
            $optionalParameters .= "&personnageFind[classe]={$classe->getId()}";
        }
        if ($groupe) {
            $criteria['groupe'] = $groupe->getId();
            $optionalParameters .= "&personnageFind[groupe]={$groupe->getId()}";
        }

        $repo = $this->entityManager->getRepository(Personnage::class);

        // attention, il y a des propriétés sur lesquelles on ne peut pas appliquer le order by
        // car elles ne sont pas en base mais calculées, ça compliquerait trop le sql
        $orderByCalculatedFields = new ArrayCollection(['pugilat', 'heroisme', 'user', 'hasAnomalie', 'status']);
        // TODO ?
        if (false && $orderByCalculatedFields->contains($orderBy)) {
            // recherche basée uniquement sur les filtres
            $filteredPersonnages = $repo->findList($criteria)->getResult();
            // on applique le tri
            PersonnageManager::sort($filteredPersonnages, $orderBy, $isAsc);
            $personnages = new ArrayCollection($filteredPersonnages);
            // on découpe suivant la pagination demandée
            $personnages = $personnages->slice($offset, $limit);

            $paginator = new Paginator($personnages);
        }
        // else {
        // recherche et applique directement en sql filtres + tri + pagination
        $personnages = $repo->findList(
            $criteria,
            ['by' => $orderBy, 'dir' => $orderDir],
            $limit,
            $offset,
        );

        $paginator = $repo->findPaginatedQuery(
            $personnages,
            $this->getRequestLimit(),
            $this->getRequestPage(),
        );
        //  }

        // récupère les colonnes à afficher
        if (empty($columnKeys)) {
            // on prend l'ordre par défaut
            $columnDefinitions = $this->columnDefinitions;
        } else {
            // on reconstruit le tableau dans l'ordre demandé
            $columnDefinitions = [];
            foreach ($columnKeys as $columnKey) {
                if (array_key_exists($columnKey, $this->columnDefinitions)) {
                    $columnDefinitions[] = $this->columnDefinitions[$columnKey];
                }
            }
        }

        return array_merge([
            'personnages' => $personnages,
            'paginator' => $paginator,
            'form' => $form->createView(),
            'optionalParameters' => $optionalParameters,
            'columnDefinitions' => $columnDefinitions,
            'formPath' => $routeName,
        ],
        );
    }

    #[Route('/{personnage}/magie', name: 'magie')]
    public function magieAction(
        #[MapEntity] Personnage $personnage,
        DomaineRepository $domaineRepository,
    ): RedirectResponse|Response {
        return $this->render('personnage/magie.twig', [
            'domaines' => $domaineRepository->findAll(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Création d'un nouveau personnage.
     */
    #[Route('/{personnage}/add', name: 'add')]
    // TODO Ou est-ce utilisé ?
    public function newAction(
        Request $request,
    ): RedirectResponse|Response {
        $personnage = new Personnage();

        $form = $this->createForm(PersonnageForm::class, $personnage)
            ->add('valider', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $this->getUser()->addPersonnage($personnage);
            $this->entityManager->persist($this->getUser());
            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $app['personnage.manager']->setCurrentPersonnage($personnage);
            $this->addFlash('success', 'Votre personnage a été créé');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('personnage/new.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{personnage}/religion/description', name: 'religion.description')]
    public function religionDescriptionAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        if (!$personnage->hasTrigger(TriggerType::PRETRISE_INITIE)) {
            $this->addFlash('error', 'Désolé, vous ne pouvez pas choisir de descriptif de religion supplémentaire.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        $availableDescriptionReligion = $this->personnageService->getAvailableDescriptionReligion($personnage);

        $form = $this->createFormBuilder()
            ->add('religion', ChoiceType::class, [
                'required' => true,
                'label' => 'Choisissez votre nouveau descriptif religion',
                'multiple' => false,
                'expanded' => true,
                'choices' => $availableDescriptionReligion,
                'choice_label' => 'label',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Valider',
                'attr' => [
                    'class' => 'btn btn-secondary',
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $religion = $data['religion'];

            $personnage->addReligion($religion);

            if ($trigger = $personnage->getTrigger(TriggerType::PRETRISE_INITIE)) {
                $this->entityManager->remove($trigger);
            }
            $this->entityManager->flush();

            $this->addFlash('success', 'Vos modifications ont été enregistrées.');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'competences'],
                303,
            );
        }

        return $this->render('personnage/descriptionReligion.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Retire la dernière compétence acquise par un personnage.
     */
    #[Route('/{personnage}/deleteCompetence', name: 'delete.competence')]
    #[IsGranted(Role::SCENARISTE->value)]
    public function removeCompetenceAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        PersonnageService $personnageService,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $lastCompetence = $personnageService->getLastCompetence($personnage);

        if (!$lastCompetence) {
            $this->addFlash('error', 'Désolé, le personnage n\'a pas encore acquis de compétences');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'competences'],
                303,
            );
        }

        $competence = new Competence();
        $form = $this->createFormBuilder($competence)
            ->add(
                'save',
                SubmitType::class,
                [
                    'label' => 'Retirer la compétence',
                    'attr' => [
                        'class' => 'btn btn-secondary',
                    ],
                ],
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnageService->removeCompetence($personnage, $lastCompetence);

            $this->addFlash('success', 'La compétence a été retirée');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'competences'],
                303,
            );
        }

        return $this->render('personnage/removeCompetence.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'competence' => $lastCompetence,
        ]);
    }

    /**
     * Selection du personnage courant.
     */
    // TODO permet à un USER de choisir son personnage ACTIF
    public function selectAction(
        Request $request,

        Personnage $personnage,
    ): RedirectResponse {
        $app['personnage.manager']->setCurrentPersonnage($personnage->getId());

        return $this->redirectToRoute('homepage', [], 303);
    }

    #[Route('/{personnage}/competence/test', name: 'test.competence')]
    #[IsGranted(Role::ADMIN->value)]
    public function testAction(
        Request $request,
        PersonnageService $personnageService,
        CompetenceService $competenceService,
        #[MapEntity] Personnage $personnage,
        CompetenceRepository $competenceRepository,
        TerritoireRepository $territoireRepository,
    ) {
        $protectionApprenti = $competenceRepository->findOneBy(['id' => 117]);
        $ressistanceApp = $competenceRepository->findOneBy(['id' => 122]);
        $agiliteApprenti = $competenceRepository->findOneBy(['id' => 1]);
        $richesseApprenti = $competenceRepository->findOneBy(['id' => 127]);

        $data = '<br />Richesse<br />';
        $data .= $personnageService->getCompetenceCout($personnage, $richesseApprenti);

        $data .= '<br />Resistance<br />';
        $data .= $personnageService->getCompetenceCout($personnage, $ressistanceApp);

        $data .= '<br />Agilite<br />';
        $data .= $personnageService->getCompetenceCout($personnage, $agiliteApprenti);

        $data .= '<br />Protection<br />';
        $data .= $personnageService->getCompetenceCout($personnage, $agiliteApprenti);

        $a = [
            'min' => 1,
            'condition' => [
                [
                    'type' => 'COMPETENCE_FAMILLE',
                    'value' => 'PROTECTION',
                ],
            ],
        ];

        /* dump(json_encode($a));
         dump(
             $competenceService
                 ->setPersonnage($personnage)
                 ->setCompetence($protectionApprenti)
                 ->getCompetenceCout(),
         );*/

        return $this->render('personnage/test.twig', [
            'personnage' => $personnage,
            'data' => $data,
        ]);
    }

    /**
     * Transfert d'un personnage à un autre utilisateur.
     */
    #[Route('/{personnage}/transfert', name: 'transfert')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function transfertAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        /*
        if (!$oldParticipant = $personnage->getLastParticipant()) {
            $this->addFlash(
                'error',
                'Désolé, le personnage ne dispose pas encore de participation et ne peut donc pas encore être transféré'
            );

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }*/

        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createFormBuilder()
            ->add('participant', EntityType::class, [
                'required' => true,
                // 'expanded' => true,
                'multiple' => false,
                'autocomplete' => true,
                'label' => 'Nouveau propriétaire',
                'help' => 'Il doit avoir une participation, et ne pas avoir de personnage associé à celle-ci',
                'class' => Participant::class,
                'choice_label' => static fn(Participant $participant) => $participant->getGn()->getLabel(
                    ).' - '.$participant->getUser()?->getFullname(),
                'query_builder' => static fn(ParticipantRepository $pr) => $pr->createQueryBuilder('prt')
                    ->select('prt')
                    ->innerJoin('prt.user', 'u')
                    ->innerJoin('prt.gn', 'gn')
                    ->innerJoin('u.etatCivil', 'ec')
                    ->andWhere('prt.personnage IS NULL AND prt.user IS NOT NULL')
                    ->orderBy('gn.id', 'DESC')
                    ->addOrderBy('ec.nom', 'ASC')
                    ->addOrderBy('ec.prenom_usage', 'ASC'),
            ])
            ->add('transfert', SubmitType::class, [
                'label' => 'Transferer',
                'attr' => ['class' => 'btn btn-secondary'],
            ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $newParticipant = $data['participant'];

            $personnage->setUser($newParticipant?->getUser());

            // gestion de l'ancien personnage
            if ($newParticipant->getPersonnage()) {
                $oldPersonnage = $newParticipant->getPersonnage();
                $oldPersonnage->removeParticipant($newParticipant);
                $oldPersonnage->setGroupeNull();
            }

            // le personnage doit rejoindre le groupe de l'utilisateur
            if ($newParticipant->getGroupeGn() && $newParticipant->getGroupeGn()->getGroupe()) {
                $personnage->setGroupe($newParticipant->getGroupeGn()->getGroupe());
            }

            if ($oldParticipant = $personnage->getLastParticipant()) {
                $oldParticipant->setPersonnageNull();
                $oldParticipant->getUser()?->setPersonnage(null);
                $this->entityManager->persist($oldParticipant);
            }

            $newParticipant->setPersonnage($personnage);
            $newParticipant->getUser()->setPersonnage($personnage);
            $personnage->addParticipant($newParticipant);
            $personnage->setUser($newParticipant->getUser());

            // Check que le groupe de destination n'est pas lock aussi
            $this->checkPersonnageGroupeLock($personnage, $newParticipant);

            $this->entityManager->persist($newParticipant);
            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été transféré');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/transfert.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un trigger.
     */
    #[Route('/{personnage}/trigger/{trigger}/delete', name: 'trigger.delete', requirements: [
        'personnage' => Requirement::DIGITS,
        'trigger' => Requirement::DIGITS,
    ])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function triggerDeleteAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnageTrigger $trigger,
    ): RedirectResponse|Response {
        $form = $this->createForm(TriggerDeleteForm::class, $trigger)
            ->add('save', SubmitType::class, ['label' => 'Valider les modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trigger = $form->getData();

            $this->entityManager->remove($trigger);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le déclencheur a été supprimé.');

            return $this->redirectToRoute(
                'personnage.detail.tab',
                ['personnage' => $personnage->getId(), 'tab' => 'competences'],
                303,
            );
        }

        return $this->render('personnage/deleteTrigger.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'trigger' => $trigger,
        ]);
    }

    /**
     * Dé-Selection du personnage courant.
     */
    // TODO ?
    public function unselectAction(Request $request): RedirectResponse
    {
        $app['personnage.manager']->resetCurrentPersonnage();

        return $this->redirectToRoute('homepage', [], 303);
    }

    /**
     * Modification du personnage.
     */
    #[Route('/{personnage}/update', name: 'update', requirements: ['personnage' => Requirement::DIGITS])]
    #[IsGranted(Role::USER->value)]
    public function updateAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $this->hasAccess($personnage, [Role::SCENARISTE, Role::ORGA]);

        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $form = $this->createForm(PersonnageUpdateForm::class, $personnage)
            ->add('save', SubmitType::class, [
                'label' => 'Valider les modifications',
                'attr' => [
                    'class' => 'btn btn-secondary',
                ],
            ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Personnage $personnage */
            $personnage = $form->getData();

            // enforce to false
            if (!$personnage->isBracelet()) {
                $personnage->setBracelet(false);
            }

            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    #[Route('/{personnage}/espece', name: 'espece')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function updateEspecesAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $especes = $this->entityManager->getRepository(Espece::class)->findBy([],
            ['nom' => 'ASC']);

        $originalEspeces = [];
        foreach ($personnage->getEspeces() as $espece) {
            $originalEspeces[] = $espece;
        }

        $form = $this->createFormBuilder()
            ->add('especes', EntityType::class, [
                'required' => true,
                'label' => 'Choisissez les espèces du personnage',
                'multiple' => true,
                'expanded' => true,
                'class' => Espece::class,
                'choices' => $especes,
                'label_html' => true,
                'choice_label' => static fn(Espece $espece) => ($espece->isSecret(
                    ) ? '<i class="fa fa-user-secret text-warning"></i> secret - ' : '').$espece->getNom(),
                'data' => $originalEspeces,
            ])
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider vos modifications', 'attr' => ['class' => 'btn btn-secondary']],
            )
            ->getForm();

        $form->handleRequest($request);

        /* @var Espece $espece * */
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $especesChanges = $data['especes'];

            // pour toutes les nouvelles espèces
            foreach ($especesChanges as $espece) {
                if (!$personnage->getEspeces()->contains($espece)) {
                    $personnage->addEspece($espece);
                }
            }

            if (0 === count($especesChanges)) {
                foreach ($personnage->getEspeces() as $espece) {
                    $personnage->removeEspece($espece);
                }
            } else {
                foreach ($originalEspeces as $espece) {
                    $found = false;

                    /** @var Espece $especeChange */
                    foreach ($especesChanges as $especeChange) {
                        if ($espece->getId() === $especeChange->getId()) {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $personnage->removeEspece($espece);
                    }
                }
            }

            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modifie la liste des langues.
     */
    #[Route('/{personnage}/updateLangue', name: 'update.langue')]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::ORGA))]
    public function updateLangueAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $participant = $this->getParticipant($personnage, $request);
        $this->checkPersonnageGroupeLock($personnage, $participant);

        $langues = $this->entityManager->getRepository(Langue::class)->findBy([],
            ['secret' => 'ASC', 'diffusion' => 'DESC', 'label' => 'ASC']);

        $originalLanguages = [];
        foreach ($personnage->getLanguages() as $languages) {
            $originalLanguages[] = $languages;
        }

        $form = $this->createFormBuilder()
            ->add('langues', EntityType::class, [
                'required' => true,
                'label' => 'Choisissez les langues du personnage',
                'multiple' => true,
                'expanded' => true,
                'class' => Langue::class,
                'choices' => $langues,
                'choice_label' => 'label',
                'data' => $originalLanguages,
            ])
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider vos modifications', 'attr' => ['class' => 'btn btn-secondary']],
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $langues = $data['langues'];

            // pour toutes les nouvelles langues
            foreach ($langues as $langue) {
                if (!$personnage->isKnownLanguage($langue)) {
                    $personnageLangue = new PersonnageLangues();
                    $personnageLangue->setPersonnage($personnage);
                    $personnageLangue->setLangue($langue);
                    $personnageLangue->setSource('ADMIN');
                    $this->entityManager->persist($personnageLangue);
                }
            }

            if (0 === count($langues)) {
                foreach ($personnage->getLanguages() as $langue) {
                    $personnageLangue = $personnage->getPersonnageLangue($langue);
                    $this->entityManager->remove($personnageLangue);
                }
            } else {
                foreach ($personnage->getLanguages() as $langue) {
                    $found = false;
                    foreach ($langues as $l) {
                        if ($l === $langue) {
                            $found = true;
                        }
                    }

                    if (!$found) {
                        $personnageLangue = $personnage->getPersonnageLangue($langue);
                        $this->entityManager->remove($personnageLangue);
                    }
                }
            }

            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Mise à jour de la photo.
     */
    #[Route('/{personnage}/trombine/update', name: 'trombine.update')]
    #[IsGranted('ROLE_USER')]
    public function updateTrombineAction(
        Request $request,

        Personnage $personnage,
    ): RedirectResponse|Response {
        /** @var User $user */
        $user = $this->getUser();

        // Admin or self USER
        if (!$user) {
            $this->addFlash('error', 'Désolé, vous devez être identifié pour accéder à cette page');
            $this->redirectToRoute('app_login', [], 303);
        }

        if (
            !($this->isGranted('ROLE_ADMIN') || $this->isGranted(Role::SCENARISTE->value))
            && $user->getId() !== $personnage->getUser()?->getId()
        ) {
            $this->addFlash('error', "Vous n'avez pas les permissions requises pour modifier une trombine");
            $this->redirect($request->headers->get('referer'));
            $this->redirectToRoute('homepage', [], 303);
        }

        $form = $this->createForm(TrombineForm::class, $personnage)
            ->add('envoyer', SubmitType::class, ['label' => 'Envoyer', 'attr' => ['class' => 'btn btn-secondary']]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Personnage $personnage */
            $personnage = $form->getData();
            $personnage->handleUpload($this->fileUploader);

            // todo can be too long dd($personnage->getTrombineUrl());
            $this->entityManager->persist($personnage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre photo a été enregistrée');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/trombine.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/vieillir', name: 'vieillir')]
    public function vieillirAction(Request $request): RedirectResponse|Response
    {
        $form = $this->createForm()
            ->add(
                'valider',
                SubmitType::class,
                ['label' => 'Faire vieillir tous les personnages', 'attr' => ['class' => 'btn-danger']],
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnages = $this->entityManager->getRepository('\\'.Personnage::class)->findAll();
            $token = $this->entityManager->getRepository('\\'.Token::class)->findOneByTag('VIEILLESSE');
            $ages = $this->entityManager->getRepository('\\'.Age::class)->findAll();

            if (!$token) {
                $this->addFlash('error', "Le jeton VIEILLESSE n'existe pas !");

                return $this->redirectToRoute('homepage', [], 303);
            }

            foreach ($personnages as $personnage) {
                // donne un jeton vieillesse
                $personnageHasToken = new PersonnageHasToken();
                $personnageHasToken->setToken($token);
                $personnageHasToken->setPersonnage($personnage);
                $personnage->addPersonnageHasToken($personnageHasToken);
                $this->entityManager->persist($personnageHasToken);

                if ($personnage->getVivant()) {
                    $personnage->setAgeReel($personnage->getAgeReel() + 5); // ajoute 5 ans à l'age réél
                }

                if (0 == $personnage->getPersonnageHasTokens()->count() % 2 && $personnage->getVivant()) {
                    if ($personnage->getAge()->getId() < 5) {
                        $personnage->setAge($ages[$personnage->getAge()->getId()]);
                    } elseif (5 == $personnage->getAge()->getId()) {
                        $personnage->setVivant(false);
                        foreach ($personnage->getParticipants() as $participant) {
                            if (null != $participant->getGn()) {
                                $anneeGN = $participant->getGn()->getDateJeu() + rand(1, 4);
                            }
                        }

                        $evenement = 'Mort de vieillesse';
                        $personnageChronologie = new PersonnageChronologie();
                        $personnageChronologie->setAnnee($anneeGN);
                        $personnageChronologie->setEvenement($evenement);
                        $personnageChronologie->setPersonnage($personnage);
                        $this->entityManager->persist($personnageChronologie);
                    }
                }

                $this->entityManager->persist($personnage);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Tous les personnages ont reçu un jeton vieillesse.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('personnage/vieillir.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @return mixed[]
     */
    private function getErrorMessages(Form $form): array
    {
        $errors = [];

        foreach ($form->getErrors() as $key => $error) {
            if ($form->isRoot()) {
                $errors['#'][] = $error->getMessage();
            } else {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }
}
