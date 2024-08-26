<?php

namespace App\Controller;

use App\Entity\Technologie;
use App\Entity\TechnologiesRessources;
use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Form\Technologie\TechnologieForm;
use App\Form\Technologie\TechnologiesRessourcesForm;
use App\Repository\TechnologieRepository;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
#[Route('/technologie', name: 'technologie.')]
class TechnologieController extends AbstractController
{
    /**
     * Liste des technologie.
     */
    #[Route(name: 'index')]
    #[Route(name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        TechnologieRepository $technologieRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($technologieRepository);

        return $this->render('technologie/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $technologieRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Ajout d'une technologie.
     */
    #[Route('/add', name: 'add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Technologie(),
            TechnologieForm::class
        );
    }

    /**
     * Détail d'une technologie.
     */
    #[Route('/{technologie}/detail', name: 'detail', requirements: ['technologie' => Requirement::DIGITS])]
    public function detailAction(#[MapEntity] Technologie $technologie): Response
    {
        return $this->render('technologie\detail.twig', [
            'technologie' => $technologie,
        ]);
    }

    /**
     * Mise à jour d'une technologie.
     */
    #[Route('/{technologie}/udpate', name: 'update', requirements: ['technologie' => Requirement::DIGITS])]
    public function updateAction(Request $request, #[MapEntity] Technologie $technologie): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            $technologie,
            TechnologieForm::class
        );
    }

    /**
     * Suppression d'une technologie.
     */
    #[Route('/{technologie}/delete', name: 'delete', requirements: ['technologie' => Requirement::DIGITS])]
    public function deleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Technologie $technologie
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $technologie,
            'Supprimer une technologie',
            'La technologie a été supprimée',
            'technologie.list',
            [
                ['route' => $this->generateUrl('technologie.list'), 'name' => 'Liste des technologies'],
                [
                    'route' => $this->generateUrl('technologie.detail', ['technologie' => $technologie->getId()]),
                    'technologie' => $technologie->getId(),
                    'name' => $technologie->getLabel(),
                ],
                ['name' => 'Supprimer une technologie'],
            ]
        );
    }

    /**
     * Liste des personnages ayant cette technologie.
     *
     * @param Technologie
     */
    #[Route('/{technologie}/personnages', name: 'personnages', requirements: ['technologie' => Requirement::DIGITS])]
    public function personnagesAction(
        Request $request,
        #[MapEntity] Technologie $technologie,
        PersonnageService $personnageService,
        TechnologieRepository $technologieRepository
    ): Response {
        $routeName = 'technologie.personnages';
        $routeParams = ['technologie' => $technologie->getId()];
        $twigFilePath = 'technologie/personnages.twig';
        $columnKeys = ['colId', 'colStatut', 'colNom', 'colClasse', 'colGroupe', 'colUser'];
        $personnages = $technologie->getPersonnages();
        $additionalViewParams = [
            'technologie' => $technologie,
        ];

        $viewParams = $personnageService->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages,
            $technologieRepository->getPersonnages($technologie)
        );

        return $this->render(
            $twigFilePath,
            $viewParams
        );
    }

    /**
     * Ajout d'une ressource à une technologie.
     */
    #[Route('/{technologie}/ressource/add', name: 'ressource.add', requirements: ['technologie' => Requirement::DIGITS])]
    public function addRessourceAction(
        Request $request,
        #[MapEntity] Technologie $technologie
    ): RedirectResponse|Response {
        return $this->handleCreateOrUpdate(
            $request,
            new TechnologiesRessources(),
            TechnologiesRessourcesForm::class,
            breadcrumb: [
                ['route' => $this->generateUrl('technologie.list'), 'name' => 'Liste des techologies'],
                [
                    'route' => $this->generateUrl('technologie.detail', ['technologie' => $technologie->getId()]),
                    'name' => $technologie->getLabel(),
                ],
                ['name' => 'Ajouter une ressource de technologie'],
            ],
            routes: [
                'root' => 'technologie.',
                'entityAlias' => 'technologiesRessources',
            ],
            msg: [
                'entity' => $this->translator->trans('ressource'),
                'entity_added' => $this->translator->trans('La ressource a été ajoutée'),
                'entity_updated' => $this->translator->trans('La ressource a été mise à jour'),
                'entity_deleted' => $this->translator->trans('La ressource a été supprimé'),
                'title_add' => $this->translator->trans('Ajouter une ressource'),
                'title_update' => $this->translator->trans('Modifier une ressource'),
            ],
            /* @var TechnologiesRessources $entity */
            entityCallback: static fn ($entity) => $entity->setTechnologie($technologie),
        );
    }

    #[Route('/{technologie}/ressource/{technologiesRessources}/update',
        name: 'ressource.update',
        requirements: ['technologie' => Requirement::DIGITS, 'technologiesRessources' => Requirement::DIGITS])]
    public function updateRessourceAction(
        Request $request,
        #[MapEntity] Technologie $technologie,
        #[MapEntity] TechnologiesRessources $technologiesRessources
    ): RedirectResponse|Response {
        return $this->handleCreateOrUpdate(
            $request,
            $technologiesRessources,
            TechnologiesRessourcesForm::class,
            breadcrumb: [
                ['route' => $this->generateUrl('technologie.list'), 'name' => 'Liste des techologies'],
                [
                    'route' => $this->generateUrl('technologie.detail', ['technologie' => $technologie->getId()]),
                    'name' => $technologie->getLabel(),
                ],
                ['name' => 'Modifier une ressource de technologie'],
            ],
            routes: [
                'root' => 'technologie.',
                'entityAlias' => 'technologiesRessources',
            ],
            msg: [
                'entity' => $this->translator->trans('ressource'),
                'entity_added' => $this->translator->trans('La ressource a été ajoutée'),
                'entity_updated' => $this->translator->trans('La ressource a été mise à jour'),
                'entity_deleted' => $this->translator->trans('La ressource a été supprimé'),
                'title_add' => $this->translator->trans('Ajouter une ressource'),
                'title_update' => $this->translator->trans('Modifier une ressource'),
            ],
            /* @var TechnologiesRessources $entity */
            entityCallback: static fn ($entity) => $entity->setTechnologie($technologie),
        );
    }

    /**
     * Retrait d'une ressource à une technologie.
     */
    #[Route('/{technologie}/ressource/{technologiesRessources}/delete',
        name: 'ressource.delete',
        requirements: ['technologie' => Requirement::DIGITS, 'technologiesRessources' => Requirement::DIGITS]
    )]
    public function removeRessourceAction(
        #[MapEntity] Technologie $technologie,
        #[MapEntity] TechnologiesRessources $technologiesRessources
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $technologiesRessources,
            'Supprimer une ressource de technologie',
            'La ressource technologie a été supprimée',
            'technologie.list',
            [
                ['route' => $this->generateUrl('technologie.list'), 'name' => 'Liste des technologies'],
                [
                    'route' => 'technologie.detail',
                    'technologie' => $technologie->getId(),
                    'name' => $technologie->getLabel(),
                ],
                ['name' => 'Supprimer une technologie'],
            ],
            $technologie->getDescription(),
        );
    }

    /**
     * Obtenir le document lié a une technologie.
     */
    #[Route('/{technologie}/document', name: 'document', requirements: ['technologie' => Requirement::DIGITS])]
    public function getTechnologieDocumentAction(#[MapEntity] Technologie $technologie)
    {
        return $this->sendDocument($technologie);
    }

    protected function handleCreateOrUpdate(
        Request $request,
        $entity,
        string $formClass,
        array $breadcrumb = [],
        array $routes = [],
        array $msg = [],
        ?callable $entityCallback = null
    ): RedirectResponse|Response {
        if (!$entityCallback) {
            /** @var Technologie $technologie */
            $entityCallback = function (mixed $technologie, FormInterface $form): ?Technologie {
                $technologie->handleUpload(
                    $this->fileUploader,
                    DocumentType::Documents,
                    FolderType::Private
                );

                return $technologie;
            };
        }

        return parent::handleCreateOrUpdate(
            request: $request,
            entity: $entity,
            formClass: $formClass,
            breadcrumb: $breadcrumb,
            routes: $routes,
            msg: [
                'entity' => $this->translator->trans('technologie'),
                'entity_added' => $this->translator->trans('La technologie a été ajoutée'),
                'entity_updated' => $this->translator->trans('La technologie a été mise à jour'),
                'entity_deleted' => $this->translator->trans('La technologie a été supprimée'),
                'entity_list' => $this->translator->trans('Liste des technologies'),
                'title_add' => $this->translator->trans('Ajouter une technologie'),
                'title_update' => $this->translator->trans('Modifier une technologie'),
                ...$msg,
            ],
            entityCallback: $entityCallback
        );
    }
}
