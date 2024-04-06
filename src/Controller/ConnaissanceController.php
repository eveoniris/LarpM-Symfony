<?php

namespace App\Controller;

use App\Entity\Connaissance;
use App\Form\ConnaissanceForm;
use App\Form\DeleteForm;
use App\Form\Entity\ListSearch;
use App\Form\ListFindForm;
use App\Repository\ConnaissanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
    // TODO : check this
    // liste des colonnes à afficher par défaut sur les vues 'personnages' (l'ordre est pris en compte)
    private array $defaultPersonnageListColumnKeys = ['colId', 'colStatut', 'colNom', 'colClasse', 'colGroupe', 'colUser'];

    #[Route(name: 'list')]
    public function listAction(Request $request): Response
    {
        $alias = ConnaissanceRepository::getEntityAlias();
        $type = null;
        $value = null;

        $listSearch = new ListSearch();
        $form = $this->createForm(type: ListFindForm::class, data: $listSearch);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data->getType();
            $value = $data->getValue();
        }

        /** @var ConnaissanceRepository $connaissanceRepository */
        $connaissanceRepository = $this->entityManager->getRepository(Connaissance::class);
        $orderBy = $this->getRequestOrder(
            defOrderBy: 'label',
            alias: $alias,
            allowedFields: $connaissanceRepository->getFieldNames()
        );

        $query = $connaissanceRepository->createQueryBuilder($alias)
            ->orderBy(key($orderBy), current($orderBy));

        if (!empty($value)) {
            if (empty($type) || '*' === $type) {
                $query->orWhere($alias.'.id LIKE :value');
                $query->orWhere($alias.'.label LIKE :value');
                $query->orWhere($alias.'.description LIKE :value');

                $query->setParameter('value', '%'.$value.'%');
            } elseif ('secret' === $type) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                $query->orWhere($alias.'.secret = :value');
                $query->setParameter('value', $value);
            } else {
                // TODO debug when using this criteria
                $query->where($query->expr()->like($alias.'.'.$type, "'%".$value."%'"));
            }
        }

        $paginator = $connaissanceRepository->findPaginatedQuery(
            $query->getQuery(), $this->getRequestLimit(), $this->getRequestPage()
        );

        return $this->render('connaissance/list.twig', [
            'searchValue' => $value,
            'form' => $form->createView(),
            'paginator' => $paginator,
            'orderDir' => $this->getRequestOrderDir(),
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

        $form = $this->createForm(ConnaissanceForm::class, $connaissance)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connaissance = $form->getData();
            $connaissance->setNiveau(1);

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('connaissance.list', [], 303);
                }

                $documentFilename = hash('md5', $connaissance->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $connaissance->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($connaissance);
            $entityManager->flush();

            $this->addFlash('success', 'La connaissance a été ajoutée');

            return $this->redirectToRoute('connaissance.detail', ['connaissance' => $connaissance->getId()], 303);
        }

        return $this->render('connaissance/add.twig', [
            'connaissance' => $connaissance,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une connaissance.
     */
    #[Route('/{connaissance}/update', name: 'update', requirements: ['connaissance' => Requirement::DIGITS])]
    public function updateAction(Request $request, #[MapEntity] Connaissance $connaissance): RedirectResponse|Response
    {
        $form = $this->createForm(ConnaissanceForm::class, $connaissance)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connaissance = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('connaissance.list', [], 303);
                }

                $documentFilename = hash('md5', $connaissance->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $connaissance->setDocumentUrl($documentFilename);
            }

            $this->entityManager->persist($connaissance);
            $this->entityManager->flush();

            $this->addFlash('success', 'La connaissance a été sauvegardée');

            return $this->redirectToRoute('connaissance.detail', ['connaissance' => $connaissance->getId()], 303);
        }

        return $this->render('connaissance/update.twig', [
            'connaissance' => $connaissance,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une connaissance.
     */
    #[Route('/{connaissance}/delete', name: 'delete', requirements: ['connaissance' => Requirement::DIGITS], methods: ['DELETE', 'GET', 'POST'])]
    public function deleteAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Connaissance $connaissance): RedirectResponse|Response
    {
        return $this->genericDelete(
            $connaissance,
            'Supprimer une connaissance',
            'La connaissance a été supprimée',
            'connaissance.list',
            [
                ['route' => $this->generateUrl('connaissance.list'), 'name' => 'Liste des connaissances'],
                ['route' => 'connaissance.detail', 'connaissance' => $connaissance->getId(), 'name' => $connaissance->getLabel()],
                ['name' => 'Supprimer une connaissance'],
            ]
        );
    }

    /**
     * Obtenir le document lié a une connaissance.
     */
    #[Route('/{connaissance}/document', name: 'document', requirements: ['connaissance' => Requirement::DIGITS])]
    public function getDocumentAction(Request $request, #[MapEntity] Connaissance $connaissance)
    {
        $document = $request->get('document');

        $file = __DIR__.'/../../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$connaissance->getPrintLabel().'.pdf"',
        ]);
    }

    #[Route('/{connaissance}/personnages', name: 'personnages', requirements: ['connaissance' => Requirement::DIGITS])]
    public function personnagesAction(Request $request, #[MapEntity] Connaissance $connaissance): Response
    {
        $routeName = 'connaissance.personnages';
        $routeParams = ['connaissance' => $connaissance->getId()];
        $twigFilePath = 'admin/connaissance/personnages.twig';
        $columnKeys = $this->defaultPersonnageListColumnKeys;
        $personnages = $connaissance->getPersonnages();
        $additionalViewParams = [
            'connaissance' => $connaissance,
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
