<?php

namespace App\Controller;

use App\Entity\Connaissance;
use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Form\ConnaissanceForm;
use App\Repository\ConnaissanceRepository;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
#[Route('/connaissance', name: 'connaissance.')]
class ConnaissanceController extends AbstractController
{
    #[Route(name: 'list')]
    public function listAction(
        Request $request,
        PagerService $pagerService,
        ConnaissanceRepository $connaissanceRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($connaissanceRepository);

        return $this->render('connaissance/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $connaissanceRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Detail d'une connaissance.
     */
    #[Route('/{connaissance}', name: 'detail', requirements: ['connaissance' => Requirement::DIGITS])]
    public function detailAction(Request $request, #[MapEntity] Connaissance $connaissance): Response
    {
        return $this->render('connaissance/detail.twig', [
            'connaissance' => $connaissance,
        ]);
    }

    /**
     * Ajoute une connaissance.
     */
    #[Route('/add', name: 'add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $connaissance = new Connaissance();

        return $this->handleCreateOrUpdate(
            $request,
            $connaissance,
            ConnaissanceForm::class
        );
    }

    /**
     * Met à jour une connaissance.
     */
    #[Route('/{connaissance}/update', name: 'update', requirements: ['connaissance' => Requirement::DIGITS])]
    public function updateAction(Request $request, #[MapEntity] Connaissance $connaissance): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            $connaissance,
            ConnaissanceForm::class
        );
    }

    /**
     * Supprime une connaissance.
     */
    #[Route('/{connaissance}/delete', name: 'delete', requirements: ['connaissance' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    public function deleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Connaissance $connaissance
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $connaissance,
            'Supprimer une connaissance',
            'La connaissance a été supprimée',
            'connaissance.list',
            [
                ['route' => $this->generateUrl('connaissance.list'), 'name' => 'Liste des connaissances'],
                [
                    'route' => $this->generateUrl('connaissance.detail', ['connaissance' => $connaissance->getId()]),
                    'connaissance' => $connaissance->getId(),
                    'name' => $connaissance->getLabel(),
                ],
                ['name' => 'Supprimer une connaissance'],
            ]
        );
    }

    /**
     * Obtenir le document lié a une connaissance.
     */
    #[Route('/{connaissance}/document', name: 'document', requirements: ['connaissance' => Requirement::DIGITS])]
    public function getDocumentAction(#[MapEntity] Connaissance $connaissance): BinaryFileResponse
    {
        return $this->sendDocument($connaissance);
    }

    #[Route('/{connaissance}/personnages', name: 'personnages', requirements: ['connaissance' => Requirement::DIGITS])]
    public function personnagesAction(
        Request $request,
        #[MapEntity] Connaissance $connaissance,
        PersonnageService $personnageService,
        ConnaissanceRepository $connaissanceRepository
    ): Response {
        $routeName = 'connaissance.personnages';
        $routeParams = ['connaissance' => $connaissance->getId()];
        $twigFilePath = 'connaissance/personnages.twig';
        $columnKeys = [
            'colId',
            'colStatut',
            'colNom',
            'colClasse',
            'colGroupe',
            'colUser',
        ]; // check if it's better in PersonnageService
        $personnages = $connaissance->getPersonnages();
        $additionalViewParams = [
            'connaissance' => $connaissance,
        ];

        // handle the request and return an array containing the parameters for the view
        $viewParams = $personnageService->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages,
            $connaissanceRepository->getPersonnages($connaissance)
        );

        return $this->render(
            $twigFilePath,
            $viewParams
        );
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
            /** @var Connaissance $connaissance */
            $entityCallback = function (mixed $connaissance, FormInterface $form): ?Connaissance {
                $connaissance->handleUpload(
                    $this->fileUploader,
                    DocumentType::Documents,
                    FolderType::Private
                );

                return $connaissance;
            };
        }

        return parent::handleCreateOrUpdate(
            request: $request,
            entity: $entity,
            formClass: $formClass,
            breadcrumb: $breadcrumb,
            routes: $routes,
            msg: [
                ...$msg,
                'entity' => $this->translator->trans('connaissance'),
                'entity_added' => $this->translator->trans('La connaissance a été ajoutée'),
                'entity_updated' => $this->translator->trans('La connaissance a été mise à jour'),
                'entity_deleted' => $this->translator->trans('La connaissance a été supprimée'),
                'entity_list' => $this->translator->trans('Liste des connaissances'),
                'title_add' => $this->translator->trans('Ajouter une connaissance'),
                'title_update' => $this->translator->trans('Modifier une connaissance'),
            ],
            entityCallback: $entityCallback
        );
    }
}
