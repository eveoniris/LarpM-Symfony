<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Gn;
use App\Entity\GroupeLangue;
use App\Entity\Langue;
use App\Entity\Personnage;
use App\Enum\Role;
use App\Form\Groupe\GroupeLangueType;
use App\Form\LangueType;
use App\Security\MultiRolesExpression;
use App\Service\LangueJoueursService;
use App\Service\PersonnageService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role::REGLE, Role::COHERENCE))]
class LangueController extends AbstractController
{
    final public const DOC_PATH = __DIR__ . '/../../private/doc/';

    /**
     * affiche la liste des langues.
     */
    #[Route('/langue', name: 'langue.index')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager, LangueJoueursService $langueJoueursService): Response
    {
        $langues = $entityManager->getRepository(Langue::class)->findAllOrderedByLabel();
        $groupeLangues = $entityManager->getRepository(GroupeLangue::class)->findAllOrderedByLabel();
        $gnRepository = $entityManager->getRepository(Gn::class);
        $gn = $gnRepository->findFromFilter($request->query->get('gn'));
        $allGns = $gnRepository->findBy([], ['date_debut' => 'DESC']);

        return $this->render('langue/index.twig', [
            'langues' => $langues,
            'groupeLangues' => $groupeLangues,
            'gn' => $gn,
            'allGns' => $allGns,
            'effectifs' => $gn ? array_column($langueJoueursService->getRepartitionGn($gn), 'total', 'id') : [],
        ]);
    }

    /**
     * Detail d'une langue.
     */
    #[Route('/langue/{langue}/detail', name: 'langue.detail')]
    public function detailAction(Request $request, Langue $langue, EntityManagerInterface $entityManager, LangueJoueursService $langueJoueursService): Response
    {
        $gnRepository = $entityManager->getRepository(Gn::class);
        $gn = $gnRepository->findFromFilter($request->query->get('gn'));
        $allGns = $gnRepository->findBy([], ['date_debut' => 'DESC']);
        $effectifs = $gn ? array_column($langueJoueursService->getRepartitionGn($gn), 'total', 'id') : [];

        return $this->render('langue/detail.twig', [
            'langue' => $langue,
            'gn' => $gn,
            'allGns' => $allGns,
            'effectif' => $gn ? $effectifs[$langue->getId()] ?? 0 : null,
        ]);
    }

    /**
     * Liste des personnages parlant la langue (langues de bonus comprises), éventuellement pour un GN.
     */
    #[Route('/langue/{langue}/personnages', name: 'langue.personnages', requirements: ['langue' => Requirement::DIGITS])]
    #[Route('/langue/{langue}/personnages/gn/{gn}', name: 'langue.personnages.gn', requirements: [
        'langue' => Requirement::DIGITS,
        'gn' => Requirement::DIGITS,
    ])]
    public function personnagesAction(
        Request $request,
        PersonnageService $personnageService,
        LangueJoueursService $langueJoueursService,
        #[MapEntity]
        Langue $langue,
        #[MapEntity]
        ?Gn $gn = null,
    ): Response {
        $breadcrumb = [
            ['name' => 'Gestion des langues', 'route' => $this->generateUrl('langue.index')],
            ['name' => $langue->getLabel(), 'route' => $this->generateUrl('langue.detail', ['langue' => $langue->getId()])],
            ['name' => 'Personnages parlant cette langue' . ($gn ? ' (' . $gn->getLabel() . ')' : '')],
        ];

        $exportParams = ['langue' => $langue->getId()] + ($gn ? ['gn' => $gn->getId()] : []);

        $viewParams = $personnageService->getSearchViewParameters(
            $request,
            $gn ? 'langue.personnages.gn' : 'langue.personnages',
            $exportParams,
            ['colId', 'colStatut', 'colNom', 'colGroupe'],
            [
                'langue' => $langue,
                'title' => 'Langue ' . $langue->getLabel(),
                'breadcrumb' => $breadcrumb,
                'exportRoute' => $gn ? 'langue.personnages.gn.export' : 'langue.personnages.export',
                'exportParams' => $exportParams,
            ],
            new ArrayCollection(),
            $langueJoueursService->getQueryBuilder($langue, $gn),
        );

        return $this->render('personnage/sub_personnages.twig', $viewParams);
    }

    /**
     * Export CSV (par défaut) ou JSON (?format=json) des personnages parlant la langue.
     */
    #[Route('/langue/{langue}/personnages/export', name: 'langue.personnages.export', requirements: ['langue' => Requirement::DIGITS])]
    #[Route('/langue/{langue}/personnages/gn/{gn}/export', name: 'langue.personnages.gn.export', requirements: [
        'langue' => Requirement::DIGITS,
        'gn' => Requirement::DIGITS,
    ])]
    public function personnagesExportAction(
        Request $request,
        LangueJoueursService $langueJoueursService,
        #[MapEntity]
        Langue $langue,
        #[MapEntity]
        ?Gn $gn = null,
    ): Response {
        $sources = $langueJoueursService->getSourcesParPersonnage($langue, $gn);
        $rows = [];
        /** @var Personnage $personnage */
        foreach ($langueJoueursService->getQueryBuilder($langue, $gn)->getQuery()->toIterable() as $personnage) {
            $rows[] = [
                'id' => $personnage->getId(),
                'personnage' => $personnage->getFullName(),
                'groupe' => $personnage->getLastParticipantGnGroupe()?->getNom() ?? '',
                'joueur' => $personnage->getUser()?->getFullName() ?? '',
                'vivant' => $personnage->getVivant() ? 'oui' : 'non',
                'source' => $sources[$personnage->getId()] ?? '',
            ];
        }

        if ('json' === $request->query->get('format')) {
            return new JsonResponse($rows);
        }

        return $this->sendCsv(
            title: 'eveoniris_langue_' . $langue->getId() . ($gn ? '_gn_' . $gn->getId() : '') . '_personnages_' . date('Ymd'),
            header: ['id', 'personnage', 'groupe', 'joueur', 'vivant', 'source'],
            dataProvider: $rows,
        );
    }

    /**
     * Ajoute une langue.
     */
    #[Route('/langue/add', name: 'langue.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): Response|RedirectResponse
    {
        $langue = new Langue();

        $form = $this->createForm(LangueType::class, $langue)->add('save', SubmitType::class, [
            'label' => 'Sauvegarder',
        ])->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        // si l'utilisateur soumet une nouvelle langue
        if ($form->isSubmitted() && $form->isValid()) {
            $langue = $form->getData();
            if (self::handleDocument($request, $entityManager, $form, $langue)) {
                $entityManager->persist($langue);
                $entityManager->flush();

                $this->addFlash('success', 'La langue a été ajoutée.');

                // l'utilisateur est redirigé soit vers la liste des langues, soit vers de nouveau
                // vers le formulaire d'ajout d'une langue
                if ($form->get('save') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('save')->isClicked()) {
                    return $this->redirectToRoute('langue.index', [], 303);
                } elseif ($form->get('save_continue') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('save_continue')->isClicked()) {
                    return $this->redirectToRoute('langue.add', [], 303);
                }
            }
        }

        return $this->render('langue/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie une langue. Si l'utilisateur clique sur "sauvegarder", la langue est sauvegardée et
     * l'utilisateur est redirigé vers la liste des langues.
     * Si l'utilisateur clique sur "supprimer", la langue est supprimée et l'utilisateur est
     * redirigé vers la liste des langues.
     */
    #[Route('/langue/{langue}/update', name: 'langue.update')]
    public function updateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Langue $langue,
    ): Response|RedirectResponse {
        $hasDocumentUrl = !empty($langue->getDocumentUrl());
        $canBeDeleted = $langue->getPersonnageLangues()->isEmpty() && $langue->getTerritoires()->isEmpty() && $langue->getDocuments()->isEmpty();

        $deleteTooltip = $canBeDeleted
            ? ''
            : 'Cette langue est référencée par '
            . $langue->getPersonnageLangues()->count()
            . ' personnages, '
            . $langue->getTerritoires()->count()
            . ' territoires et '
            . $langue->getDocuments()->count()
            . ' documents et ne peut pas être supprimée';

        $formBuilder = $this->createForm(LangueType::class, $langue, [
            'hasDocumentUrl' => $hasDocumentUrl,
        ])->add('update', SubmitType::class, ['label' => 'Sauvegarder'])->add('delete', SubmitType::class, [
            'label' => 'Supprimer',
            'disabled' => !$canBeDeleted,
            'attr' => ['title' => $deleteTooltip],
        ]);

        $form = $formBuilder;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $langue = $form->getData();

            if ($form->get('update') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('update')->isClicked()) {
                if (self::handleDocument($request, $entityManager, $form, $langue)) {
                    $entityManager->persist($langue);
                    $entityManager->flush();
                    $this->addFlash('success', 'La langue a été mise à jour.');

                    return $this->redirectToRoute('langue.detail', ['langue' => $langue->getId()], 303);
                }
            } elseif ($form->get('delete') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('delete')->isClicked()) {
                $entityManager->remove($langue);
                $entityManager->flush();
                // delete language document if it exists
                self::tryDeleteDocument($langue);
                $this->addFlash('success', 'La langue a été supprimée.');

                return $this->redirectToRoute('langue.index', [], 303);
            }
        }

        return $this->render('langue/update.twig', [
            'langue' => $langue,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gère le document uploadé et renvoie true si il est valide, false sinon.
     */
    private function handleDocument(
        Request $request,
        EntityManagerInterface $entityManager,
        FormInterface $form,
        Langue $langue,
    ): bool {
        $files = $request->files->get($form->getName());
        $documentFile = $files['document'];
        // Si un document est fourni, l'enregistrer
        if (null != $documentFile) {
            $filename = $documentFile->getClientOriginalName();
            $extension = pathinfo((string) $filename, \PATHINFO_EXTENSION);

            if (!$extension || 'pdf' != $extension) {
                $this->addFlash('error', 'Désolé, votre document n\'est pas valide. Vérifiez le format de votre document (' . $extension . '), seuls les .pdf sont acceptés.');

                return false;
            }

            $documentFilename = hash('md5', $langue->getLabel() . $filename . time()) . '.' . $extension;

            $documentFile->move(self::DOC_PATH, $documentFilename);

            // delete previous language document if it exists
            self::tryDeleteDocument($langue);

            $langue->setDocumentUrl($documentFilename);
        }

        return true;
    }

    /**
     * Supprime le document spécifié, en cas d'erreur, ne fait rien pour le moment.
     *
     * @param Langue $langue
     */
    private function tryDeleteDocument($langue): void
    {
        try {
            if (!empty($langue->getDocumentUrl())) {
                $docFilePath = self::DOC_PATH . $langue->getDocumentUrl();
                unlink($docFilePath);
            }
        } catch (FileException) {
            // for now, simply ignore
        }
    }

    /**
     * Detail d'un groupe de langue.
     */
    #[Route('/langue/groupe/{groupeLangue}/detail', name: 'langue.detailGroup')]
    public function detailGroupAction(
        Request $request,
        EntityManagerInterface $entityManager,
        GroupeLangue $groupeLangue,
    ): Response {
        return $this->render('langue/detailGroup.twig', ['groupeLangue' => $groupeLangue]);
    }

    /**
     * Ajoute un groupe de langue.
     */
    #[Route('/langue/groupe/add', name: 'langue.addGroup')]
    public function addGroupAction(Request $request, EntityManagerInterface $entityManager): Response|RedirectResponse
    {
        $groupeLangue = new GroupeLangue();

        $form = $this->createForm(GroupeLangueType::class, $groupeLangue)->add('save', SubmitType::class, [
            'label' => 'Sauvegarder',
        ])->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        // si l'utilisateur soumet une nouvelle langue
        if ($form->isSubmitted() && $form->isValid()) {
            $groupeLangue = $form->getData();
            $entityManager->persist($groupeLangue);
            $entityManager->flush();

            $this->addFlash('success', 'Le groupe de langue a été ajouté.');

            // l'utilisateur est redirigé soit vers la liste des langues, soit vers de nouveau
            // vers le formulaire d'ajout d'une langue
            if ($form->get('save') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('save')->isClicked()) {
                return $this->redirectToRoute('langue.index', [], 303);
            } elseif ($form->get('save_continue') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('langue.addGroup', [], 303);
            }
        }

        return $this->render('langue/addGroup.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie un groupe de langue. Si l'utilisateur clique sur "sauvegarder", le groupe de langue est sauvegardé.
     * Si l'utilisateur clique sur "supprimer", le groupe de langue est supprimé et l'utilisateur est
     * redirigé vers la liste des langues.
     */
    #[Route('/langue/groupe/{groupeLangue}/update', name: 'langue.updateGroup')]
    public function updateGroupAction(
        Request $request,
        EntityManagerInterface $entityManager,
        GroupeLangue $groupeLangue,
    ): Response|RedirectResponse {
        $canBeDeleted = $groupeLangue->getLangues()->isEmpty();
        $deleteTooltip = $canBeDeleted
            ? ''
            : 'Ce groupe est référencé par ' . $groupeLangue->getLangues()->count() . ' langues et ne peut pas être supprimé';

        $formBuilder = $this->createForm(GroupeLangueType::class, $groupeLangue)->add('update', SubmitType::class, [
            'label' => 'Sauvegarder',
        ])->add('delete', SubmitType::class, [
            'label' => 'Supprimer',
            'disabled' => !$canBeDeleted,
            'attr' => ['title' => $deleteTooltip],
        ]);

        $form = $formBuilder;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeLangue = $form->getData();

            if ($form->get('update') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('update')->isClicked()) {
                $entityManager->persist($groupeLangue);
                $entityManager->flush();
                $this->addFlash('success', 'Le groupe de langue a été mis à jour.');

                return $this->redirectToRoute('langue.detailGroup', ['groupeLangue' => $groupeLangue->getId()], 303);
            } elseif ($form->get('delete') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('delete')->isClicked()) {
                $entityManager->remove($groupeLangue);
                $entityManager->flush();
                $this->addFlash('success', 'Le groupe de langue a été supprimé.');

                return $this->redirectToRoute('langue.index', [], 303);
            }
        }

        return $this->render('langue/updateGroup.twig', [
            'groupeLangue' => $groupeLangue,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une langue.
     */
    #[Route('/langue/{langue}/document', name: 'langue.document')]
    public function adminDocumentAction(Request $request, Langue $langue): BinaryFileResponse
    {
        $document = $langue->getDocumentUrl();
        $file = self::DOC_PATH . $document;

        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Control', 'private');
        $response->headers->set('Content-Type', 'text/pdf');
        $response->headers->set('Content-length', (string) filesize($file));
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $langue->getPrintLabel() . '.pdf"');

        return $response;
    }
}
