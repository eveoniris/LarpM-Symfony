<?php

namespace App\Controller;

use App\Entity\AttributeType;
use App\Form\AttributeTypeForm;
use App\Repository\AttributeTypeRepository;
use App\Service\PagerService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
#[Route('/attributType', name: 'attributeType.')]
class AttributeTypeController extends AbstractController
{
    #[Route('/', name: 'list')]
    #[Route('/', name: 'index')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        AttributeTypeRepository $attributeTypeRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($attributeTypeRepository);

        return $this->render('attributeType/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $attributeTypeRepository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/add', name: 'add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        $attributeType = new AttributeType();

        return $this->handleCreateOrUpdate($request, $attributeType, AttributeTypeForm::class);
    }

    #[Route('/{attributeType}/update', name: 'update', requirements: ['attributeType' => Requirement::DIGITS])]
    public function updateAction(Request $request, #[MapEntity] AttributeType $attributeType): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            $attributeType,
            AttributeTypeForm::class
        );
    }

    #[Route('/{attributeType}/delete', name: 'delete', requirements: ['attributeType' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    public function deleteAction(#[MapEntity] AttributeType $attributeType): RedirectResponse|Response
    {
        return $this->genericDelete(
            $attributeType,
            title: 'Supprimer un type d\'attribut',
            successMsg: 'Le type d\'attribut a été supprimée',
            redirect: 'attributeType.list',
            breadcrumb: [
                ['route' => $this->generateUrl('attributeType.list'), 'name' => 'Liste des types d\'attributs'],
                [
                    'route' => $this->generateUrl('attributeType.detail', ['attributeType' => $attributeType->getId()]),
                    'name' => $attributeType->getLabel(),
                ],
                ['name' => 'Supprimer un type d\'attribut'],
            ]
        );
    }

    /**
     * Detail d'un attribut.
     */
    #[Route('/{attributeType}', name: 'view', requirements: ['attributeType' => Requirement::DIGITS])]
    #[Route('/{attributeType}', name: 'detail', requirements: ['attributeType' => Requirement::DIGITS])]
    public function detailAction(#[MapEntity] AttributeType $attributeType): RedirectResponse|Response
    {
        return $this->render('attributeType/detail.twig', ['attributeType' => $attributeType]);
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
        return parent::handleCreateOrUpdate(
            request: $request,
            entity: $entity,
            formClass: $formClass,
            breadcrumb: $breadcrumb,
            routes: $routes,
            msg: [
                ...$msg,
                'entity' => $this->translator->trans("type d'attribut"),
                'entity_added' => $this->translator->trans("Le type d'attribut a été ajouté"),
                'entity_updated' => $this->translator->trans("Le type d'attribut a été mis à jour"),
                'entity_deleted' => $this->translator->trans("Le type d'attribut a été supprimé"),
                'entity_list' => $this->translator->trans("Liste des types d'attributs"),
                'title_add' => $this->translator->trans("Ajouter un type d'attribut"),
                'title_update' => $this->translator->trans("Modifier un type d'attribut"),
            ],
            entityCallback: $entityCallback
        );
    }
}
