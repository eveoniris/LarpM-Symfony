<?php

namespace App\Controller;

use App\Entity\Espece;
use App\Entity\User;
use App\Enum\Role;
use App\Form\Espece\EspeceForm;
use App\Repository\EspeceRepository;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/espece', name: 'espece.')]
class EspeceController extends AbstractController
{
    #[Route(name: 'index')]
    #[Route(name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        EspeceRepository $repository,
    ): Response {
        $pagerService->setRequest($request)->setRepository($repository);

        return $this->render('espece/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $repository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/add', name: 'add')]
    #[IsGranted('ROLE_REGLE')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Espece(),
            EspeceForm::class
        );
    }

    #[Route('/{espece}/detail', name: 'detail', requirements: ['espece' => Requirement::DIGITS])]
    #[IsGranted('ROLE_USER')]
    public function detailAction(#[MapEntity] Espece $espece, PersonnageService $personnageService): Response
    {
        $this->checkHasAccess(
            [Role::ORGA, Role::REGLE, Role::SCENARISTE],
            function () use ($espece, $personnageService) {
                /** @var User $user */
                $user = $this->getUser();
                foreach ($user->getPersonnages() as $personnage) {
                    if ($personnageService->hasEspece($personnage, $espece)) {
                        return true;
                    }
                }

                return false;
            });

        return $this->render('espece\detail.twig', [
            'espece' => $espece,
        ]);
    }

    #[Route('/{espece}/udpate', name: 'update', requirements: ['espece' => Requirement::DIGITS])]
    #[IsGranted('ROLE_REGLE')]
    public function updateAction(Request $request, #[MapEntity] Espece $espece): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            $espece,
            EspeceForm::class
        );
    }

    #[Route('/{espece}/delete', name: 'delete', requirements: ['espece' => Requirement::DIGITS])]
    #[IsGranted('ROLE_REGLE')]
    public function deleteAction(
        #[MapEntity] Espece $espece,
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $espece,
            'Supprimer une espece',
            "L'espèce a été supprimée",
            'espece.list',
            [
                ['route' => $this->generateUrl('espece.list'), 'name' => 'Liste des espèces'],
                [
                    'route' => $this->generateUrl('espece.detail', ['espece' => $espece->getId()]),
                    'espece' => $espece->getId(),
                    'name' => $espece->getLabel(),
                ],
                ['name' => 'Supprimer une espèce'],
            ]
        );
    }

    #[Route('/{espece}/personnages', name: 'personnages', requirements: ['espece' => Requirement::DIGITS])]
    #[IsGranted('ROLE_REGLE')]
    public function personnagesAction(
        Request $request,
        #[MapEntity] Espece $espece,
        PersonnageService $personnageService,
        EspeceRepository $especeRepository,
    ): Response {
        $routeName = 'espece.personnages';
        $routeParams = ['espece' => $espece->getId()];
        $twigFilePath = 'personnage/sub_personnages.twig';
        $columnKeys = ['colId', 'colStatut', 'colNom', 'colClasse', 'colGroupe', 'colUser'];
        $personnages = $espece->getPersonnages();
        $additionalViewParams = [
            'espece' => $espece,
            'title' => 'Espèces',
            'breadcrumb' => [
                [
                    'name' => 'Liste des espèces',
                    'route' => $this->generateUrl('espece.list'),
                ],
                [
                    'name' => $espece->getLabel(),
                    'route' => $this->generateUrl('espece.detail', ['espece' => $espece->getId()]),
                ],
                [
                    'name' => 'Personnages ayant cette espèce',
                ],
            ],
        ];

        $viewParams = $personnageService->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages,
            $especeRepository->getPersonnages($espece)
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
        ?callable $entityCallback = null,
    ): RedirectResponse|Response {
        return parent::handleCreateOrUpdate(
            request: $request,
            entity: $entity,
            formClass: $formClass,
            breadcrumb: $breadcrumb,
            routes: $routes,
            msg: [
                'entity' => $this->translator->trans('espece'),
                'entity_added' => $this->translator->trans("L'espèce a été ajoutée"),
                'entity_updated' => $this->translator->trans("L'espèce a été mise à jour"),
                'entity_deleted' => $this->translator->trans("L'espèce a été supprimée"),
                'entity_list' => $this->translator->trans('Liste des espèces'),
                'title_add' => $this->translator->trans('Ajouter une espèce'),
                'title_update' => $this->translator->trans('Modifier une espèce'),
                ...$msg,
            ],
            entityCallback: $entityCallback
        );
    }
}
