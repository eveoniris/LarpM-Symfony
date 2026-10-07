<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Bonus;
use App\Entity\Espece;
use App\Entity\EspeceBonus;
use App\Entity\User;
use App\Enum\Role;
use App\Enum\Status;
use App\Form\Espece\EspeceType;
use App\Repository\BonusRepository;
use App\Repository\EspeceRepository;
use App\Security\MultiRolesExpression;
use App\Service\PagerService;
use App\Service\PersonnageService;
use DateTime;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/espece', name: 'espece.')]
class EspeceController extends AbstractController
{
    #[Route(name: 'index')]
    #[Route(name: 'list')]
    #[IsGranted(new MultiRolesExpression(Role::CARTOGRAPHE, Role::SCENARISTE), message: 'You are not allowed to access to this.')]
    public function indexAction(Request $request, PagerService $pagerService, EspeceRepository $repository): Response
    {
        $pagerService->setRequest($request)->setRepository($repository);

        $this->setCan(self::IS_ADMIN, $this->isGranted(Role::COHERENCE->value));

        return $this->render('espece/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $repository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/add', name: 'add')]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function addAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate($request, new Espece(), EspeceType::class);
    }

    #[Route('/{espece}/detail', name: 'detail', requirements: ['espece' => Requirement::DIGITS])]
    #[IsGranted('ROLE_USER')]
    public function detailAction(#[MapEntity] Espece $espece, PersonnageService $personnageService): Response
    {
        $this->checkHasAccess([Role::CARTOGRAPHE, Role::SCENARISTE], function () use ($espece, $personnageService) {
            /** @var User $user */
            $user = $this->getUser();
            foreach ($user->getPersonnages() as $personnage) {
                if ($personnageService->hasEspece($personnage, $espece)) {
                    return true;
                }
            }

            return false;
        });

        $isAdmin = $this->isGranted(Role::CARTOGRAPHE->value) || $this->isGranted(Role::SCENARISTE->value);

        return $this->render('espece/detail.twig', [
            'espece' => $espece,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/{espece}/udpate', name: 'update', requirements: ['espece' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function updateAction(Request $request, #[MapEntity] Espece $espece): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate($request, $espece, EspeceType::class);
    }

    #[Route('/{espece}/delete', name: 'delete', requirements: ['espece' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function deleteAction(#[MapEntity] Espece $espece): RedirectResponse|Response
    {
        return $this->genericDelete($espece, 'Supprimer une espece', "L'espèce a été supprimée", 'espece.list', [
            ['route' => $this->generateUrl('espece.list'), 'name' => 'Liste des espèces'],
            [
                'route' => $this->generateUrl('espece.detail', ['espece' => $espece->getId()]),
                'espece' => $espece->getId(),
                'name' => $espece->getLabel(),
            ],
            ['name' => 'Supprimer une espèce'],
        ]);
    }

    #[Route('/{espece}/personnages', name: 'personnages', requirements: ['espece' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::CARTOGRAPHE, Role::SCENARISTE))]
    public function personnagesAction(
        Request $request,
        #[MapEntity]
        Espece $espece,
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

        $viewParams = $personnageService->getSearchViewParameters($request, $routeName, $routeParams, $columnKeys, $additionalViewParams, $personnages, $especeRepository->getPersonnages($espece));

        return $this->render($twigFilePath, $viewParams);
    }

    #[Route('/{espece}/bonus', name: 'bonus', requirements: ['espece' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function bonusAction(
        Request $request,
        #[MapEntity]
        Espece $espece,
        BonusRepository $bonusRepository,
    ): RedirectResponse|Response {
        $bonusChoices = $bonusRepository->findBy([], ['titre' => 'ASC']);

        $form = $this
            ->createFormBuilder()
            ->add('bonus', EntityType::class, [
                'required' => true,
                'label' => 'Choisissez un bonus à ajouter',
                'class' => Bonus::class,
                'choices' => $bonusChoices,
                'choice_label' => static fn (Bonus $bonus) => ($bonus->getTitre() ?? '—') . ' (' . ($bonus->getType()?->value ?? '?') . ')',
                'autocomplete' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Ajouter le bonus',
                'attr' => ['class' => 'btn btn-secondary'],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Bonus $bonus */
            $bonus = $form->getData()['bonus'];

            $especeBonus = new EspeceBonus();
            $especeBonus
                ->setEspece($espece)
                ->setBonus($bonus)
                ->setCreationDate(new DateTime())
                ->setStatus(Status::ACTIVE);

            $this->entityManager->persist($especeBonus);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le bonus a été ajouté à l\'espèce.');

            return $this->redirectToRoute('espece.bonus', ['espece' => $espece->getId()], 303);
        }

        return $this->render('espece/bonus.twig', [
            'espece' => $espece,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{espece}/bonus/{especeBonus}/delete', name: 'bonus.delete', requirements: ['espece' => Requirement::DIGITS, 'especeBonus' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::COHERENCE))]
    public function bonusDeleteAction(
        #[MapEntity]
        Espece $espece,
        #[MapEntity]
        EspeceBonus $especeBonus,
    ): RedirectResponse {
        $espece->removeEspeceBonus($especeBonus);
        $this->entityManager->remove($especeBonus);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le bonus a été retiré de l\'espèce.');

        return $this->redirectToRoute('espece.bonus', ['espece' => $espece->getId()], 303);
    }

    /** @param array<int, array<string, string|null>> $breadcrumb @param array<string, string> $routes @param array<string, string> $msg */
    protected function handleCreateOrUpdate(
        Request $request,
        object $entity,
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
            entityCallback: $entityCallback,
        );
    }
}
