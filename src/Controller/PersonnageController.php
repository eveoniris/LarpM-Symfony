<?php

namespace App\Controller;

use App\Entity\Age;
use App\Entity\Background;
use App\Entity\Classe;
use App\Entity\Competence;
use App\Entity\Connaissance;
use App\Entity\Domaine;
use App\Entity\ExperienceGain;
use App\Entity\HeroismeHistory;
use App\Entity\Ingredient;
use App\Entity\Langue;
use App\Entity\Participant;
use App\Entity\Personnage;
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
use App\Entity\Religion;
use App\Entity\RenommeHistory;
use App\Entity\Ressource;
use App\Entity\Sort;
use App\Entity\Technologie;
use App\Entity\Token;
use App\Entity\User;
use App\Enum\CompetenceFamilyType;
use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Enum\Role;
use App\Form\Personnage\PersonnageChronologieForm;
use App\Form\Personnage\PersonnageDocumentForm;
use App\Form\Personnage\PersonnageEditForm;
use App\Form\Personnage\PersonnageIngredientForm;
use App\Form\Personnage\PersonnageItemForm;
use App\Form\Personnage\PersonnageLigneeForm;
use App\Form\Personnage\PersonnageOriginForm;
use App\Form\Personnage\PersonnageReligionForm;
use App\Form\Personnage\PersonnageRessourceForm;
use App\Form\Personnage\PersonnageRichesseForm;
use App\Form\Personnage\PersonnageTechnologieForm;
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
use App\Form\PotionFindForm;
use App\Form\PriereFindForm;
use App\Form\SortFindForm;
use App\Form\TriggerDeleteForm;
use App\Form\TriggerForm;
use App\Form\TrombineForm;
use App\Manager\GroupeManager;
use App\Manager\PersonnageManager;
use App\Repository\ConnaissanceRepository;
use App\Repository\DomaineRepository;
use App\Repository\PotionRepository;
use App\Repository\PriereRepository;
use App\Repository\SortRepository;
use App\Repository\TechnologieRepository;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[Route('/personnage', name: 'personnage.')]
class PersonnageController extends AbstractController
{
    // contient la liste des colonnes
    protected $columnDefinitions = [
        'colId' => ['label' => '#', 'fieldName' => 'id', 'sortFieldName' => 'id', 'tooltip' => 'Numéro d\'identifiant'],
        'colStatut' => ['label' => 'S', 'fieldName' => 'status', 'sortFieldName' => 'status', 'tooltip' => 'Statut'],
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
        ],
        'colPugilat' => [
            'label' => 'Pugilat',
            'fieldName' => 'pugilat',
            'sortFieldName' => 'pugilat',
            'tooltip' => 'Points de pugilat',
        ],
        'colHeroisme' => [
            'label' => 'Héroïsme',
            'fieldName' => 'heroisme',
            'sortFieldName' => 'heroisme',
            'tooltip' => 'Points d\'héroisme',
        ],
        'colUser' => [
            'label' => 'Utilisateur',
            'fieldName' => 'user',
            'sortFieldName' => 'user',
            'tooltip' => 'Liste des utilisateurs (Nom et prénom) par GN',
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
        ],
    ];

    /**
     * Retourne le tableau de paramètres à utiliser pour l'affichage de la recherche des personnages.
     */
    public function getSearchViewParameters(
        Request $request,
        EntityManagerInterface $entityManager,
        string $routeName,
    ): array {
        // récupère les filtres et tris de recherche + pagination renseignés dans le formulaire
        $orderBy = $request->get('order_by') ?: 'id';
        $orderDir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $isAsc = 'ASC' == $orderDir;
        $limit = (int)($request->get('limit') ?: 50);
        $page = (int)($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $formData = $request->query->all('personnage_find_form');
        $religion = isset($formData['religion']) ? $entityManager->find(
            'App\Entity\Religion',
            $formData['religion']
        ) : null;
        $competence = isset($formData['competence']) ? $entityManager->find(
            'App\Entity\Competence',
            $formData['competence']
        ) : null;
        $classe = isset($formData['classe']) ? $entityManager->find('App\Entity\Classe', $formData['classe']) : null;
        $groupe = isset($formData['groupe']) ? $entityManager->find('App\Entity\Groupe', $formData['groupe']) : null;
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
            ]
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

        $repo = $entityManager->getRepository(Personnage::class);

        // attention, il y a des propriétés sur lesquelles on ne peut pas appliquer le order by
        // car elles ne sont pas en base mais calculées, ça compliquerait trop le sql
        $orderByCalculatedFields = new ArrayCollection(['pugilat', 'heroisme', 'user', 'hasAnomalie', 'status']);
        if ($orderByCalculatedFields->contains($orderBy)) {
            // recherche basée uniquement sur les filtres
            $filteredPersonnages = $repo->findList($criteria)->getResult();
            // on applique le tri
            PersonnageManager::sort($filteredPersonnages, $orderBy, $isAsc);
            $personnagesCollection = new ArrayCollection($filteredPersonnages);
            // on découpe suivant la pagination demandée
            $personnages = $personnagesCollection->slice($offset, $limit);
        } else {
            // recherche et applique directement en sql filtres + tri + pagination
            $personnages = $repo->findList(
                $criteria,
                ['by' => $orderBy, 'dir' => $orderDir],
                $limit,
                $offset
            );
        }

        $paginator = $repo->findPaginatedQuery(
            $personnages,
            $this->getRequestLimit(),
            $this->getRequestPage()
        );

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
            ]
        );
    }

    /**
     * Selection du personnage courant.
     */
    public function selectAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Personnage $personnage,
    ): RedirectResponse {
        $app['personnage.manager']->setCurrentPersonnage($personnage->getId());

        return $this->redirectToRoute('homepage', [], 303);
    }

    /**
     * Dé-Selection du personnage courant.
     */
    public function unselectAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $app['personnage.manager']->resetCurrentPersonnage();

        return $this->redirectToRoute('homepage', [], 303);
    }

    /**
     * Obtenir une image protégée.
     */
    #[Route('/{personnage}/trombine', name: 'trombine')]
    public function getTrombineAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): Response {
        // PROD path https://larpmanager.eveoniris.com/ => ???
        // PROD lARPV2 https://larpm.eveoniris.com/ => ???
        $miniature = $request->get('miniature');

        $trombine = $personnage->getTrombine($this->fileUploader->getProjectDirectory());
        if (!$trombine) {
            return $this->sendNoImageAvailable();
        }
        $path = $this->fileUploader->getProjectDirectory(
            ).FolderType::Private->value.DocumentType::Image->value.'/'.$personnage->getTrombineUrl();

        $filename = $personnage->getTrombine($this->fileUploader->getProjectDirectory());
        if (!file_exists($filename)) {
            // get old ?
            //
            $path = $this->fileUploader->getProjectDirectory(
                ).FolderType::Private->value.DocumentType::Image->value.'/';
            $filename = $path.$personnage->getTrombineUrl();

            if (!file_exists($filename)) {
                $path = $path = $this->fileUploader->getProjectDirectory(
                    ).'/../larpmanager/private/img/';
                $filename = $path.$personnage->getTrombineUrl();
                if (!file_exists($filename)) {

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
     * Mise à jour de la photo.
     */
    #[Route('/{personnage}/trombine/update', name: 'trombine.update')]
    #[IsGranted('ROLE_USER')]
    public function updateTrombineAction(
        Request $request,
        EntityManagerInterface $entityManager,
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
            !($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_SCENARISTE'))
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

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Votre photo a été enregistrée');

            return $this->redirectToRoute('personnage.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/trombine.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Création d'un nouveau personnage.
     */
    #[Route('/{personnage}/add', name: 'add')]
    public function newAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $personnage = new Personnage();

        $form = $this->createForm(PersonnageForm::class, $personnage)
            ->add('valider', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $this->getUser()->addPersonnage($personnage);
            $entityManager->persist($this->getUser());
            $entityManager->persist($personnage);
            $entityManager->flush();

            $app['personnage.manager']->setCurrentPersonnage($personnage);
            $this->addFlash('success', 'Votre personnage a été créé');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('personnage/new.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page d'accueil de gestion des personnage.
     */
    public function accueilAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('personnage/accueil.twig', []);
    }

    /**
     * Permet de faire vieillir les personnages
     * Cela va donner un Jeton Vieillesse à tous les personnages et changer la catégorie d'age des personnages cumulants deux jetons vieillesse.
     */
    // TODO
    #[Route('/vieillir', name: 'vieillir')]
    public function vieillirAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $form = $this->createForm()
            ->add(
                'valider',
                SubmitType::class,
                ['label' => 'Faire vieillir tous les personnages', 'attr' => ['class' => 'btn-danger']]
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnages = $entityManager->getRepository('\\'.Personnage::class)->findAll();
            $token = $entityManager->getRepository('\\'.Token::class)->findOneByTag('VIEILLESSE');
            $ages = $entityManager->getRepository('\\'.Age::class)->findAll();

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
                $entityManager->persist($personnageHasToken);

                if (true == $personnage->getVivant()) {
                    $personnage->setAgeReel($personnage->getAgeReel() + 5); // ajoute 5 ans à l'age réél
                }

                if (0 == $personnage->getPersonnageHasTokens()->count() % 2 && true == $personnage->getVivant()) {
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
                        $entityManager->persist($personnageChronologie);
                    }
                }

                $entityManager->persist($personnage);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Tous les personnages ont reçu un jeton vieillesse.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('personnage/vieillir.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifier l'age d'un personnage.
     */
    #[Route('/{personnage}/updateAge', name: 'admin.update.age')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateAgeAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        // TODO update route redirect to personnage Detail
        return $this->handleCreateOrUpdate(
            $request,
            $personnage,
            PersonnageUpdateAgeForm::class
        );
    }

    /**
     * Modification des technologies d'un personnage.
     */
    #[Route('/{personnage}/updateTechnologie', name: 'admin.update.technologie')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateTechnologieAction(
        Request $request,
        PagerService $pagerService,
        #[MapEntity] Personnage $personnage,
        TechnologieRepository $technologieRepository
    ): Response {
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
     * Ajoute une technologie à un personnage.
     */
    #[Route('/{personnage}/technologie/{technologie}/add', name: 'admin.add.technologie')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminAddTechnologieAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Technologie $technologie,
    ): RedirectResponse {
        $personnage->addTechnologie($technologie);

        $this->entityManager->flush();

        $this->addFlash('success', $technologie->getLabel().' a été ajoutée.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.admin.update.technologie',
            ['personnage' => $personnage->getId(), 303]
        );
    }

    /**
     * Retire une technologie à un personnage.
     */
    #[Route('/{personnage}/technologie/{technologie}/delete', name: 'admin.delete.technologie')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminRemoveTechnologieAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Technologie $technologie,
    ): RedirectResponse {
        $personnage->removeTechnologie($technologie);

        $this->entityManager->flush();

        $this->addFlash('success', $technologie->getLabel().' a été retirée.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.update.technologie',
            ['personnage' => $personnage->getId(), 303]
        );
    }

    /**
     * @return non-falsy-string[]
     */
    public function getLangueMateriel(Personnage $personnage): array
    {
        $langueMateriel = [];
        foreach ($personnage->getPersonnageLangues() as $langue) {
            if ($langue->getLangue()->getGroupeLangue()->getId() > 0 && $langue->getLangue()->getGroupeLangue()->getId(
                ) < 6) {
                if (!in_array('Bracelet '.$langue->getLangue()->getGroupeLangue()->getCouleur(), $langueMateriel)) {
                    $langueMateriel[] = 'Bracelet '.$langue->getLangue()->getGroupeLangue()->getCouleur();
                }
            }

            if (0 === $langue->getLangue()->getDiffusion()) {
                $langueMateriel[] = 'Alphabet '.$langue->getLangue()->getLabel();
            }
        }
        sort($langueMateriel);

        return $langueMateriel;
    }

    #[Route('/{personnage}/enveloppe/print', name: 'enveloppe.print')]
    public function enveloppePrintAction(#[MapEntity] Personnage $personnage): Response
    {
        return $this->render('personnage/enveloppe.twig', [
            'personnage' => $personnage,
            'langueMateriel' => $this->getLangueMateriel($personnage),
        ]);
    }

    /**
     * Modifie le matériel lié à un personnage.
     */
    #[Route('/{personnage}/updateMateriel', name: 'update.materiel')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminMaterielAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $form = $this->createForm()
            ->add('materiel', 'textarea', [
                'required' => false,
                'data' => $personnage->getMateriel(),
            ])
            ->add('valider', SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $personnage->setMateriel($data['materiel']);
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/materiel.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modification du statut d'un personnage.
     */
    #[Route('/{personnage}/statut', name: 'admin.statut')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminStatutAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $form = $this->createForm(PersonnageStatutForm::class, $personnage)
            ->add('submit', SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $evenement = false == $personnage->getVivant() ? 'Mort violente' : 'Résurrection';

            // TODO: Trouver comment avoir la date du GN
            /*
            $personnageChronologie = new \App\Entity\PersonnageChronologie();
            $personnageChronologie->setAnnee($anneeGN);
            $personnageChronologie->setEvenement($evenement);
            $personnageChronologie->setPersonnage($personnage);
            $entityManager->persist($personnageChronologie);
            */

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le statut du personnage a été modifié');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/statut.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Transfert d'un personnage à un autre utilisateur.
     */
    #[Route('/{personnage}/transfert', name: 'admin.transfert')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminTransfertAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        if ($oldParticipant = $personnage->getLastParticipant()) {
            $this->addFlash(
                'error',
                'Désolé, le personnage ne dispose pas encore de participation et ne peut donc pas encore être transféré'
            );

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        $form = $this->createFormBuilder()
            ->add('id', EntityType::class, [
                'required' => true,
                // 'expanded' => true,
                'autocomplete' => true,
                'label' => 'Nouveau propriétaire',
                'class' => Participant::class,
                // 'choice_label' => 'UserIdentity',
            ])
            ->add('transfert', SubmitType::class, [
                'label' => 'Transferer',
                'attr' => ['class' => 'btn btn-secondary'],
            ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $newParticipant = $data['participant'];

            $personnage->setUser($newParticipant->getUser());

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

            $oldParticipant->setPersonnageNull();
            $oldParticipant->getUser()?->setPersonnage(null);
            $newParticipant->setPersonnage($personnage);
            $newParticipant->getUser()->setPersonnage($personnage);
            $personnage->addParticipant($newParticipant);

            $entityManager->persist($oldParticipant);
            $entityManager->persist($newParticipant);
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été transféré');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/transfert.twig', [
            'personnage' => $personnage,
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

    /**
     * Liste des personnages.
     */
    #[Route('/admin/list', name: 'admin.list')]
    #[Route('/list', name: 'list')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminListAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $routeName = 'personnage.admin.list';
        $twigFilePath = 'admin/personnage/list.twig';

        // handle the request and return an array containing the parameters for the view
        $viewParams = $this->getSearchViewParameters($request, $entityManager, $routeName);

        return $this->render($twigFilePath, $viewParams);
    }

    /**
     * Imprimer la liste des personnages.
     */
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminPrintAction(Request $request, EntityManagerInterface $entityManager): void
    {
        // TODO
    }

    /**
     * Télécharger la liste des personnages au format CSV.
     */
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminDownloadAction(Request $request, EntityManagerInterface $entityManager): void
    {
        // TODO
    }

    /**
     * Affiche le détail d'un personnage (pour les orgas).
     */
    #[Route('/{personnage}/admin', name: 'admin.detail')]
    #[Route('/{personnage}', name: 'detail')]
    #[Route('/admin/{personnage}/detail', name: 'admin.detail')] // larp V1 url
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminDetailAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
        Environment $twig,
    ): Response {
        $descendants = $entityManager->getRepository(Personnage::class)->findDescendants($personnage);
        $tab = $request->get('tab', 'general');
        if (!$twig->getLoader()->exists('personnage/fragment/tab_'.$tab.'.twig')) {
            $tab = 'general';
        }

        return $this->render(
            'personnage/detail.twig',
            [
                'personnage' => $personnage,
                'descendants' => $descendants,
                'langueMateriel' => $this->getLangueMateriel($personnage),
                'participant' => $personnage->getLastParticipant(),
                'tab' => $tab,
            ]
        );
    }

    /**
     * Gestion des points d'expérience d'un personnage (pour les orgas).
     */
    #[Route('/{personnage}/xp', name: 'admin.xp')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminXpAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
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

            $entityManager->persist($personnage);
            $entityManager->persist($historique);
            $entityManager->flush();

            $this->addFlash('success', 'Les points d\'expériences ont été ajoutés');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/xp.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajout d'un personnage (orga seulement).
     */
    #[Route('/add', name: 'admin.add')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminAddAction(
        Request $request,
        EntityManagerInterface $entityManager,
        PersonnageService $personnageService,
    ): Response {
        $personnage = new Personnage();
        $gnActif = GroupeManager::getGnActif($entityManager);

        $participant = $request->get('participant');
        if (!$participant) {
            // essaye de récupérer le participant du gn actif

            if ($gnActif) {
                $participant = $this->getUser()->getParticipant($gnActif);
            }

            if (!$participant) {
                // sinon récupère le dernier dans la liste
                $participant = $this->getUser()->getLastParticipant();
            }
        } else {
            $participant = $entityManager->getRepository('\\'.Participant::class)->find($participant);
        }

        $form = $this->createForm(PersonnageForm::class, $personnage)
            ->add('classe', 'entity', [
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
            $entityManager->persist($historique);

            // ajout des compétences acquises à la création
            $personnageService->addClasseCompetencesFamilyCreation($personnage);
            if ($personnageService->hasErrors()) {
                $this->addFlash('success', $personnageService->getErrorsAsString());

                return $this->redirectToRoute('gn.personnage', ['gn' => $participant->getGn()->getId()], 303);
            }
            /*foreach ($personnage->getClasse()->getCompetenceFamilyCreations() as $competenceFamily) {
                $firstCompetence = $competenceFamily->getFirstCompetence();
                if ($firstCompetence) {
                    $personnage->addCompetence($firstCompetence);
                    $firstCompetence->addPersonnage($personnage);
                    $entityManager->persist($firstCompetence);
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
                $entityManager->persist($historique);
            }

            $entityManager->persist($personnage);
            if ($participant) {
                $entityManager->persist($participant);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre personnage a été sauvegardé.');
            if ($participant && $participant->getGroupe()) {
                return $this->redirectToRoute('groupe.detail', ['groupe' => $participant->getGroupe()->getId()], 303);
            } else {
                return $this->redirectToRoute('homepage', [], 303);
            }
        }

        return $this->render('personnage/add.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
        ]);
    }

    /**
     * Supression d'un personnage (orga seulement).
     */
    #[Route('/{personnage}/delete', name: 'admin.delete')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $form = $this->createForm(PersonnageDeleteForm::class, $personnage)
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            foreach ($personnage->getExperienceGains() as $xp) {
                $personnage->removeExperienceGain($xp);
                $entityManager->remove($xp);
            }

            foreach ($personnage->getExperienceUsages() as $xp) {
                $personnage->removeExperienceUsage($xp);
                $entityManager->remove($xp);
            }

            foreach ($personnage->getMembres() as $membre) {
                $personnage->removeMembre($membre);
                $entityManager->remove($membre);
            }

            foreach ($personnage->getPersonnagesReligions() as $personnagesReligions) {
                $personnage->removePersonnagesReligions($personnagesReligions);
                $entityManager->remove($personnagesReligions);
            }

            foreach ($personnage->getPostulants() as $postulant) {
                $personnage->removePostulant($postulant);
                $entityManager->remove($postulant);
            }

            foreach ($personnage->getPersonnageLangues() as $personnageLangue) {
                $personnage->removePersonnageLangues($personnageLangue);
                $entityManager->remove($personnageLangue);
            }

            foreach ($personnage->getPersonnageTriggers() as $trigger) {
                $personnage->removePersonnageTrigger($trigger);
                $entityManager->remove($trigger);
            }

            foreach ($personnage->getPersonnageBackgrounds() as $background) {
                $personnage->removePersonnageBackground($background);
                $entityManager->remove($background);
            }

            foreach ($personnage->getPersonnageHasTokens() as $token) {
                $personnage->removePersonnageHasToken($token);
                $entityManager->remove($token);
            }

            foreach ($personnage->getParticipants() as $participant) {
                $participant->setPersonnage();
                $entityManager->persist($participant);
            }

            $entityManager->remove($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été supprimé.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('personnage/delete.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modification du personnage.
     */
    #[Route('/{personnage}/update', name: 'update')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {

        $editClass = PersonnageEditForm::class;
        if ($this->isGranted(Role::SCENARISTE->value)) {
            $editClass = PersonnageUpdateForm::class;
        }

        $form = $this->createForm($editClass, $personnage)
            ->add('save', SubmitType::class, [
                'label' => 'Valider les modifications',
                'attr' => [
                    'class' => 'btn btn-secondary',
                ],
            ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Ajoute un background au personnage.
     */
    #[Route('/{personnage}/addBackground', name: 'admin.add.background')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminAddBackgroundAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $background = new PersonnageBackground();

        $background->setPersonnage($personnage);
        $background->setUser($this->getUser());

        $form = $this->createForm(PersonnageBackgroundForm::class, $background);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();

            $entityManager->persist($background);
            $entityManager->flush();

            $this->addFlash('success', 'Le background a été sauvegardé.');

            return $this->redirectToRoute(
                'personnage.admin.detail',
                ['personnage' => $personnage->getId(), 'tab' => 'biographie'],
                303
            );
        }

        return $this->render('personnage/addBackground.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'background' => $background,
        ]);
    }

    /**
     * Modifie le background d'un personnage.
     */
    #[Route('/{personnage}/background/{background}/update', name: 'admin.update.background', requirements: [
        'personnage' => Requirement::DIGITS,
        'background' => Requirement::DIGITS,
    ])]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateBackgroundAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnageBackground $background,
    ): RedirectResponse|Response {
        $form = $this->createForm(PersonnageBackgroundForm::class, $background);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $background = $form->getData();

            $entityManager->persist($background);
            $entityManager->flush();

            $this->addFlash('success', 'Le background a été sauvegardé.');

            return $this->redirectToRoute(
                'personnage.admin.detail',
                ['personnage' => $personnage->getId(), 'tab' => 'biographie'],
                303
            );
        }

        return $this->render('personnage/updateBackground.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'background' => $background,
        ]);
    }

    #[Route('/{personnage}/background/{background}/delete', name: 'admin.delete.background', requirements: [
        'personnage' => Requirement::DIGITS,
        'background' => Requirement::DIGITS,
    ])]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminDeleteBackgroundAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnageBackground $background,
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $background,
            'Supprimer un background',
            'La background a été supprimé',
            $this->generateUrl('personnage.detail', ['personnage' => $personnage->getId(), 'tab' => 'biographie']),
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
     * Modification de la renommee du personnage.
     */
    #[Route('/{personnage}/updateRenomme', name: 'admin.update.renomme')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateRenommeAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $form = $this->createForm(PersonnageUpdateRenommeForm::class)
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider les modifications', 'attr' => ['class' => 'btn btn-secondary']]
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

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modification de l'héroisme d'un personnage.
     */
    #[Route('/{personnage}/updateHeroisme', name: 'admin.update.heroisme')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateHeroismeAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
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

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modification du pugilat d'un personnage.
     */
    #[Route('/{personnage}/updatePugilat', name: 'admin.update.pugilat')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdatePugilatAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $form = $this->createForm(PersonnageUpdatePugilatForm::class)
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider les modifications', 'attr' => ['class' => 'btn btn-secondary']]
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

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Ajoute un jeton vieillesse au personnage.
     */
    #[Route('/{personnage}/addToken', name: 'admin.token.add')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminTokenAddAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse {
        $token = $request->get('token');
        $token = $entityManager->getRepository('\\'.Token::class)->findOneByTag($token);

        // donne un jeton vieillesse
        $personnageHasToken = new PersonnageHasToken();
        $personnageHasToken->setToken($token);
        $personnageHasToken->setPersonnage($personnage);

        $personnage->addPersonnageHasToken($personnageHasToken);
        $entityManager->persist($personnageHasToken);

        $personnage->setAgeReel($personnage->getAgeReel() + 5); // ajoute 5 ans à l'age réél

        if (0 == $personnage->getPersonnageHasTokens()->count() % 2) {
            if (5 != $personnage->getAge()->getId()) {
                $age = $entityManager->getRepository('\\'.Age::class)->findOneById($personnage->getAge()->getId() + 1);
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
                $entityManager->persist($personnageChronologie);
            }
        }

        $entityManager->persist($personnage);
        $entityManager->flush();
        $this->addFlash('success', 'Le jeton '.$token->getTag().' a été ajouté.');

        return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
    }

    /**
     * Retire un jeton d'un personnage.
     */
    #[Route('/{personnage}/deleteToken', name: 'admin.token.delete')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminTokenDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
        PersonnageHasToken $personnageHasToken,
    ): RedirectResponse {
        $personnage->removePersonnageHasToken($personnageHasToken);
        // $personnage->setAgeReel($personnage->getAgeReel() - 5);
        if (0 != $personnage->getPersonnageHasTokens()->count() % 2 && 5 != $personnage->getAge()->getId()) {
            $age = $entityManager->getRepository('\\'.Age::class)->findOneById($personnage->getAge()->getId() - 1);
            $personnage->setAge($age);
        }

        $entityManager->remove($personnageHasToken);
        $entityManager->persist($personnage);

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
        $entityManager->persist($personnageChronologie);

        $entityManager->flush();

        $this->addFlash('success', 'Le jeton a été retiré.');

        return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
    }

    /**
     * Ajoute un trigger.
     */
    #[Route('/{personnage}/addTrigger', name: 'admin.trigger.add')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminTriggerAddAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $trigger = new PersonnageTrigger();
        $trigger->setPersonnage($personnage);
        $trigger->setDone(false);

        $form = $this->createForm(TriggerForm::class, $trigger)
            ->add('save', SubmitType::class, ['label' => 'Valider les modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trigger = $form->getData();

            $entityManager->persist($trigger);
            $entityManager->flush();

            $this->addFlash('success', 'Le déclencheur a été ajouté.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/addTrigger.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Supprime un trigger.
     */
    #[Route('/{personnage}/deleteTrigger', name: 'admin.trigger.delete')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminTriggerDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $trigger = $request->get('trigger');

        $form = $this->createForm(TriggerDeleteForm::class, $trigger)
            ->add('save', SubmitType::class, ['label' => 'Valider les modifications']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trigger = $form->getData();

            $entityManager->remove($trigger);
            $entityManager->flush();

            $this->addFlash('success', 'Le déclencheur a été supprimé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/deleteTrigger.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'trigger' => $trigger,
        ]);
    }

    /**
     * Modifie la liste des domaines de magie.
     */
    #[Route('/{personnage}/updateDomaine', name: 'admin.update.domaine')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateDomaineAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $originalDomaines = new ArrayCollection();
        foreach ($personnage->getDomaines() as $domaine) {
            $originalDomaines[] = $domaine;
        }

        $form = $this->createForm(PersonnageUpdateDomaineForm::class, $personnage)
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider les modifications', 'attr' => ['class' => 'btn btn-secondary']]
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

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    #[Route('/{personnage}/domaine/{domaine}/delete', name: 'admin.delete.domaine')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminRemoveDomaineAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Domaine $domaine,
    ): RedirectResponse {
        $nomDomaine = $domaine->getLabel();

        $domaine->removePersonnage($personnage);

        $this->entityManager->flush();

        $this->addFlash('success', $nomDomaine.' a été retirée.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.admin.update.domaine',
            ['personnage' => $personnage->getId(), 303]
        );
    }

    /**
     * Modifie la liste des langues.
     */
    #[Route('/{personnage}/updateLangue', name: 'admin.update.langue')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateLangueAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
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
                ['label' => 'Valider vos modifications', 'attr' => ['class' => 'btn btn-secondary']]
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

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Affiche la liste des prières pour modifications.
     */
    #[Route('/{personnage}/priere/update', name: 'admin.update.priere')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdatePriereAction(
        Request $request,
        PagerService $pagerService,
        #[MapEntity] Personnage $personnage,
        PriereRepository $priereRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($priereRepository)->setLimit(50);

        return $this->render(
            'personnage/updatePriere.twig',
            [
                'personnage' => $personnage,
                'pagerService' => $pagerService,
                'paginator' => $priereRepository->searchPaginated($pagerService),
            ]
        );
    }

    /**
     * Ajoute une priere à un personnage.
     */
    #[Route('/{personnage}/priere/{priere}/add', name: 'admin.add.priere')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminAddPriereAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Priere $priere,
    ): RedirectResponse {
        $priere->addPersonnage($personnage);

        $this->entityManager->flush();

        $this->addFlash('success', $priere->getLabel().' a été ajoutée.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.admin.update.priere',
            ['personnage' => $personnage->getId(), 303]
        );
    }

    /**
     * Retire une priere à un personnage.
     */
    #[Route('/{personnage}/priere/{priere}/delete', name: 'admin.priere.delete')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminRemovePriereAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Priere $priere,
    ): RedirectResponse {
        $priere->removePersonnage($personnage);

        $this->entityManager->flush();

        $this->addFlash('success', $priere->getLabel().' a été retirée.');

        return $this->redirectToReferer($request) ?? $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.admin.update.priere',
            ['personnage' => $personnage->getId(), 303]
        );
    }

    /**
     * Affiche la liste des connaissances pour modification.
     */
    #[Route('/{personnage}/updateConnaissance', name: 'admin.update.connaissance')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateConnaissanceAction(
        Request $request,
        PagerService $pagerService,
        #[MapEntity] Personnage $personnage,
        ConnaissanceRepository $connaissanceRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($connaissanceRepository)->setLimit(50);

        return $this->render('personnage/updateConnaissance.twig', [
            'personnage' => $personnage,
            'pagerService' => $pagerService,
            'paginator' => $connaissanceRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Ajoute une connaissance à un personnage.
     */
    #[Route('/{personnage}/connaissance/{connaissance}/add', name: 'admin.add.connaissance')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminAddConnaissanceAction(
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Connaissance $connaissance,
    ): RedirectResponse {
        $personnage->addConnaissance($connaissance);
        $this->entityManager->flush();

        $this->addFlash('success', $connaissance->getLabel().' '.$connaissance->getNiveau().' a été ajouté.');

        return $this->redirectToRoute(
            'personnage.admin.update.connaissance',
            ['personnage' => $personnage->getId(), 303]
        );
    }

    /**
     * Retire une connaissance à un personnage.
     */
    #[Route('/{personnage}/connaissance/{connaissance}/delete', name: 'admin.delete.connaissance')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminRemoveConnaissanceAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Connaissance $connaissance,
    ): RedirectResponse {
        $personnage->removeConnaissance($connaissance);
        $this->entityManager->flush();

        $this->addFlash('success', $connaissance->getLabel().' '.$connaissance->getNiveau().' a été retiré.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.admin.update.connaissance',
            ['personnage' => $personnage->getId(), 303]
        );
    }

    /**
     * Affiche la liste des sorts pour modification.
     */
    #[Route('/{personnage}/updateSort', name: 'admin.update.sort')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateSortAction(
        Request $request,
        PagerService $pagerService,
        #[MapEntity] Personnage $personnage,
        SortRepository $sortRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($sortRepository)->setLimit(50);

        return $this->render(
            'personnage/updateSort.twig',
            [
                'personnage' => $personnage,
                'pagerService' => $pagerService,
                'paginator' => $sortRepository->searchPaginated($pagerService),
            ]
        );
    }

    /**
     * Ajoute un sort à un personnage.
     */
    #[Route('/{personnage}/sort/{sort}/add', name: 'admin.add.sort')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminAddSortAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Sort $sort,
    ): RedirectResponse {
        $personnage->addSort($sort);

        $this->entityManager->flush();

        $this->addFlash('success', $sort->getLabel().' '.$sort->getNiveau().' a été ajouté.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.admin.update.sort',
            ['personnage' => $personnage->getId(), 303]
        );
    }

    /**
     * Retire un sort à un personnage.
     */
    #[Route('/{personnage}/sort/{sort}/delete', name: 'admin.delete.sort')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminRemoveSortAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Sort $sort,
    ): RedirectResponse {
        $personnage->removeSort($sort);

        $this->entityManager->flush();

        $this->addFlash('success', $sort->getLabel().' '.$sort->getNiveau().' a été retiré.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.admin.update.sort',
            ['personnage' => $personnage->getId(), 303]
        );
    }

    /**
     * Affiche la liste des potions pour modification.
     */
    #[Route('/{personnage}/updatePotion', name: 'admin.update.potion')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdatePotionAction(
        Request $request,
        PagerService $pagerService,
        #[MapEntity] Personnage $personnage,
        PotionRepository $potionRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($potionRepository)->setLimit(50);

        return $this->render(
            'personnage/updatePotion.twig',
            [
                'pagerService' => $pagerService,
                'paginator' => $potionRepository->searchPaginated($pagerService),

                'personnage' => $personnage,
            ]
        );
    }

    /**
     * Ajoute une potion à un personnage.
     */
    #[Route('/{personnage}/addPotion', name: 'admin.add.potion')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminAddPotionAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse {
        $potionID = $request->get('potion');

        $potion = $entityManager->getRepository(Potion::class)
            ->find($potionID);

        $nomPotion = $potion->getLabel();

        $personnage->addPotion($potion);

        $entityManager->flush();

        $this->addFlash('success', $nomPotion.' a été ajoutée.');

        return $this->redirectToRoute('personnage.admin.update.potion', ['personnage' => $personnage->getId(), 303]);
    }

    /**
     * Retire une potion à un personnage.
     */
    #[Route('/{personnage}/potion/{potion}/delete', name: 'admin.delete.potion')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminRemovePotionAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] Potion $potion,
    ): RedirectResponse {

        $nomPotion = $potion->getLabel();
        $personnage->removePotion($potion);

        $this->entityManager->flush();

        $this->addFlash('success', $nomPotion.' a été retirée.');

        return $this->redirectToReferer($request) ?? $this->redirectToRoute(
            'personnage.admin.update.potion',
            ['personnage' => $personnage->getId(), 303]
        );
    }

    /**
     * Modifie la liste des ingrédients.
     */
    #[Route('/{personnage}/updateIngredient', name: 'admin.update.ingredient')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateIngredientAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $originalPersonnageIngredients = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets personnageIngredient du groupe
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
                $personnageIngredient->setPersonnage($personnage);
            }

            /*
             *  supprime la relation entre personnageIngredient et le personnage
             */
            foreach ($originalPersonnageIngredients as $personnageIngredient) {
                if (false == $personnage->getPersonnageIngredients()->contains($personnageIngredient)) {
                    $entityManager->remove($personnageIngredient);
                }
            }

            $random = $form['random']->getData();

            /*
             *  Gestion des ingrédients alloués au hasard
             */
            if ($random && $random > 0) {
                $ingredients = $entityManager->getRepository(Ingredient::class)->findAllOrderedByLabel();
                shuffle($ingredients);
                $needs = new ArrayCollection(array_slice($ingredients, 0, $random));

                foreach ($needs as $ingredient) {
                    $pi = new PersonnageIngredient();
                    $pi->setIngredient($ingredient);
                    $pi->setNombre(1);
                    $pi->setPersonnage($personnage);
                    $entityManager->persist($pi);
                }
            }

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/ingredients.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modifie la liste des ressources.
     */
    #[Route('/{personnage}/updateRessource', name: 'admin.update.ressource')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateRessourceAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
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
             *  supprime la relation entre personnageRessource et le personnage
             */
            foreach ($originalPersonnageRessources as $personnageRessource) {
                if (false == $personnage->getPersonnageRessources()->contains($personnageRessource)) {
                    $entityManager->remove($personnageRessource);
                }
            }

            $randomCommun = $form['randomCommun']->getData();

            /*
             *  Gestion des ressources communes alloués au hasard
             */
            if ($randomCommun && $randomCommun > 0) {
                $ressourceCommune = $entityManager->getRepository(Ressource::class)->findCommun();
                shuffle($ressourceCommune);
                $needs = new ArrayCollection(array_slice($ressourceCommune, 0, $randomCommun));

                foreach ($needs as $ressource) {
                    $pr = new PersonnageRessource();
                    $pr->setRessource($ressource);
                    $pr->setNombre(1);
                    $pr->setPersonnage($personnage);
                    $entityManager->persist($pr);
                }
            }

            $randomRare = $form['randomRare']->getData();

            /*
             *  Gestion des ressources rares alloués au hasard
             */
            if ($randomRare && $randomRare > 0) {
                $ressourceRare = $entityManager->getRepository(Ressource::class)->findRare();
                shuffle($ressourceRare);
                $needs = new ArrayCollection(array_slice($ressourceRare, 0, $randomRare));

                foreach ($needs as $ressource) {
                    $pr = new PersonnageRessource();
                    $pr->setRessource($ressource);
                    $pr->setNombre(1);
                    $pr->setPersonnage($personnage);
                    $entityManager->persist($pr);
                }
            }

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/ressources.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Modifie la richesse.
     */
    #[Route('/{personnage}/updateRichesse', name: 'admin.update.richesse')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateRichesseAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $form = $this->createForm(PersonnageRichesseForm::class, $personnage);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();

            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/richesse.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Gestion des documents lié à un personnage.
     */
    #[Route('/{personnage}/documents', name: 'documents')]
    public function documentAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $form = $this->createForm(PersonnageDocumentForm::class, $personnage)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer', 'attr' => ['class' => 'btn-secondary']]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'Le document a été ajouté au personnage.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/documents.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gestion des objets lié à un personnage.
     */
    #[Route('/{personnage}/items', name: 'items')]
    public function itemAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $form = $this->createForm(PersonnageItemForm::class, $personnage)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form->getData();
            $entityManager->persist($personnage);
            $entityManager->flush();

            $this->addFlash('success', 'L\'objet a été ajouté au personnage.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/items.twig', [
            'personnage' => $personnage,
            'form' => $form->createView(),
        ]);
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
     * Ajoute une religion à un personnage.
     */
    #[Route('/{personnage}/addReligion', name: 'admin.add.religion')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    #[Deprecated] // TODO import from Participant::religionAddAction()
    public function adminAddReligionAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        // refUser la demande si le personnage est Fanatique
        if ($personnage->isFanatique()) {
            $this->addFlash(
                'error',
                'Désolé, le personnage êtes un Fanatique, il vous est impossible de choisir une nouvelle religion. (supprimer la religion fanatique qu\'il possède avant)'
            );

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        $personnageReligion = new PersonnagesReligions();
        $personnageReligion->setPersonnage($personnage);

        // ne proposer que les religions que le personnage ne pratique pas déjà ...
        $availableReligions = $app['personnage.manager']->getAdminAvailableReligions($personnage);

        if (0 == $availableReligions->count()) {
            $this->addFlash('error', 'Désolé, il n\'y a plus de religion disponibles');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        // construit le tableau de choix
        $choices = [];
        foreach ($availableReligions as $religion) {
            $choices[] = $religion;
        }

        $form = $this->createForm(PersonnageReligionForm::class, $personnageReligion)
            ->add('religion', 'entity', [
                'required' => true,
                'label' => 'Votre religion',
                'class' => Religion::class,
                'choices' => $availableReligions,
                'choice_label' => 'label',
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider votre religion']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnageReligion = $form->getData();

            // supprimer toutes les autres religions si l'utilisateur à choisi fanatique
            // n'autoriser que un Fervent que si l'utilisateur n'a pas encore Fervent.
            if (3 == $personnageReligion->getReligionLevel()->getIndex()) {
                $personnagesReligions = $personnage->getPersonnagesReligions();
                foreach ($personnagesReligions as $oldReligion) {
                    $entityManager->remove($oldReligion);
                }
            } elseif (2 == $personnageReligion->getReligionLevel()->getIndex()) {
                if ($personnage->isFervent()) {
                    $this->addFlash(
                        'error',
                        'Désolé, vous êtes déjà Fervent d\'une autre religion, il vous est impossible de choisir une nouvelle religion en tant que Fervent. Veuillez contacter votre orga en cas de problème.'
                    );

                    return $this->redirectToRoute('homepage', [], 303);
                }
            }

            $entityManager->persist($personnageReligion);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/religion_add.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Retire une religion d'un personnage.
     */
    #[Route('/{personnage}/religion/{personnageReligion}/delete', name: 'admin.delete.religion')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminRemoveReligionAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnagesReligions $personnageReligion,
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $personnageReligion,
            'Supprimer une religion',
            'La religion a été supprimée',
            $this->generateUrl('personnage.detail', ['personnage' => $personnage->getId(), 'tab' => 'religions']),
            [
                // it's an admin view, do not need to test role for this breadcrumb
                ['route' => $this->generateUrl('personnage.list'), 'name' => 'Liste des personnages'],
                [
                    'route' => $this->generateUrl(
                        'personnage.detail',
                        ['personnage' => $personnage->getId(), 'tab' => 'religions']
                    ),
                    'name' => $personnage->getPublicName(),
                ],
                ['name' => 'Supprimer une religion'],
            ],
        );
    }

    /**
     * Retire une langue d'un personnage.
     */
    #[Route('/{personnage}/deleteLangue/{personnageLangue}', name: 'admin.delete.langue')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminRemoveLangueAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnageLangues $personnageLangue,
    ): RedirectResponse|Response {

        $form = $this->createFormBuilder()
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Retirer la langue', 'attr' => ['class' => 'btn btn-secondary']]
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($personnageLangue);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage a été sauvegardé.');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/removeLangue.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'personnageLangue' => $personnageLangue,
        ]);
    }

    /**
     * Modifie l'origine d'un personnage.
     */
    #[Route('/{personnage}/updateOrigine', name: 'admin.update.origine')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminUpdateOriginAction(
        Request $request,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $form = $this->createForm(PersonnageOriginForm::class, $personnage)
            ->add(
                'save',
                SubmitType::class,
                ['label' => "Valider l'origine du personnage", 'attr' => ['class' => 'btn btn-secondary']]
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

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/update.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
        ]);
    }

    /**
     * Retire la dernière compétence acquise par un personnage.
     */
    #[Route('/{personnage}/deleteCompetence', name: 'admin.delete.competence')]
    public function removeCompetenceAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
        PersonnageService $personnageService,
    ): RedirectResponse|Response {
        $lastCompetence = $personnageService->getLastCompetence($personnage);

        if (!$lastCompetence) {
            $this->addFlash('error', 'Désolé, le personnage n\'a pas encore acquis de compétences');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
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
                ]
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnageService->removeCompetence($personnage, $lastCompetence);

            $this->addFlash('success', 'La compétence a été retirée');

            return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
        }

        return $this->render('personnage/removeCompetence.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'competence' => $lastCompetence,
        ]);
    }

    #[Route('/{personnage}/addCompetence', name: 'admin.add.competence')]
    #[Route('/{personnage}/addCompetence', name: 'add.competence')]
    #[Route('/{personnage}/competence/add', name: 'add.competence')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminAddCompetenceAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
        PersonnageService $personnageService,
    ): RedirectResponse|Response {
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
                $competence
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
                ['label' => 'Ajouter la compétence', 'attr' => ['class' => 'btn btn-secondary']]
            )->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $competenceId = $data->getId();
            $competence = $entityManager->find(Competence::class, $competenceId);
            $service = null;

            if (!$competence) {
                $form->addError('Competence not found');
            } else {
                $service = $personnageService->addCompetence($personnage, $competence);
            }

            if (!$service->hasErrors()) {
                $this->addFlash('success', 'Votre personnage a été sauvegardé.');

                return $this->redirectToRoute('personnage.admin.detail', ['personnage' => $personnage->getId()], 303);
            }

            $form->get('id')->addError(new FormError($service->getErrorsAsString()));
        }

        return $this->render('personnage/competence.twig', [
            'form' => $form->createView(),
            'isAdmin' => $this->isGranted(Role::ADMIN->value) || $this->isGranted(Role::SCENARISTE->value),
            'personnage' => $personnage,
            'competences' => $availableCompetences,
        ]);
    }

    /**
     * Exporte la fiche d'un personnage.
     */
    #[Route('/{personnage}/export', name: 'export')]
    public function exportAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): Response {
        $participant = $personnage->getParticipants()->last();
        $groupe = null;

        if ($participant && $participant->getGroupeGn()) {
            $groupe = $participant->getGroupeGn()->getGroupe();
        }

        return $this->render('personnage/print.twig', [
            'personnage' => $personnage,
            'participant' => $participant,
            'langueMateriel' => $this->getLangueMateriel($personnage),
            'groupe' => $groupe,
        ]);
    }

    /**
     * Ajoute un evenement de chronologie au personnage.
     */
    #[Route('/{personnage}/addChronologie', name: 'admin.add.chronologie')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminAddChronologieAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $personnageChronologie = new PersonnageChronologie();
        $personnageChronologie->setPersonnage($personnage);

        $form = $this->createForm(PersonnageChronologieForm::class, $personnageChronologie)
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider l\'évènement', 'attr' => ['class' => 'btn btn-secondary']]
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $anneeGN = $form->get('annee')->getData();
            $evenement = $form->get('evenement')->getData();

            $personnageChronologie = new PersonnageChronologie();

            $personnageChronologie->setAnnee($anneeGN);
            $personnageChronologie->setEvenement($evenement);
            $personnageChronologie->setPersonnage($personnage);

            $entityManager->persist($personnageChronologie);
            $entityManager->flush();

            $this->addFlash('success', 'L\'évènement a été ajouté à la chronologie.');

            return $this->redirectToRoute(
                'personnage.admin.detail',
                ['personnage' => $personnage->getId(), 'tab' => 'biographie'],
                303
            );
        }

        return $this->render('personnage/updateChronologie.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'personnageChronologie' => $personnageChronologie,
        ]);
    }

    /**
     * Retire un évènement d'un personnage.
     */
    #[Route('/{personnage}/chronologie/{personnageChronologie}/delete', name: 'admin.delete.chronologie', requirements: [
        'personnage' => Requirement::DIGITS,
        'personnageChronologie' => Requirement::DIGITS,
    ])]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminDeleteChronologieAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnageChronologie $personnageChronologie,
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $personnageChronologie,
            'Supprimer une chronologie',
            'La chronologie a été supprimée',
            $this->generateUrl('personnage.detail', ['personnage' => $personnage->getId(), 'tab' => 'biographie']),
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
                $personnageChronologie->getEvenement()
            )
        );
    }

    /**
     * Ajoute une lignée au personnage.
     */
    #[Route('/{personnage}/addLignee', name: 'admin.add.lignee')]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminAddLigneeAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Personnage $personnage,
    ): RedirectResponse|Response {
        $personnageLignee = new PersonnageLignee();
        $personnageLignee->setPersonnage($personnage);

        $form = $this->createForm(PersonnageLigneeForm::class, $personnageLignee)
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Valider les modifications', 'attr' => ['class' => 'btn btn-secondary']]
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parent1 = $form->get('parent1')->getData();
            $parent2 = $form->get('parent2')->getData();
            $lignee = $form->get('lignee')->getData();

            $personnageLignee->setParent1($parent1);
            $personnageLignee->setParent2($parent2);
            $personnageLignee->setLignee($lignee);

            $entityManager->persist($personnageLignee);
            $entityManager->flush();

            $this->addFlash('success', 'La lignée a été ajoutée.');

            return $this->redirectToRoute(
                'personnage.admin.detail',
                ['personnage' => $personnage->getId(), 'tab' => 'biographie'],
                303
            );
        }

        return $this->render('personnage/updateLignee.twig', [
            'form' => $form->createView(),
            'personnage' => $personnage,
            'lignee' => $personnageLignee,
        ]);
    }

    /**
     * Retire une lignée d'un personnage.
     */
    #[Route('/{personnage}/lignee/{personnageLignee}/delete', name: 'admin.delete.lignee', requirements: [
        'personnage' => Requirement::DIGITS,
        'personnageLignee' => Requirement::DIGITS,
    ])]
    #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_ORGA") or is_granted("ROLE_SCENARISTE")'))]
    public function adminDeleteLigneeAction(
        #[MapEntity] Personnage $personnage,
        #[MapEntity] PersonnageLignee $personnageLignee,
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $personnageLignee,
            'Supprimer une lignée',
            'La lignée a été supprimée',
            $this->generateUrl('personnage.detail', ['personnage' => $personnage->getId(), 'tab' => 'biographie']),
            [
                // it's an admin view, do not need to test role for this breadcrumb
                ['route' => $this->generateUrl('personnage.list'), 'name' => 'Liste des personnages'],
                [
                    'route' => $this->generateUrl('personnage.detail', ['personnage' => $personnage->getId()]),
                    'name' => $personnage->getPublicName(),
                ],
                ['name' => 'Supprimer une lignée'],
            ],
            content: $personnageLignee->getParent1()?->getIdName().' & '.$personnageLignee->getParent2()?->getIdName()
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
            entityCallback: $entityCallback
        );
    }
}
