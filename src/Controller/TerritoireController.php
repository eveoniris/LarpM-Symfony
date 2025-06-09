<?php

namespace App\Controller;

use App\Entity\Chronologie;
use App\Entity\Construction;
use App\Entity\Groupe;
use App\Entity\OrigineBonus;
use App\Entity\Personnage;
use App\Entity\Territoire;
use App\Entity\User;
use App\Enum\BonusPeriode;
use App\Enum\CompetenceFamilyType;
use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Enum\LevelType;
use App\Enum\Role;
use App\Form\ChronologieForm;
use App\Form\Territoire\FiefForm;
use App\Form\Territoire\TerritoireBlasonForm;
use App\Form\Territoire\TerritoireCiblesForm;
use App\Form\Territoire\TerritoireConstructionForm;
use App\Form\Territoire\TerritoireCultureForm;
use App\Form\Territoire\TerritoireDeleteForm;
use App\Form\Territoire\TerritoireForm;
use App\Form\Territoire\TerritoireIngredientsForm;
use App\Form\Territoire\TerritoireLoiForm;
use App\Form\Territoire\TerritoireStatutForm;
use App\Form\Territoire\TerritoireStrategieForm;
use App\Repository\BonusRepository;
use App\Repository\TerritoireRepository;
use App\Security\MultiRolesExpression;
use App\Service\GeoJson;
use App\Service\PagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Role::USER->value)]
class TerritoireController extends AbstractController
{
    /**
     * Modifier les listes de cibles pour les quêtes commerciales.
     */
    // #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/add', name: 'territoire.add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        $territoire = new Territoire();

        $form = $this->createForm(TerritoireForm::class, $territoire)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $this->entityManager->persist($territoire);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le territoire a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('territoire.list', [], 303);
            }
            if ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('territoire.add', [], 303);
            }
        }

        return $this->render('territoire/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Récupération de l'image du blason d'un territoire.
     */
    #[Route('/territoire/{territoire}/blason', name: 'territoire.blason', methods: ['GET'])]
    public function blasonAction(#[MapEntity] Territoire $territoire): Response
    {
        $projectDir = $this->fileUploader->getProjectDirectory();
        $paths = [
            'first' => __DIR__.'/../../assets/img/blasons/',
            'v2' => $projectDir.FolderType::Photos->value.DocumentType::Blason->value.'/',
            'v1' => $projectDir.'/../larpmanager/private/img/blasons/',
            'last' => $projectDir.'/../larpm/private/img/blasons/',
        ];

        foreach ($paths as $type => $path) {
            $filename = $path.$territoire->getBlason();

            if (file_exists($filename)) {
                break;
            }
            if ('last' === $type) {
                return $this->sendNoImageAvailable($filename);
            }
        }

        $response = new Response(file_get_contents($filename));
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }

    /**
     * Ajoute une construction dans un territoire.
     */
    #[IsGranted(new MultiRolesExpression(Role::TERRITOIRE))]
    #[Route('/territoire/{territoire}/constructionAdd', name: 'territoire.constructionAdd')]
    public function constructionAddAction(
        Request $request,
        #[MapEntity] Territoire $territoire,
    ): RedirectResponse|Response {
        $form = $this->createForm(TerritoireConstructionForm::class, $territoire)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            // $territoire->addConstruction($data['constructions']);
            $this->entityManager->persist($territoire);
            $this->entityManager->flush();

            $this->addFlash('success', 'La construction a été ajoutée.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        $isMappingInitiated = false;
        // Visible des cartographes initiés (pour les merveilles
        /** @var User $user */
        $user = $this->getUser();
        /** @var Personnage $personnage */
        foreach ($user->getPersonnages() as $personnage) {
            if ($personnage->hasCompetenceLevel(CompetenceFamilyType::MAPPING, LevelType::INITIATED)) {
                $isMappingInitiated = true;
            }
        }

        return $this->render('territoire/addConstruction.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
            'isMappingInitiated' => $isMappingInitiated,
        ]);
    }

    /**
     * Retire une construction d'un territoire.
     */
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/{territoire}/constructionRemove/{construction}', name: 'territoire.constructionRemove')]
    public function constructionRemoveAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Territoire $territoire,
        #[MapEntity] Construction $construction,
    ): RedirectResponse|Response {
        $form = $this->createFormBuilder($territoire)
            ->add('save', SubmitType::class, ['label' => 'Retirer la construction'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $territoire->removeConstruction($construction);
            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'La construction a été retirée.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/removeConstruction.twig', [
            'territoire' => $territoire,
            'construction' => $construction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supression d'un territoire.
     */
    #[Route('/territoire/{territoire}/delete', name: 'territoire.delete')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    public function deleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Territoire $territoire,
    ): RedirectResponse|Response {
        $form = $this->createForm(TerritoireDeleteForm::class, $territoire)
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            foreach ($territoire->getPersonnages() as $personnage) {
                $personnage->setTerritoire(null);
                $entityManager->persist($personnage);
            }

            foreach ($territoire->getGroupes() as $groupe) {
                $groupe->removeTerritoire($territoire);
                $entityManager->persist($groupe);
            }

            if ($territoire->getGroupe()) {
                $groupe = $territoire->getGroupe();
                $groupe->setTerritoire(null);
                $entityManager->persist($groupe);
            }

            $entityManager->remove($territoire);
            $entityManager->flush();
            $this->addFlash('success', 'Le territoire a été supprimé.');

            return $this->redirectToRoute('territoire.list', [], 303);
        }

        return $this->render('territoire/delete.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'un territoire pour les joueurs.
     */
    #[Route('/territoire/{territoire}', name: 'territoire.detail', requirements: ['territoire' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::USER))]
    public function detailAction(
        #[MapEntity] Territoire $territoire,
    ): Response {
        $this->hasAccess(null, [Role::ORGA, Role::SCENARISTE, Role::CARTOGRAPHE]);

        return $this->render('territoire/detail.twig', ['territoire' => $territoire]);
    }

    /**
     * Ajoute un événement à un territoire.
     */
    // TODO
    protected function hasAccess(?Territoire $territoire = null, array $roles = []): void
    {
        $isMembreOrLeadOfGroupeOwner = false;
        if ($groupe = $territoire?->getGroupe()) {
            $isMembreOrLeadOfGroupeOwner = $this->groupeService->isUserIsGroupeMember($groupe)
                || $this->groupeService->isUserIsGroupeResponsable($groupe);
        }

        // Visible des cartographes initiés (pour les merveilles)
        $isMappingInitiated = $this->getPersonnage()
            ?->hasCompetenceLevel(CompetenceFamilyType::MAPPING, LevelType::INITIATED);

        $canSeeDetail = false;
        foreach ($territoire?->getGroupe()?->getPersonnages() ?? [] as $personnage) {
            if ($personnage->getUser()?->getId() === $this->getUser()?->getId()) {
                $canSeeDetail = true;
            }
        }

        $this->setCan(self::IS_ADMIN, $this->isGranted(Role::TERRITOIRE->value));
        $this->setCan(self::CAN_MANAGE, false); // not used?
        $this->setCan(self::CAN_READ_PRIVATE, $isMembreOrLeadOfGroupeOwner || $canSeeDetail);
        $this->setCan(self::CAN_READ_SECRET, (bool) $isMappingInitiated);
        $this->setCan(self::CAN_WRITE, false); // not used?
        $this->setCan(self::CAN_READ, $this->isGranted(Role::USER->value)); // public page

        $this->checkHasAccess(
            $roles,
            fn() => $this->can(self::CAN_READ),
        );
    }


    #[Route('/territoire/{territoire}/eventAdd', name: 'territoire.eventAdd')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function eventAddAction(
        Request $request,
        #[MapEntity] Territoire $territoire,
    ): RedirectResponse|Response {
        $event = $request->get('event');

        $event ??= new Chronologie();

        $form = $this->createForm(EventForm::class, $event)
            ->add('add', SubmitType::class, ['label' => 'Ajouter']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();

            $this->entityManager->persist($event);
            $this->entityManager->flush();
            $this->addFlash('success', 'L\'evenement a été ajouté.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/addEvent.twig', [
            'territoire' => $territoire,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un événement.
     */
    #[Route('/territoire/{territoire}/eventDelete', name: 'territoire.eventDelete')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function eventDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Territoire $territoire,
    ): RedirectResponse|Response {
        $event = $request->get('event');

        $form = $this->createForm(ChronologieDeleteForm::class, $event)
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();

            $entityManager->remove($event);
            $entityManager->flush();
            $this->addFlash('success', 'L\'evenement a été supprimé.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/deleteEvent.twig', [
            'territoire' => $territoire,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un événement.
     */
    #[Route('/territoire/{territoire}/eventUpdate', name: 'territoire.eventUpdate')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function eventUpdateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Territoire $territoire,
    ): RedirectResponse|Response {
        $event = $request->get('event');

        $form = $this->createForm(ChronologieForm::class, $event)
            ->add('update', SubmitType::class, ['label' => 'Mettre à jour']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();

            $entityManager->persist($event);
            $entityManager->flush();
            $this->addFlash('success', 'L\'evenement a été modifié.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/updateEvent.twig', [
            'territoire' => $territoire,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des fiefs.
     */
    #[Route('/territoire/fief', name: 'territoire.fief')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE, Role::CARTOGRAPHE))]
    public function fiefAction(Request $request): Response
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' === $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $formData = $request->query->get('personnageFind');
        $pays = isset($formData['pays']) ? $this->entityManager->find(Territoire::class, $formData['pays']) : null;
        $province = isset($formData['provinces']) ? $this->entityManager->find(
            Territoire::class,
            $formData['provinces'],
        ) : null;
        $groupe = isset($formData['groupe']) ? $this->entityManager->find(Groupe::class, $formData['groupe']) : null;
        $optionalParameters = '';

        // $listeGroupes = $entityManager->getRepository('\\'.Groupe::class)->findList(null, null, ['by' => 'nom', 'dir' => 'ASC'], 1000, 0);
        $listeGroupes = $this->entityManager->getRepository(Groupe::class)->findBy([], ['nom' => 'ASC']);
        $listePays = $this->entityManager->getRepository(Territoire::class)->findRoot();
        $listeProvinces = $this->entityManager->getRepository(Territoire::class)->findProvinces();

        $form = $this->createForm(
            FiefForm::class,
            null,
            [
                'data' => [
                    'pays' => $pays,
                    'province' => $province,
                    'groupe' => $groupe,
                ],
                'listeGroupes' => $listeGroupes,
                'listePays' => $listePays,
                'listeProvinces' => $listeProvinces,
                'method' => 'get',
                'csrf_protection' => false,
            ],
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            $pays = $data['pays'] ?? null;
            $province = $data['province'] ?? null;
            $groupe = $data['groupe'] ?? null;

            if ($type && $value) {
                switch ($type) {
                    case 'idFief':
                        $criteria['t.id'] = $value;
                        break;
                    case 'nomFief':
                        $criteria['t.nom'] = $value;
                        break;
                }
            }
        }

        if ($groupe) {
            $criteria['tgr.id'] = $groupe->getId();
            $optionalParameters .= '&fief[groupe]='.$groupe->getId();
        }

        if ($pays) {
            $criteria['tp.id'] = $pays->getId();
            $optionalParameters .= '&fief[pays]='.$pays->getId();
        }

        if ($province) {
            $criteria['tpr.id'] = $province->getId();
            $optionalParameters .= '&fief[province]='.$province->getId();
        }

        /* @var TerritoireRepository $repo */
        $repo = $this->entityManager->getRepository(Territoire::class);
        $fiefs = $repo->findFiefsList(
            $limit,
            $offset,
            $criteria,
            ['by' => $order_by, 'dir' => $order_dir],
        );

        // $numResults = count($fiefs);

        // $paginator = new Paginator($numResults, $limit, $page,
        //    $app['url_generator']->generate('territoire.fief').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir.$optionalParameters
        // );

        $paginator = $repo->findPaginatedQuery(
            $fiefs,
            $this->getRequestLimit(50),
            $this->getRequestPage(),
        );

        $isAdmin = $this->isGranted(Role::ORGA->value) || $this->isGranted(Role::CARTOGRAPHE->value);

        return $this->render('territoire/fief.twig', [
            'fiefs' => $fiefs,
            'form' => $form->createView(),
            'paginator' => $paginator,
            'isAdmin' => $isAdmin,
            'optionalParameters' => $optionalParameters,
        ]);
    }

    /**
     * Liste des territoires.
     */
    #[Route('/territoire', name: 'territoire.list')]
    public function listAction(
        PagerService $pagerService,
        TerritoireRepository $territoireRepository,
    ): Response {
        // Set order by nom by default
        // $pagerService->setOrdersBy(['nom' => OrderBy::ASC]); // test default overwrite from request
        $alias = $territoireRepository->getAlias();

        // Got only main territory
        $queryBuilder = $territoireRepository->root(
            $territoireRepository->createQueryBuilder($alias),
            true,
        );

        $isAdmin = $this->isGranted(Role::ORGA->value) || $this->isGranted(Role::CARTOGRAPHE->value);

        return $this->render(
            'territoire/list.twig',
            [
                'paginator' => $territoireRepository->searchPaginated($pagerService, $queryBuilder),
                'isAdmin' => $isAdmin,
                'pagerService' => $pagerService,
            ],
        );
    }

    /**
     * Liste des pays avec le nombre de noble.
     */
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/noble', name: 'territoire.noble')]
    public function nobleAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $territoires = $entityManager->getRepository(Territoire::class)->findRoot();

        return $this->render('territoire/noble.twig', ['territoires' => $territoires]);
    }

    /**
     * Impression des territoires.
     */
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/print', name: 'territoire.print')]
    public function printAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $territoires = $entityManager->getRepository(Territoire::class)->findFiefs();

        return $this->render('territoire/print.twig', ['territoires' => $territoires]);
    }

    /**
     * Liste des fiefs pour les quêtes.
     */
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/quete', name: 'territoire.quete')]
    public function queteAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $territoires = $entityManager->getRepository('\\'.Territoire::class)->findFiefs();

        return $this->render('territoire/quete.twig', ['territoires' => $territoires]);
    }

    #[Route('/territoire/geoJsonTest', name: 'territoire.geojson.test')]
    public function testGeoAction(GeoJson $geoJson, TerritoireRepository $territoireRepository)
    {
        $mDist = 10.0;
        $comparator = $geoJson->setMaxDistance($mDist);

        /** @var Territoire $asgard */
        $asgard = $territoireRepository->findOneBy(['id' => 80]);
        /** @var Territoire $hyperbore */
        $hyperbore = $territoireRepository->findOneBy(['id' => 28]);

        $pointsCorrespondants = $comparator->comparePoints(
            json_decode(
                $asgard->getGeojson(),
                true,
            ),
            json_decode(
                $hyperbore->getGeojson(),
                true,
            ),
        );

        echo 'Comparons la proximité Géo de Asgard avec Hyperborée : <br />';
        foreach ($pointsCorrespondants as $match) {
            echo sprintf(
                    "<br />Points trouvés : [%f, %f] et [%f, %f] - Distance : %.2f km\n",
                    $match['point1'][0],
                    $match['point1'][1],
                    $match['point2'][0],
                    $match['point2'][1],
                    $match['distance'],
                ).PHP_EOL;
        }

        echo '<br /><br />Soit un total de '.count(
                $pointsCorrespondants,
            ).' points géographiquement proches de moins de '.$mDist.'km';

        return new Response();
    }

    /**
     * Modifie un territoire.
     */
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/{territoire}/update', name: 'territoire.update')]
    public function updateAction(
        Request $request,
        #[MapEntity] Territoire $territoire,
    ): RedirectResponse|Response {
        $form = $this->createForm(TerritoireForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $this->entityManager->persist($territoire);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le territoire a été mis à jour.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/update.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour le blason d'un territoire.
     */
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/{territoire}/updateBlason', name: 'territoire.updateBlason')]
    public function updateBlasonAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Territoire $territoire,
    ): RedirectResponse|Response {
        $form = $this->createForm(TerritoireBlasonForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $request->files->get($form->getName());

            $path = __DIR__.'/../../assets/img/blasons/';
            $filename = $files['blason']->getClientOriginalName();
            $extension = $files['blason']->guessExtension();

            if (!$extension || !in_array($extension, ['png', 'jpg', 'jpeg', 'bmp'])) {
                $this->addFlash(
                    'error',
                    'Désolé, votre image ne semble pas valide (vérifiez le format de votre image)',
                );

                return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
            }

            $blasonFilename = hash('md5', $this->getUser()->getUsername().$filename.time()).'.'.$extension;

            $imagine = new \Imagine\Gd\Imagine();
            $image = $imagine->open($files['blason']->getPathname());
            $image->resize($image->getSize()->widen(160));
            $image->save($path.$blasonFilename);

            $territoire->setBlason($blasonFilename);
            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'Le blason a été enregistré');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/blason.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/{territoire}/updateBonus', name: 'territoire.updateBonus')]
    public function updateBonusAction(
        Request $request,
        #[MapEntity] Territoire $territoire,
        BonusRepository $bonusRepository,
    ): RedirectResponse|Response {
        /*$form = $this->createForm(TerritoireBonusForm::class, $territoire->getOriginesBonus())
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder', 'attr' => ['class' => 'btn btn-secondary']]);
*/
        // TODO auto selected existing and check why it's not working
        $bonusChoices = $bonusRepository->findBy(['periode' => BonusPeriode::NATIVE->value], ['titre' => 'ASC']);
        $currentOrigineBonus = $territoire->getOriginesBonus()?->last() ?: null;
        $currentBonus = $currentOrigineBonus?->getBonus();
        $form = $this->createFormBuilder()
            ->add('bonus', ChoiceType::class, [
                'label' => 'Choisissez vos bonus',
                'multiple' => false,
                // 'autocomplete' => true,
                'expanded' => true,
                'choices' => $bonusChoices,
                'choice_label' => 'titre',
                'data' => $currentBonus,
            ])
            ->add('save', SubmitType::class, ['label' => 'Valider '])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bonus = $form->getData()['bonus'] ?? null;

            $origineBonus = new OrigineBonus();
            $origineBonus->setBonus($bonus);
            $origineBonus->setTerritoire($territoire);

            foreach ($territoire->getOriginesBonus() as $currentOriginesBonus) {
                $this->entityManager->remove($currentOriginesBonus);
            }

            $this->entityManager->persist($origineBonus);
            $this->entityManager->persist($territoire);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/bonus.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/{territoire}/updateCibles', name: 'territoire.updateCibles')]
    public function updateCiblesAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Territoire $territoire,
    ): RedirectResponse|Response {
        $form = $this->createForm(TerritoireCiblesForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/cibles.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour la culture d'un territoire.
     */
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/{territoire}/updateCulture', name: 'territoire.updateCulture')]
    public function updateCultureAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Territoire $territoire,
    ): RedirectResponse|Response {
        $form = $this->createForm(TerritoireCultureForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/culture.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour de la liste des ingrédients fourni par un territoire.
     */
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/{territoire}/updateIngredients', name: 'territoire.updateIngredients')]
    public function updateIngredientsAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Territoire $territoire,
    ): RedirectResponse|Response {
        $form = $this->createForm(TerritoireIngredientsForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'Le territoire a été mis à jour.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/updateIngredients.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute une loi à un territoire.
     */
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/{territoire}/updateLoi', name: 'territoire.updateLoi')]
    public function updateLoiAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Territoire $territoire,
    ): RedirectResponse|Response {
        $form = $this->createForm(TerritoireLoiForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/loi.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour le statut d'un territoire.
     */
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/{territoire}/updateStatut', name: 'territoire.updateStatut')]
    public function updateStatutAction(
        Request $request,
        #[MapEntity] Territoire $territoire,
    ): RedirectResponse|Response {
        $form = $this->createForm(TerritoireStatutForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($territoire);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/updateStatut.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie le jeu strategique d'un territoire.
     */
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::CARTOGRAPHE))]
    #[Route('/territoire/{territoire}/update/strategie', name: 'territoire.updateStrategie')]
    public function updateStrategieAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Territoire $territoire,
    ): RedirectResponse|Response {
        $form = $this->createForm(TerritoireStrategieForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();
            $this->addFlash('success', 'Le territoire a été mis à jour.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/updateStrategie.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }
}
