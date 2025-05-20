<?php

namespace App\Controller;

use App\Entity\AttributeType;
use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\Level;
use App\Enum\LevelType;
use App\Enum\Role;
use App\Form\CompetenceForm;
use App\Repository\CompetenceFamilyRepository;
use App\Repository\CompetenceRepository;
use App\Repository\LevelRepository;
use App\Security\MultiRolesExpression;
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

#[Route('/competence', name: 'competence.')]
class CompetenceController extends AbstractController
{
    /**
     * Ajout d'une compétence.
     */
    #[Route('/competence/add', name: 'add')]
    #[IsGranted('ROLE_REGLE')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        $competence = new Competence();

        // l'identifiant de la famille de competence peux avoir été passé en paramètre
        // pour initialiser le formulaire avec une valeur par défaut.

        // TODO : dans ce cas, il ne faut proposer que les niveaux pour lesquels une compétence
        // n'a pas été défini pour cette famille
        // voir si réalisable dans le Forma

        $competenceFamilyId = $request->get('competenceFamily');
        $levelIndex = $request->get('level');

        if ($competenceFamilyId) {
            $competenceFamily = $this->entityManager->find(CompetenceFamily::class, $competenceFamilyId);
            if ($competenceFamily) {
                $competence->setCompetenceFamily($competenceFamily);
            }
        }

        if ($levelIndex) {
            /** @var LevelRepository $repo */
            $repo = $this->entityManager->getRepository(Level::class);
            $level = $repo->findOneBy(['index' => $levelIndex + 1]);
            if ($level) {
                $competence->setLevel($level);
            }
        }

        return $this->handleCreateOrUpdate(
            $request,
            $competence,
            CompetenceForm::class,
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
            /** @var Competence $competence */
            $entityCallback = fn(mixed $competence, FormInterface $form): ?Competence => $competence->handleUpload(
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
                ...$msg,
                'entity' => $this->translator->trans('competence'),
                'entity_added' => $this->translator->trans('La competence a été ajoutée'),
                'entity_updated' => $this->translator->trans('La competence a été mise à jour'),
                'entity_deleted' => $this->translator->trans('La competence a été supprimée'),
                'entity_list' => $this->translator->trans('Liste des competences'),
                'title_add' => $this->translator->trans('Ajouter une competence'),
                'title_update' => $this->translator->trans('Modifier une competence'),
            ],
            entityCallback: $entityCallback,
        );
    }

    #[Route('/{competence}/delete', name: 'delete', requirements: ['competence' => Requirement::DIGITS], methods: [
        'DELETE',
        'GET',
        'POST',
    ])]
    #[IsGranted('ROLE_REGLE')]
    public function deleteAction(
        #[MapEntity] Competence $competence,
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $competence,
            'Supprimer une competence',
            'La competence a été supprimée',
            'competence.list',
            [
                ['route' => $this->generateUrl('competence.list'), 'name' => 'Liste des compétences'],
                [
                    'route' => $this->generateUrl('competence.detail', ['competence' => $competence->getId()]),
                    'competence' => $competence->getId(),
                    'name' => $competence->getLabel(),
                ],
                ['name' => 'Supprimer une competence'],
            ],
        );
    }

    /**
     * Detail d'une compétence.
     */
    #[Route('/{competence}', name: 'detail')]
    #[IsGranted('ROLE_REGLE')]
    public function detailAction(#[MapEntity] Competence $competence): Response
    {
        return $this->render('competence/detail.twig', ['competence' => $competence]);
    }

    /**
     * Téléchargement du document lié à une compétence.
     */
    #[Route('/{competence}/document', name: 'document', requirements: ['competence' => Requirement::DIGITS])]
    // TODO a voter strategies ?
    public function getDocumentAction(
        CompetenceRepository $competenceRepository,
        #[MapEntity] Competence $competence,
    ) {
        // on ne peut télécharger que les documents des compétences que l'on connait
        if (!$this->getUser()) {
            return $this->render('security/denied.html.twig');
        }

        $hasCompetence = $competenceRepository->userHasCompetence(
            $this->getUser(),
            $competence,
        ); // $this->getUser()->getPersonnages()->getCompetences()->contains($competence)

        if (!$this->isGranted(Role::REGLE->value) && !$hasCompetence) {
            return $this->render('security/denied.html.twig');
        }

        return $this->sendDocument($competence);
    }

    /**
     * Liste des compétences.
     */
    #[Route('', name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        CompetenceRepository $competenceRepository,
    ): Response {
        $pagerService->setRequest($request)->setRepository($competenceRepository)->setLimit(100);

        $alias = $competenceRepository->getAlias();
        $queryBuilder = $competenceRepository->createQueryBuilder($alias)
            ->orderBy('competenceFamily.label', 'ASC')
            ->addOrderBy('level.index', 'ASC');

        if (!$this->isGranted('ROLE_REGLE')) {
            $queryBuilder = $competenceRepository->level($queryBuilder, LevelType::APPRENTICE);
            $queryBuilder = $competenceRepository->secret($queryBuilder, false);
        }

        return $this->render('competence/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $competenceRepository->searchPaginated($pagerService, $queryBuilder),
        ]);
    }

    /**
     * Liste du matériel necessaire par compétence.
     */
    #[Route('/competence/materiel', name: 'materiel')]
    #[IsGranted(new MultiRolesExpression(
        Role::SCENARISTE, Role::REGLE, Role::ORGA,
    ), message: 'You are not allowed to access to this.')]
    public function materielAction(CompetenceRepository $competenceRepository): Response
    {
        return $this->render(
            'competence/materiel.twig',
            ['competences' => $competenceRepository->findAllOrderedByLabel()],
        );
    }

    /**
     * Liste des perso ayant cette compétence.
     */
    #[Route('/perso', name: 'perso')]
    #[IsGranted(new MultiRolesExpression(
        Role::SCENARISTE, Role::REGLE, Role::ORGA,
    ), message: 'You are not allowed to access to this.')]
    public function persoAction(Request $request): Response
    {
        $competence = $request->get('competence');

        return $this->render('competence/perso.twig', ['competence' => $competence]);
    }

    #[Route('/{competence}/personnages', name: 'personnages', requirements: ['competence' => Requirement::DIGITS])]
    #[IsGranted('ROLE_REGLE')]
    public function personnagesAction(
        Request $request,
        #[MapEntity] Competence $competence,
        PersonnageService $personnageService,
        CompetenceRepository $competenceRepository,
    ): Response {
        $routeName = 'competence.personnages';
        $routeParams = ['competence' => $competence->getId()];
        $twigFilePath = 'personnage/sub_personnages.twig';
        $columnKeys = [
            'colId',
            'colStatut',
            'colNom',
        ]; // check if it's better in PersonnageService
        $personnages = $competence->getPersonnages();
        $competenceFamily = $competence->getCompetenceFamily();
        $additionalViewParams = [
            'competence' => $competence,
            'title' => 'Competence',
            'extraData' => $competenceFamily?->getCompetenceFamilyType()?->value,
            'extraDataTitle' => 'Niveau de '.$competenceFamily?->getLabel(),
            'breadcrumb' => [
                [
                    'name' => 'Liste des compétences',
                    'route' => $this->generateUrl('competence.list'),
                ],
                [
                    'name' => $competence->getLabel(),
                    'route' => $this->generateUrl('competence.detail', ['competence' => $competence->getId()]),
                ],
                [
                    'name' => 'Personnages ayant cette compétence',
                ],
            ],
        ];

        // handle the request and return an array containing the parameters for the view
        $viewParams = $personnageService->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages,
            $competenceRepository->getPersonnages($competence),
        );

        return $this->render(
            $twigFilePath,
            $viewParams,
        );
    }

    /**
     * Retire le document d'une competence.
     */
    #[IsGranted('ROLE_REGLE')]
    #[Route('/remove-document', name: 'document.remove')]
    public function removeDocumentAction(
        EntityManagerInterface $entityManager,
        Competence $competence,
    ): RedirectResponse {
        $competence->setDocumentUrl(null);

        $entityManager->persist($competence);
        $entityManager->flush();
        $this->addFlash('success', 'La compétence a été mise à jour.');

        return $this->redirectToRoute('competence.list', [], 303);
    }

    /**
     * Met à jour une compétence.
     */
    #[Route('/{competence}/update', name: 'update')]
    #[IsGranted('ROLE_REGLE')]
    public function updateAction(
        Request $request,
        #[MapEntity] Competence $competence,
    ): RedirectResponse|Response {
        return $this->handleCreateOrUpdate(
            $request,
            $competence,
            CompetenceForm::class,
        );
    }
}
