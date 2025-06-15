<?php

namespace App\Controller;

use App\Entity\Technologie;
use App\Entity\TechnologiesRessources;
use App\Enum\Role;
use App\Form\Technologie\TechnologieForm;
use App\Form\Technologie\TechnologiesRessourcesForm;
use App\Repository\TechnologieRepository;
use App\Security\MultiRolesExpression;
use App\Service\OrderBy;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/technologie', name: 'technologie.')]
class TechnologieController extends AbstractController
{
    /**
     * Ajout d'une technologie.
     */
    #[Route('/add', name: 'add')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::REGLE))]
    public function addAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Technologie(),
            TechnologieForm::class,
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
        if (!$entityCallback) {
            /** @var Technologie $technologie */
            $entityCallback = fn(mixed $technologie, FormInterface $form): ?Technologie => $technologie->handleUpload(
                $this->fileUploader,
            );
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
            entityCallback: $entityCallback,
        );
    }

    /**
     * Ajout d'une ressource à une technologie.
     */
    #[Route('/{technologie}/ressource/add', name: 'ressource.add', requirements: ['technologie' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::REGLE))]
    public function addRessourceAction(
        Request $request,
        #[MapEntity] Technologie $technologie,
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
            entityCallback: static fn($entity) => $entity->setTechnologie($technologie),
        );
    }

    /**
     * Suppression d'une technologie.
     */
    #[Route('/{technologie}/delete', name: 'delete', requirements: ['technologie' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::REGLE))]
    public function deleteAction(
        #[MapEntity] Technologie $technologie,
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
            ],
        );
    }

    /**
     * Détail d'une technologie.
     */
    #[Route('/{technologie}/detail', name: 'detail', requirements: ['technologie' => Requirement::DIGITS])]
    public function detailAction(#[MapEntity] Technologie $technologie): Response
    {
        $this->checkHasAccess(
            [Role::ORGA, Role::REGLE, Role::SCENARISTE],
            function () use ($technologie) {
                $personnage = $this->getPersonnage();
                $this->checkHasPersonnage($personnage);

                if ($personnage && !$personnage->isKnownTechnologie($technologie)) {
                    $this->addFlash('error', 'Vous ne connaissez pas cette technologie !');

                    return $this->redirectToRoute('homepage');
                }

                return false;
            },
        );

        return $this->render('technologie\detail.twig', [
            'technologie' => $technologie,
        ]);
    }

    /**
     * Obtenir le document lié a une technologie.
     */
    #[Route('/{technologie}/document', name: 'document', requirements: ['technologie' => Requirement::DIGITS])]
    public function getTechnologieDocumentAction(#[MapEntity] Technologie $technologie): BinaryFileResponse
    {
        $this->checkHasAccess(
            [Role::ORGA, Role::REGLE, Role::SCENARISTE],
            function () use ($technologie) {
                $personnage = $this->getPersonnage();
                $this->checkHasPersonnage($personnage);

                if ($personnage && !$personnage->isKnownTechnologie($technologie)) {
                    $this->addFlash('error', 'Vous ne connaissez pas cette technologie !');

                    return $this->redirectToRoute('homepage');
                }

                return false;
            },
        );

        return $this->sendDocument($technologie);
    }

    /**
     * Liste des technologie.
     */
    #[Route(name: 'index')]
    #[Route(name: 'list')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::REGLE))]
    public function indexAction(
        PagerService $pagerService,
        TechnologieRepository $technologieRepository,
    ): Response {
        //$pagerService->setDefaultOrdersBy(['label' => OrderBy::ASC]); // test default overwrite from request

        return $this->render('technologie/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $technologieRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Liste des personnages ayant cette technologie.
     *
     * @param Technologie
     */
    #[Route('/{technologie}/personnages', name: 'personnages', requirements: ['technologie' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::REGLE))]
    public function personnagesAction(
        Request $request,
        #[MapEntity] Technologie $technologie,
        PersonnageService $personnageService,
        TechnologieRepository $technologieRepository,
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
            $technologieRepository->getPersonnages($technologie),
        );

        return $this->render(
            $twigFilePath,
            $viewParams,
        );
    }

    /**
     * Retrait d'une ressource à une technologie.
     */
    #[Route('/{technologie}/ressource/{technologiesRessources}/delete',
        name: 'ressource.delete',
        requirements: ['technologie' => Requirement::DIGITS, 'technologiesRessources' => Requirement::DIGITS]
    )]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::REGLE))]
    public function removeRessourceAction(
        #[MapEntity] Technologie $technologie,
        #[MapEntity] TechnologiesRessources $technologiesRessources,
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
     * Mise à jour d'une technologie.
     */
    #[Route('/{technologie}/udpate', name: 'update', requirements: ['technologie' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::REGLE))]
    public function updateAction(Request $request, #[MapEntity] Technologie $technologie): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            $technologie,
            TechnologieForm::class,
        );
    }

    #[Route('/{technologie}/ressource/{technologiesRessources}/update',
        name: 'ressource.update',
        requirements: ['technologie' => Requirement::DIGITS, 'technologiesRessources' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::REGLE))]
    public function updateRessourceAction(
        Request $request,
        #[MapEntity] Technologie $technologie,
        #[MapEntity] TechnologiesRessources $technologiesRessources,
    ): RedirectResponse|Response {
        return $this->handleCreateOrUpdate(
            $request,
            $technologiesRessources,
            TechnologiesRessourcesForm::class,
            breadcrumb: [
                ['route' => $this->generateUrl('technologie.list'), 'name' => 'Liste des technologies'],
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
            entityCallback: static fn($entity) => $entity->setTechnologie($technologie),
        );
    }
}
