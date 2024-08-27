<?php

namespace App\Controller;

use App\Entity\AttributeType;
use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\Level;
use App\Enum\LevelType;
use App\Form\CompetenceFindForm;
use App\Form\CompetenceForm;
use App\Form\Entity\BaseSearch;
use App\Repository\CompetenceFamilyRepository;
use App\Repository\CompetenceRepository;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
     * Liste des compétences.
     */
    #[Route('', name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        CompetenceRepository $competenceRepository,
        CompetenceFamilyRepository $competenceFamilyRepository
    ): Response {
        $pagerService->setRequest($request)->setRepository($competenceRepository)->setLimit(50);

        $alias = $competenceRepository->getAlias();
        $queryBuilder = $competenceRepository->createQueryBuilder($alias);

        if (!$this->isGranted('ROLE_REGLE')) {
            $queryBuilder = $competenceRepository->level($queryBuilder, LevelType::APPRENTICE);
        }

        return $this->render('competence/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $competenceRepository->searchPaginated($pagerService, $queryBuilder),
        ]);

        $form = $this->createForm(CompetenceFindForm::class, new BaseSearch());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $value = $data->getValue();
        }

        $query = $competenceRepository->createQueryBuilder('c')
            ->join('c.competenceFamily', 'cf')
            ->join('c.level', 'l')
            ->addOrderBy('cf.label')
            ->addOrderBy('l.index');

        if (!$this->isGranted('ROLE_REGLE')) {
            $query->andWhere('c.level = 1');
        }

        if (!empty($value)) {
            $query->andWhere($query->expr()->like('cf.label', ':label'));
            $query->setParameter('label', '%'.$value.'%');
        }

        $paginator = $competenceRepository->findPaginatedQuery(
            $query->getQuery(),
            $limit = 100,
        );

        return $this->render(
            'competence/list.twig',
            [
                'paginator' => $paginator,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Liste des perso ayant cette compétence.
     */
    #[Route('/perso', name: 'perso')]
    public function persoAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $competence = $request->get('competence');

        return $this->render('competence/perso.twig', ['competence' => $competence]);
    }

    #[Route('/{competence}/personnages', name: 'personnages', requirements: ['competence' => Requirement::DIGITS])]
    public function personnagesAction(
        Request $request,
        #[MapEntity] Competence $competence,
        PersonnageService $personnageService,
        CompetenceRepository $competenceRepository
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
            $competenceRepository->getPersonnages($competence)
        );

        return $this->render(
            $twigFilePath,
            $viewParams
        );
    }

    /**
     * Liste du matériel necessaire par compétence.
     */
    #[Route('/competence/materiel', name: 'materiel')]
    public function materielAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository('\\'.Competence::class);
        $competences = $repo->findAllOrderedByLabel();

        return $this->render('competence/materiel.twig', ['competences' => $competences]);
    }

    /**
     * Ajout d'une compétence.
     */
    #[Route('/competence/add', name: 'add')]
    #[IsGranted('ROLE_REGLE')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $competence = new Competence();

        // l'identifiant de la famille de competence peux avoir été passé en paramètre
        // pour initialiser le formulaire avec une valeur par défaut.
        // TODO : dans ce cas, il ne faut proposer que les niveaux pour lesquels une compétence
        // n'a pas été défini pour cette famille

        $competenceFamilyId = $request->get('competenceFamily');
        $levelIndex = $request->get('level');

        if ($competenceFamilyId) {
            $competenceFamily = $entityManager->find('\\'.CompetenceFamily::class, $competenceFamilyId);
            if ($competenceFamily) {
                $competence->setCompetenceFamily($competenceFamily);
            }
        }

        if ($levelIndex) {
            $repo = $entityManager->getRepository('\\'.Level::class);
            $level = $repo->findOneByIndex($levelIndex + 1);
            if ($level) {
                $competence->setLevel($level);
            }
        }

        $form = $this->createForm(CompetenceForm::class, $competence)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $competence = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $this->addFlash(
                        'error',
                        'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)'
                    );

                    return $this->redirectToRoute('competenceFamily.index', [], 303);
                }

                $documentFilename = hash('md5', $competence->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $competence->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($competence);
            $entityManager->flush();

            $this->addFlash('success', 'La compétence a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('competence.detail', ['competence' => $competence->getId()]);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('competence.add', [], 303);
            }
        }

        return $this->render('competence/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'une compétence.
     */
    #[Route('/{competence}', name: 'detail')]
    public function detailAction(#[MapEntity] Competence $competence): Response
    {
        return $this->render('competence/detail.twig', ['competence' => $competence]);
    }

    /**
     * Met à jour une compétence.
     */
    #[Route('/competence/{competence}/update', name: 'update')]
    #[IsGranted('ROLE_REGLE')]
    public function updateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Competence $competence
    ): RedirectResponse|Response {
        $attributeRepos = $entityManager->getRepository(AttributeType::class);
        $form = $this->createForm(CompetenceForm::class, $competence)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $competence = $form->getData();

            $files = $request->files->get($form->getName());

            // si un document est fourni, l'enregistré TODO
            if (null != $files['document']) {
                $path = __DIR__.'/../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $this->addFlash(
                        'error',
                        'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)'
                    );

                    return $this->redirectToRoute('competenceFamily.index', [], 303);
                }

                $documentFilename = hash('md5', $competence->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $competence->setDocumentUrl($documentFilename);
            }

            if ($form->get('update')->isClicked()) {
                $competence->setCompetenceAttributesAsString(
                    $request->get('competenceAttributesAsString'),
                    $entityManager,
                    $attributeRepos
                );
                $entityManager->persist($competence);
                $entityManager->flush();
                $this->addFlash('success', 'La compétence a été mise à jour.');

                return $this->redirectToRoute('competence.detail', ['competence' => $competence->getId()]);
            }
            if ($form->get('delete')->isClicked()) {
                $entityManager->remove($competence);
                $entityManager->flush();
                $this->addFlash('success', 'La compétence a été supprimée.');

                return $this->redirectToRoute('competence');
            }
        }

        return $this->render('competence/update.twig', [
            'competence' => $competence,
            'form' => $form->createView(),
            'available_attributes' => $attributeRepos->findAllOrderedByLabel(),
        ]);
    }

    /**
     * Retire le document d'une competence.
     */
    #[IsGranted('ROLE_REGLE')]
    #[Route('/competence/remove-document', name: 'remove')]
    public function removeDocumentAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Competence $competence
    ): RedirectResponse {
        $competence->setDocumentUrl(null);

        $entityManager->persist($competence);
        $entityManager->flush();
        $this->addFlash('success', 'La compétence a été mise à jour.');

        return $this->redirectToRoute('competence.list', [], 303);
    }

    /**
     * Téléchargement du document lié à une compétence.
     */
    #[Route('/competence/{competence}/document', name: 'document')]
    public function getDocumentAction(Request $request, EntityManagerInterface $entityManager)
    {
        $document = $request->get('document');
        $competence = $request->get('competence');

        // on ne peux télécharger que les documents des compétences que l'on connait
        if (!$app['security.authorization_checker']->isGranted('ROLE_REGLE') && $this->getUser()->getPersonnage()) {
            if (!$this->getUser()->getPersonnage()->getCompetences()->contains($competence)) {
                $this->addFlash('error', "Vous n'avez pas les droits necessaires");
            }
        }

        $file = __DIR__.'/../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$competence->getLabel().'.pdf"',
        ]);
    }
}
