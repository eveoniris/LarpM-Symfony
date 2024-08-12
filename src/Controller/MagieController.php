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
use App\Form\DomaineDeleteForm;
use App\Form\DomaineForm;
use App\Form\Potion\PotionDeleteForm;
use App\Form\Potion\PotionForm;
use App\Form\PriereDeleteForm;
use App\Form\PriereForm;
use App\Form\SortDeleteForm;
use App\Form\SortForm;
use App\Form\SphereDeleteForm;
use App\Form\SphereForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
    public function sphereListAction(EntityManagerInterface $entityManager): Response
    {
        $spheres = $entityManager->getRepository(Sphere::class)->findAll();

        return $this->render('sphere/list.twig', [
            'spheres' => $spheres,
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
    #[Route('/sphere', name: 'magie.sphere.add')]
    public function sphereAddAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Sphere(),
            SphereForm::class,
            msg: $this->getSphereMsg(),
            entityCallback: $this->getDocumentCallBack()
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
            msg: $this->getSphereMsg(),
            entityCallback: $this->getDocumentCallBack()
        );
    }

    #[Route('/sphere/{sphere}/delete', name: 'sphere.delete', requirements: ['sphere' => Requirement::DIGITS])]
    public function sphereDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Sphere $sphere
    ): RedirectResponse|Response {
        $form = $this->createForm(SphereDeleteForm::class, $sphere)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sphere = $form->getData();

            $entityManager->remove($sphere);
            $entityManager->flush();

            $this->addFlash('success', 'La sphere a été suprimé');

            return $this->redirectToRoute('magie.sphere.list', [], 303);
        }

        return $this->render('sphere/delete.twig', [
            'sphere' => $sphere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des prieres.
     */
    #[Route('/priere', name: 'priere.list')]
    public function priereListAction(EntityManagerInterface $entityManager): Response
    {
        $prieres = $entityManager->getRepository(Priere::class)->findAll();

        return $this->render('priere/list.twig', [
            'prieres' => $prieres,
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
        return function (Priere|Sphere|Sort $entity, FormInterface $form): Priere|Sphere|Sort {
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
            msg: $this->getPriereMsg(),
            entityCallback: $this->getDocumentCallBack()
        );
    }

    /**
     * Supprime une priere.
     */
    #[Route('/priere/{priere}/delete', name: 'priere.delete', requirements: ['priere' => Requirement::DIGITS])]
    public function priereDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Priere $priere
    ): RedirectResponse|Response {
        $form = $this->createForm(PriereDeleteForm::class, $priere)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $priere = $form->getData();

            $entityManager->remove($priere);
            $entityManager->flush();

            $this->addFlash('success', 'La priere a été suprimé');

            return $this->redirectToRoute('magie.priere.list', [], 303);
        }

        return $this->render('priere/delete.twig', [
            'priere' => $priere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une priere.
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
    #[Route('/priere/personnages', name: 'priere.personnages')]
    public function prierePersonnagesAction(
        Request $request,
        Priere $priere
    ): Response {
        $routeName = 'magie.priere.personnages';
        $routeParams = ['priere' => $priere->getId()];
        $twigFilePath = 'admin/priere/personnages.twig';
        $columnKeys = $this->defaultPersonnageListColumnKeys;
        $personnages = $priere->getPersonnages();
        $additionalViewParams = [
            'priere' => $priere,
        ];

        // handle the request and return an array containing the parameters for the view
        $personnageSearchHandler = $app['personnage.manager']->getSearchHandler();

        $viewParams = $personnageSearchHandler->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages
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
    public function potionListAction(EntityManagerInterface $entityManager): Response
    {
        $potions = $entityManager->getRepository(Potion::class)->findAll();

        return $this->render('potion/list.twig', [
            'potions' => $potions,
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
        #[MapEntity] Potion $potion
    ): Response {
        $routeName = 'magie.potion.personnages';
        $routeParams = ['potion' => $potion->getId()];
        $twigFilePath = 'admin/potion/personnages.twig';
        $columnKeys = $this->defaultPersonnageListColumnKeys;
        $personnages = $potion->getPersonnages();
        $additionalViewParams = [
            'potion' => $potion,
        ];

        // handle the request and return an array containing the parameters for the view
        $personnageSearchHandler = $app['personnage.manager']->getSearchHandler();

        $viewParams = $personnageSearchHandler->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages
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
            msg: $this->getPotionMsg(),
            entityCallback: $this->getDocumentCallBack()
        );
    }

    /**
     * Supprime une potion.
     */
    #[Route('/potion/{potion}/delete', name: 'potion.delete', requirements: ['potion' => Requirement::DIGITS])]
    public function potionDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Potion $potion
    ): RedirectResponse|Response {
        $form = $this->createForm(PotionDeleteForm::class, $potion)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $potion = $form->getData();

            $entityManager->remove($potion);
            $entityManager->flush();

            $this->addFlash('success', 'La potion a été suprimé');

            return $this->redirectToRoute('magie.potion.list', [], 303);
        }

        return $this->render('potion/delete.twig', [
            'potion' => $potion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une potion.
     */
    #[Route('/potion/{potion}/document/{document}', name: 'potion.document', requirements: [
        'potion' => Requirement::DIGITS,
        'document' => Requirement::DIGITS, // todo may be a string
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
            msg: $this->getDomaineMsg()
        );
    }

    /**
     * Supprime un domaine de magie.
     */
    #[Route('/domaine/{domaine}', name: 'domaine.delete', requirements: ['domaine' => Requirement::DIGITS])]
    public function domaineDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Domaine $domaine
    ): RedirectResponse|Response {
        $form = $this->createForm(DomaineDeleteForm::class, $domaine)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $domaine = $form->getData();

            $entityManager->remove($domaine);
            $entityManager->flush();

            $this->addFlash('success', 'Le domaine de magie a été suprimé');

            return $this->redirectToRoute('magie.domaine.list', [], 303);
        }

        return $this->render('domaine/delete.twig', [
            'domaine' => $domaine,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des sorts.
     */
    #[Route('/sort', name: 'sort.list')]
    public function sortListAction(EntityManagerInterface $entityManager): Response
    {
        $sorts = $entityManager->getRepository(Sort::class)->findAll();

        return $this->render('sort/list.twig', [
            'sorts' => $sorts,
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
            msg: $this->getSortMsg(),
            entityCallback: $this->getDocumentCallBack()
        );
    }

    /**
     * Supprime un sortilège.
     */
    #[Route('/sort/{sort}/delete', name: 'sort.delete', requirements: ['sort' => Requirement::DIGITS])]
    public function sortDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Sort $sort
    ): RedirectResponse|Response {
        $form = $this->createForm(SortDeleteForm::class, $sort)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sort = $form->getData();

            $entityManager->remove($sort);
            $entityManager->flush();

            $this->addFlash('success', 'Le sort a été supprimé');

            return $this->redirectToRoute('magie.sort.list', [], 303);
        }

        return $this->render('sort/delete.twig', [
            'sort' => $sort,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a un sort.
     */
    #[Route('/sort/{sort}/document/{document}', name: 'sort.document', requirements: ['sort' => Requirement::DIGITS])]
    public function getSortDocumentAction(#[MapEntity] Sort $sort, #[MapEntity] Document $document)
    {
        // TODO on ne peux télécharger que les documents des compétences que l'on connait
        return $this->sendDocument($sort, $document);
    }

    #[Route('/sort/{sort}/personnages', name: 'sort.personnages', requirements: ['sort' => Requirement::DIGITS])]
    public function sortPersonnagesAction(Request $request, #[MapEntity] Sort $sort): Response
    {
        $routeName = 'magie.sort.personnages';
        $routeParams = ['sort' => $sort->getId()];
        $twigFilePath = 'admin/sort/personnages.twig';
        $columnKeys = $this->defaultPersonnageListColumnKeys;
        $personnages = $sort->getPersonnages();
        $additionalViewParams = [
            'sort' => $sort,
        ];

        // handle the request and return an array containing the parameters for the view
        $personnageSearchHandler = $app['personnage.manager']->getSearchHandler();

        $viewParams = $personnageSearchHandler->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages
        );

        return $this->render(
            $twigFilePath,
            $viewParams
        );
    }
}
