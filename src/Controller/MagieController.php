<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Domaine;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Sort;
use App\Entity\Sphere;
use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Form\DomaineForm;
use App\Form\Potion\PotionForm;
use App\Form\PriereForm;
use App\Form\SortForm;
use App\Form\SphereForm;
use App\Repository\PotionRepository;
use App\Repository\PriereRepository;
use App\Repository\SortRepository;
use App\Repository\SphereRepository;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')] // TODO some action may be allowed to player
#[Route('/magie', name: 'magie.')]
class MagieController extends AbstractController
{
    // liste des colonnes à afficher par défaut sur les vues 'personnages' (l'ordre est pris en compte)
    // TODO
    private array $defaultPersonnageListColumnKeys = [
        'colId',
        'colStatut',
        'colNom',
        'colClasse',
        'colGroupe',
        'colUser',
    ];

    /**
     * Liste des sphere.
     */
    #[Route('/sphere', name: 'sphere.list')]
    public function sphereListAction(
        Request $request,
        PagerService $pagerService,
        SphereRepository $sphereRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($sphereRepository);

        return $this->render('sphere/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $sphereRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Detail d'une sphere.
     */
    #[Route('/sphere/{sphere}', name: 'sphere.detail')]
    public function sphereDetailAction(Sphere $sphere): Response
    {
        return $this->render('sphere/detail.twig', [
            'sphere' => $sphere,
        ]);
    }

    /**
     * Ajoute une sphere.
     */
    #[Route('/sphere/add', name: 'sphere.add')]
    public function sphereAddAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Sphere(),
            SphereForm::class,
            routes: ['root' => 'magie.sphere.', 'entityAlias' => 'sphere'],
            msg: $this->getSphereMsg()
        );
    }

    /**
     * Met à jour une sphere.
     */
    #[Route('/sphere/{sphere}/update', name: 'sphere.update', requirements: ['sphere' => Requirement::DIGITS])]
    public function sphereUpdateAction(
        Request $request,
        #[MapEntity] Sphere $sphere
    ): RedirectResponse|Response {
        return $this->handleCreateOrUpdate(
            $request,
            $sphere,
            SphereForm::class,
            routes: ['root' => 'magie.sphere.', 'entityAlias' => 'sphere'],
            msg: $this->getSphereMsg()
        );
    }

    #[Route('/sphere/{sphere}/delete', name: 'sphere.delete', requirements: ['sphere' => Requirement::DIGITS])]
    public function sphereDeleteAction(
        #[MapEntity] Sphere $sphere
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $sphere,
            title: $this->translator->trans('Supprimer une sphère'),
            successMsg: $this->translator->trans('La sphère a été supprimée'),
            redirect: 'magie.sphere.list',
            breadcrumb: [
                ['route' => $this->generateUrl('magie.sphere.list'), 'name' => $this->translator->trans('Liste des sphères')],
                [
                    'route' => $this->generateUrl('magie.sphere.detail', ['sphere' => $sphere->getId()]),
                    'name' => $sphere->getLabel(),
                ],
                ['name' => $this->translator->trans('Supprimer une sphère')],
            ]
        );
    }

    /**
     * Liste des prieres.
     */
    #[Route('/priere', name: 'priere.list')]
    public function priereListAction(
        Request $request,
        PagerService $pagerService,
        PriereRepository $priereRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($priereRepository)->setLimit(25);

        return $this->render('priere/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $priereRepository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/priere/{priere}', name: 'priere.detail', requirements: ['priere' => Requirement::DIGITS])]
    public function priereDetailAction(#[MapEntity] Priere $priere): Response
    {
        return $this->render('priere/detail.twig', ['priere' => $priere]);
    }

    /**
     * Ajoute une priere.
     */
    #[Route('/priere/add', name: 'priere.add')]
    public function priereAddAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Priere(),
            PriereForm::class,
            routes: ['root' => 'magie.priere.', 'entityAlias' => 'priere'],
            msg: $this->getPriereMsg(),
            entityCallback: $this->getDocumentCallBack()
        );
    }

    protected function getPriereMsg(): array
    {
        return [
            'entity' => $this->translator->trans('prière'),
            'entity_added' => $this->translator->trans('La prière a été ajoutée'),
            'entity_updated' => $this->translator->trans('La prière a été mise à jour'),
            'entity_deleted' => $this->translator->trans('La prière a été supprimée'),
            'entity_list' => $this->translator->trans('Liste des prières'),
            'title_add' => $this->translator->trans('Ajouter une prière'),
            'title_update' => $this->translator->trans('Modifier une prière'),
        ];
    }

    protected function getSphereMsg(): array
    {
        return [
            'entity' => $this->translator->trans('sphère'),
            'entity_added' => $this->translator->trans('La sphère a été ajoutée'),
            'entity_updated' => $this->translator->trans('La sphère a été mise à jour'),
            'entity_deleted' => $this->translator->trans('La sphère a été supprimée'),
            'entity_list' => $this->translator->trans('Liste des sphères'),
            'title_add' => $this->translator->trans('Ajouter une sphère'),
            'title_update' => $this->translator->trans('Modifier une sphère'),
        ];
    }

    protected function getPotionMsg(): array
    {
        return [
            'entity' => $this->translator->trans('potion'),
            'entity_added' => $this->translator->trans('La potion a été ajoutée'),
            'entity_updated' => $this->translator->trans('La potion a été mise à jour'),
            'entity_deleted' => $this->translator->trans('La potion a été supprimée'),
            'entity_list' => $this->translator->trans('Liste des potions'),
            'title_add' => $this->translator->trans('Ajouter une potion'),
            'title_update' => $this->translator->trans('Modifier une potion'),
        ];
    }

    protected function getDomaineMsg(): array
    {
        return [
            'entity' => $this->translator->trans('domaine'),
            'entity_added' => $this->translator->trans('Le domaine a été ajouté'),
            'entity_updated' => $this->translator->trans('Le domaine a été mise à jour'),
            'entity_deleted' => $this->translator->trans('Le domaine a été supprimé'),
            'entity_list' => $this->translator->trans('Liste des domaines'),
            'title_add' => $this->translator->trans('Ajouter un domaine'),
            'title_update' => $this->translator->trans('Modifier un domaine'),
        ];
    }

    protected function getSortMsg(): array
    {
        return [
            'entity' => $this->translator->trans('sort'),
            'entity_added' => $this->translator->trans('Le sort a été ajouté'),
            'entity_updated' => $this->translator->trans('Le sort a été mise à jour'),
            'entity_deleted' => $this->translator->trans('Le sort a été supprimé'),
            'entity_list' => $this->translator->trans('Liste des sorts'),
            'title_add' => $this->translator->trans('Ajouter un sort'),
            'title_update' => $this->translator->trans('Modifier un sort'),
        ];
    }

    protected function getDocumentCallBack(): \Closure
    {
        return function (Priere|Sphere|Sort|Potion $entity, FormInterface $form): Priere|Sphere|Sort|Potion {
            $entity->handleUpload(
                $this->fileUploader,
                DocumentType::Documents,
                FolderType::Private
            );

            return $entity;
        };
    }

    /**
     * Met à jour une priere.
     */
    #[Route('/priere/{priere}/update', name: 'priere.update', requirements: ['priere' => Requirement::DIGITS])]
    public function priereUpdateAction(Request $request, #[MapEntity] Priere $priere): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            $priere,
            PriereForm::class,
            routes: ['root' => 'magie.priere.', 'entityAlias' => 'priere'],
            msg: $this->getPriereMsg(),
            entityCallback: $this->getDocumentCallBack()
        );
    }

    /**
     * Supprime une priere.
     */
    #[Route('/priere/{priere}/delete', name: 'priere.delete', requirements: ['priere' => Requirement::DIGITS])]
    public function priereDeleteAction(
        #[MapEntity] Priere $priere
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $priere,
            title: $this->translator->trans('Supprimer une prière'),
            successMsg: $this->translator->trans('La prière a été supprimée'),
            redirect: 'magie.priere.list',
            breadcrumb: [
                ['route' => $this->generateUrl('magie.priere.list'), 'name' => $this->translator->trans('Liste des prières')],
                [
                    'route' => $this->generateUrl('magie.priere.detail', ['priere' => $priere->getId()]),
                    'name' => $priere->getLabel(),
                ],
                ['name' => $this->translator->trans('Supprimer une prière')],
            ]
        );
    }

    /**
     * Obtenir le document lié a une priere.
     * Todo voir pour un autre Ctrl pour autoriser les DL des User ou modifier le ROlE pour utiliser un VOTER.
     */
    #[Route('/priere/{priere}/document', name: 'priere.document', requirements: ['priere' => Requirement::DIGITS])]
    public function getPriereDocumentAction(Request $request, #[MapEntity] Priere $priere)
    {
        // Multi doc ? $document = $request->get('document');

        // TODO on ne peux télécharger que les documents des compétences que l'on connait
        /*if  ( ! $app['security.authorization_checker']->isGranted('ROLE_REGLE') )
        {
        if ( $this->getUser()->getPersonnage() )
        {
        if ( ! $this->getUser()->getPersonnage()->getCompetences()->contains($competence) )
        {
       $this->addFlash('error', 'Vous n\'avez pas les droits necessaires');
        }
        }
        }*/

        return $this->sendDocument($priere);
    }

    protected function sendDocument(mixed $entity, ?Document $document = null): BinaryFileResponse
    {
        // TODO check usage of entity Document

        // TODO on ne peux télécharger que les documents des compétences que l'on connait
        $filename = $entity->getDocument($this->fileUploader->getProjectDirectory());
        if (!$entity->getDocumentUrl() || !file_exists($filename)) {
            throw new NotFoundHttpException("Le document n'existe pas");
        }

        $response = (new BinaryFileResponse($filename, Response::HTTP_OK))
            ->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $entity->getPrintLabel().'.pdf');

        $response->headers->set('Content-Control', 'private');
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Type', 'application/pdf');

        return $response;
    }

    /**
     * Liste des personnages ayant cette prière.
     */
    // TODO
    #[Route('/priere/{priere}/personnages', name: 'priere.personnages', requirements: ['priere' => Requirement::DIGITS])]
    public function prierePersonnagesAction(
        Request $request,
        #[MapEntity] Priere $priere,
        PersonnageService $personnageService,
        PriereRepository $priereRepository
    ): Response {
        $routeName = 'magie.priere.personnages';
        $routeParams = ['priere' => $priere->getId()];
        $twigFilePath = 'priere/personnages.twig';
        $columnKeys = [
            'colId',
            'colStatut',
            'colNom',
            'colClasse',
            'colGroupe',
            'colUser',
        ];
        $personnages = $priere->getPersonnages();
        $additionalViewParams = [
            'priere' => $priere,
        ];

        $viewParams = $personnageService->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages,
            $priereRepository->getPersonnages($priere)
        );

        return $this->render(
            $twigFilePath,
            $viewParams
        );
    }

    /**
     * Liste des potions.
     */
    #[Route('/potion', name: 'potion.list')]
    public function potionListAction(
        Request $request,
        PagerService $pagerService,
        PotionRepository $potionRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($potionRepository)->setLimit(25);

        return $this->render('potion/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $potionRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Detail d'une potion.
     */
    #[Route('/potion/{potion}', name: 'potion.detail', requirements: ['potion' => Requirement::DIGITS])]
    public function potionDetailAction(#[MapEntity] Potion $potion): Response
    {
        return $this->render('potion/detail.twig', [
            'potion' => $potion,
        ]);
    }

    #[Route('/potion/{potion}/personnages', name: 'potion.personnages', requirements: ['potion' => Requirement::DIGITS])]
    public function potionPersonnagesAction(
        Request $request,
        #[MapEntity] Potion $potion,
        PersonnageService $personnageService,
        PotionRepository $potionRepository
    ): Response {
        $routeName = 'magie.potion.personnages';
        $routeParams = ['potion' => $potion->getId()];
        $twigFilePath = 'potion/personnages.twig';
        $columnKeys = [
            'colId',
            'colStatut',
            'colNom',
            'colClasse',
            'colGroupe',
            'colUser',
        ];
        $personnages = $potion->getPersonnages();
        $additionalViewParams = [
            'potion' => $potion,
        ];

        $viewParams = $personnageService->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages,
            $potionRepository->getPersonnages($potion)
        );

        return $this->render(
            $twigFilePath,
            $viewParams
        );
    }

    /**
     * Ajoute une potion.
     */
    #[Route('/potion/add', name: 'potion.add')]
    public function potionAddAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Potion(),
            PotionForm::class,
            routes: ['root' => 'magie.potion.', 'entityAlias' => 'potion'],
            msg: $this->getPotionMsg(),
            entityCallback: $this->getDocumentCallBack()
        );
    }

    /**
     * Met à jour une potion.
     */
    #[Route('/potion/{potion}/update', name: 'potion.update', requirements: ['potion' => Requirement::DIGITS])]
    public function potionUpdateAction(
        Request $request,
        #[MapEntity] Potion $potion
    ): RedirectResponse|Response {
        return $this->handleCreateOrUpdate(
            $request,
            $potion,
            PotionForm::class,
            routes: ['root' => 'magie.potion.', 'entityAlias' => 'potion'],
            msg: $this->getPotionMsg(),
            entityCallback: $this->getDocumentCallBack()
        );
    }

    /**
     * Supprime une potion.
     */
    #[Route('/potion/{potion}/delete', name: 'potion.delete', requirements: ['potion' => Requirement::DIGITS])]
    public function potionDeleteAction(
        #[MapEntity] Potion $potion
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $potion,
            title: $this->translator->trans('Supprimer une potion'),
            successMsg: $this->translator->trans('La potion a été supprimée'),
            redirect: 'magie.potion.list',
            breadcrumb: [
                ['route' => $this->generateUrl('magie.potion.list'), 'name' => $this->translator->trans('Liste des potions')],
                [
                    'route' => $this->generateUrl('magie.potion.detail', ['potion' => $potion->getId()]),
                    'name' => $potion->getLabel(),
                ],
                ['name' => $this->translator->trans('Supprimer une potion')],
            ]
        );
    }

    /**
     * Obtenir le document lié a une potion.
     */
    #[Route('/potion/{potion}/document/{document}', name: 'potion.document', requirements: [
        'potion' => Requirement::DIGITS,
        // 'document' => Requirement::ASCII_SLUG, // todo may be a string
    ])]
    public function getPotionDocumentAction(
        Request $request,
        #[MapEntity] Potion $potion,
        #[MapEntity] Document $document
    ): Response {
        return $this->sendDocument($potion, $document);
    }

    /**
     * Liste des domaines de magie.
     */
    #[Route('/domaine', name: 'domaine.list')]
    public function domaineListAction(EntityManagerInterface $entityManager): Response
    {
        $domaines = $entityManager->getRepository(Domaine::class)->findAll();

        return $this->render('domaine/list.twig', [
            'domaines' => $domaines,
        ]);
    }

    /**
     * Detail d'un domaine de magie.
     */
    #[Route('/domaine/{domaine}/detail', name: 'domaine.detail', requirements: ['domaine' => Requirement::DIGITS])]
    public function domaineDetailAction(#[MapEntity] Domaine $domaine): Response
    {
        return $this->render('domaine/detail.twig', [
            'domaine' => $domaine,
        ]);
    }

    /**
     * Ajoute un domaine de magie.
     */
    #[Route('/domaine/add', name: 'domaine.add')]
    public function domaineAddAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Domaine(),
            DomaineForm::class,
            routes: ['root' => 'magie.domaine.', 'entityAlias' => 'domaine'],
            msg: $this->getDomaineMsg()
        );
    }

    /**
     * Met à jour un domaine de magie.
     */
    #[Route('/domaine/{domaine}/update', name: 'domaine.update', requirements: ['domaine' => Requirement::DIGITS])]
    public function domaineUpdateAction(
        Request $request,
        #[MapEntity] Domaine $domaine
    ): RedirectResponse|Response {
        return $this->handleCreateOrUpdate(
            $request,
            $domaine,
            DomaineForm::class,
            routes: ['root' => 'magie.domaine.', 'entityAlias' => 'domaine'],
            msg: $this->getDomaineMsg()
        );
    }

    /**
     * Supprime un domaine de magie.
     */
    #[Route('/domaine/{domaine}', name: 'domaine.delete', requirements: ['domaine' => Requirement::DIGITS])]
    public function domaineDeleteAction(
        #[MapEntity] Domaine $domaine
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $domaine,
            title: $this->translator->trans('Supprimer un domaine'),
            successMsg: $this->translator->trans('Le domaine a été supprimé'),
            redirect: 'magie.domaine.list',
            breadcrumb: [
                ['route' => $this->generateUrl('magie.domaine.list'), 'name' => $this->translator->trans('Liste des domaines')],
                [
                    'route' => $this->generateUrl('magie.domaine.detail', ['sort' => $domaine->getId()]),
                    'name' => $domaine->getLabel(),
                ],
                ['name' => $this->translator->trans('Supprimer un domaine')],
            ]
        );
    }

    /**
     * Liste des sorts.
     */
    #[Route('/sort', name: 'sort.list')]
    public function sortListAction(
        Request $request,
        PagerService $pagerService,
        SortRepository $sortRepository
    ): Response {
        $pagerService->setRequest($request);

        return $this->render('sort/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $sortRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Detail d'un sort.
     */
    #[Route('/sort/{sort}/detail', name: 'sort.detail', requirements: ['sort' => Requirement::DIGITS])]
    public function sortDetailAction(#[MapEntity] Sort $sort): Response
    {
        return $this->render('sort/detail.twig', [
            'sort' => $sort,
        ]);
    }

    /**
     * Ajoute un sort.
     */
    #[Route('/sort/add', name: 'sort.add', requirements: ['sort' => Requirement::DIGITS])]
    public function sortAddAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Sort(),
            SortForm::class,
            routes: ['root' => 'magie.sort.', 'entityAlias' => 'sort'],
            msg: $this->getSortMsg(),
            entityCallback: $this->getDocumentCallBack()
        );
    }

    /**
     * Met à jour un sort.
     */
    #[Route('/sort/{sort}/update', name: 'sort.update', requirements: ['sort' => Requirement::DIGITS])]
    public function sortUpdateAction(
        Request $request,
        #[MapEntity] Sort $sort
    ): RedirectResponse|Response {
        return $this->handleCreateOrUpdate(
            $request,
            $sort,
            SortForm::class,
            routes: ['root' => 'magie.sort.', 'entityAlias' => 'sort'],
            msg: $this->getSortMsg(),
            entityCallback: $this->getDocumentCallBack()
        );
    }

    /**
     * Supprime un sortilège.
     */
    #[Route('/sort/{sort}/delete', name: 'sort.delete', requirements: ['sort' => Requirement::DIGITS])]
    public function sortDeleteAction(
        #[MapEntity] Sort $sort
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $sort,
            title: $this->translator->trans('Supprimer un sort'),
            successMsg: $this->translator->trans('Le sort a été supprimé'),
            redirect: 'magie.sort.list',
            breadcrumb: [
                ['route' => $this->generateUrl('magie.sort.list'), 'name' => $this->translator->trans('Liste des sorts')],
                [
                    'route' => $this->generateUrl('magie.sort.detail', ['sort' => $sort->getId()]),
                    'name' => $sort->getLabel(),
                ],
                ['name' => $this->translator->trans('Supprimer un sort')],
            ]
        );
    }

    /**
     * Obtenir le document lié a un sort.
     */
    // TODO migrate documentUrl as documentId to Entity Document
    #[Route('/sort/{sort}/document/{document}', name: 'sort.document', requirements: ['sort' => Requirement::DIGITS])]
    public function getSortDocumentAction(#[MapEntity] Sort $sort, #[MapEntity] Document $document): BinaryFileResponse
    {
        // TODO on ne peux télécharger que les documents des compétences que l'on connait
        return $this->sendDocument($sort, $document);
    }

    #[Route('/sort/{sort}/personnages', name: 'sort.personnages', requirements: ['sort' => Requirement::DIGITS])]
    public function sortPersonnagesAction(
        Request $request,
        #[MapEntity] Sort $sort,
        PersonnageService $personnageService,
        SortRepository $sortRepository
    ): Response {
        $routeName = 'magie.sort.personnages';
        $routeParams = ['sort' => $sort->getId()];
        $twigFilePath = 'sort/personnages.twig';
        $columnKeys = [
            'colId',
            'colStatut',
            'colNom',
            'colClasse',
            'colGroupe',
            'colUser',
        ];
        $personnages = $sort->getPersonnages();
        $additionalViewParams = [
            'sort' => $sort,
        ];

        $viewParams = $personnageService->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages,
            $sortRepository->getPersonnages($sort)
        );

        return $this->render(
            $twigFilePath,
            $viewParams
        );
    }
}
