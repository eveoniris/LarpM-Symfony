<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Objet;
use App\Form\Entity\ObjetSearch;
use App\Form\ObjetFindForm;
use App\Form\Stock\ObjetDeleteForm;
use App\Form\Stock\ObjetForm;
use App\Form\Stock\ObjetTagForm;
use App\Repository\ObjetRepository;
use App\Service\ImageOptimizer;
use Doctrine\ORM\EntityManagerInterface;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface as FormInterfaceAlias;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STOCK')]
class StockObjetController extends AbstractController
{
    /**
     * Ajoute un objet.
     */
    #[Route('/stock/objet/add', name: 'stockObjet.add')]
    public function addAction(
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse|Response {
        $objet = new Objet();

        $objet->setNombre(1);

        $repo = $entityManager->getRepository(Etat::class);
        $etat = $repo->find(1);
        if ($etat) {
            $objet->setEtat($etat);
        }

        $form = $this->createForm(ObjetForm::class, $objet)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder et fermer'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder et créer un nouveau'])
            ->add('save_clone', SubmitType::class, ['label' => 'Sauvegarder et cloner']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Objet $objet */
            $objet = $this->handleObjetPost($form, $entityManager);

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('stockObjet.index', [], 303);
            }
            if ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('stockObjet.add', [], 303);
            }
            if ($form->get('save_clone')->isClicked()) {
                return $this->redirectToRoute('stockObjet.clone', ['objet' => $objet->getId()], 303);
            }
        }

        return $this->render('stock/objet/add.twig', ['form' => $form->createView()]);
    }

    public function handleObjetPost(
        FormInterfaceAlias $form,
        EntityManagerInterface $entityManager,
        ?string $successMsg = null,
    ): mixed {
        $objet = $form->getData();

        if ($objet->getObjetCarac()) {
            $entityManager->persist($objet->getObjetCarac());
        }

        if ($objet->getPhoto()) {
            $objet->getPhoto()->handleUpload(
                $this->fileUploader,
                $objet->getPhotosDocumentType(),
                $objet->getPhotosFolderType(),
            );
            $entityManager->persist($objet->getPhoto());
        }

        $entityManager->persist($objet);
        $entityManager->flush();

        $this->addFlash('success', $successMsg ?? 'L\'objet a été ajouté dans le stock');

        return $objet;
    }

    /**
     * Créé un objet à partir d'un autre.
     */
    #[Route('/stock/objet/{objet}/clone', name: 'stockObjet.clone')]
    public function cloneAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Objet $objet,
    ): RedirectResponse|Response {
        $newObjet = clone $objet;

        $numero = $objet->getNumero();
        if ('' !== $numero && '0' !== $numero && is_numeric($numero)) {
            $newObjet->setNumero(++$numero);
        } else {
            $newObjet->setNumero($numero.'_2');
        }

        $form = $this->createForm(ObjetForm::class, $newObjet)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder et fermer'])
            ->add('save_clone', SubmitType::class, ['label' => 'Sauvegarder et cloner']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newObjet = $this->handleObjetPost($form, $entityManager);

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('stockObjet.index', [], 303);
            }

            return $this->redirectToRoute('stockObjet.clone', ['objet' => $newObjet->getId()], 303);
        }

        return $this->render('stock/objet/clone.twig', ['objet' => $newObjet, 'form' => $form->createView()]);
    }

    /**
     * Suppression d'un objet.
     */
    #[Route('/stock/objet/{objet}/delete', name: 'stockObjet.delete')]
    public function deleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Objet $objet,
    ): RedirectResponse|Response {
        $form = $this->createForm(ObjetDeleteForm::class, $objet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $objet = $form->getData();

            $entityManager->remove($objet);
            $entityManager->flush();

            $this->addFlash('success', 'L\'objet a été supprimé');

            return $this->redirectToRoute('stockObjet.index');
        }

        return $this->render('stock/objet/delete.twig', ['objet' => $objet, 'form' => $form->createView()]);
    }

    /**
     * Affiche la détail d'un objet.
     */
    #[Route('/stock/objet/{objet}/detail', name: 'stockObjet.detail')]
    public function detailAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Objet $objet,
    ): Response {
        return $this->render('stock/objet/detail.twig', ['objet' => $objet]);
    }

    /**
     * Exporte la liste des objets au format CSV.
     */
    #[Route('/stock/objet/export', name: 'stockObjet.export')]
    public function exportAction(ObjetRepository $objetRepository): Response
    {
        return $this->sendCsv(
            'eveoniris_stock_'.date('Ymd'),
            repository: $objetRepository,
        );
    }

    /**
     * Affiche la liste des objets.
     */
    #[Route('/stock/objet', name: 'stockObjet.index')]
    public function indexAction(
        Request $request,
        ObjetRepository $objetRepository,
    ): Response {
        $type = null;
        $value = null;
        $tag = null;
        $rangement = null;

        $objetSearch = new ObjetSearch();
        $form = $this->createForm(
            type: ObjetFindForm::class,
            data: $objetSearch,
        );

        // TODO move photo from database to drive

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data->getType();
            $value = $data->getValue();

            // tag: null => no search based on tag;
            // tag: <= 0 or ObjetRepository::CRIT_WITHOUT => search object without
            // tag: [a-Z]+ => search object with this tag name
            $tag = $data->getTag();

            // rangement: null => no search based on rangement;
            // rangement: <= 0> or ObjetRepository::CRIT_WITHOUT => search object without
            // rangement: [a-Z]+ => search object with this rangement name
            $rangement = $data->getRangement();
        }

        $alias = ObjetRepository::getEntityAlias();

        $orderBy = $this->getRequestOrder(
            defOrderBy: 'nom',
            alias: $alias,
            allowedFields: $objetRepository->getFieldNames(),
        );

        $query = $objetRepository->createQueryBuilder($alias)
            ->orderBy(key($orderBy), current($orderBy));

        $objetRepository->addTagCriteriaToQueryBuilder($tag, $query);
        $objetRepository->addRangementCriteriaToQueryBuilder($rangement, $query);

        $query->leftJoin($alias.'.items', 'i');
        $query->leftJoin($alias.'.photo', 'p');

        if (!empty($value)) {
            if (empty($type) || '*' === $type) {
                $query->orWhere($alias.'.id LIKE :value');
                $query->orWhere($alias.'.numero LIKE :value');
                $query->orWhere('i.numero LIKE :value');
                $query->orWhere($alias.'.nom LIKE :value');
                $query->orWhere($alias.'.description LIKE :value');
                $query->orWhere('i.label LIKE :value');
                $query->orWhere($alias.'.nom LIKE :value');
                $query->orWhere($alias.'.description LIKE :value');
                $query->orWhere('i.label LIKE :value');

                $query->setParameter('value', '%'.$value.'%');
            } else {
                $query->orWhere($query->expr()->like($alias.'.'.$type, "'%".$value."%'"));
            }
        }

        $paginator = $objetRepository->findPaginatedQuery(
            $query->getQuery(),
            $this->getRequestLimit(25),
            $this->getRequestPage(),
        );

        return $this->render('stock/objet/list.twig', [
            'searchValue' => $value,
            'form' => $form->createView(),
            'paginator' => $paginator,
            'orderDir' => $this->getRequestOrderDir(),
        ]);
    }

    /**
     * Fourni la liste des objets sans proprietaire.
     */
    #[Route('/stock/objet/list-without-proprio', name: 'stockObjet.list-without-proprio')]
    public function listWithoutProprioAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository(Objet::class);

        $qb = $repo->createQueryBuilder('o');
        $qb->select('o');
        $qb->where('o.proprietaire IS NULL');

        $objet_without_proprio = $qb->getQuery()->getResult();

        return $this->render('stock/objet/listWithoutProprio.twig', [
            'objets' => $objet_without_proprio,
        ]);
    }

    /**
     * Fourni la liste des objets sans rangement.
     */
    #[Route('/stock/objet/list-without-rangement', name: 'stockObjet.list-without-rangement')]
    public function listWithoutRangementAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository(Objet::class);

        $qb = $repo->createQueryBuilder('o');
        $qb->select('o');
        $qb->where('o.rangement IS NULL');

        $objet_without_rangement = $qb->getQuery()->getResult();

        return $this->render('stock/objet/listWithoutRangement.twig', [
            'objets' => $objet_without_rangement,
        ]);
    }

    /**
     * Fourni la liste des objets sans responsable.
     */
    #[Route('/stock/objet/list-without-responsable', name: 'stockObjet.list-without-responsable')]
    public function listWithoutResponsableAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository(Objet::class);

        $qb = $repo->createQueryBuilder('o');
        $qb->select('o');
        $qb->where('o.user IS NULL');

        $objet_without_responsable = $qb->getQuery()->getResult();

        return $this->render('stock/objet/listWithoutResponsable.twig', [
            'objets' => $objet_without_responsable,
        ]);
    }

    /**
     * Fourni les données de la photo liée à l'objet.
     */
    #[Route('/stock/objet/{objet}/photo', name: 'stockObjet.photo')]
    public function photoAction(
        Request $request,
        #[MapEntity] Objet $objet,
        ImageOptimizer $imageOptimizer,
    ): BinaryFileResponse|StreamedResponse {
        // PROD path https://larpmanager.eveoniris.com/stock/objet/1200/photo?miniature=1
        // PROD lARPV2 https://larpm.eveoniris.com/stock/objet/90/photo

        $miniature = $request->get('miniature');
        $photo = $objet->getPhoto();

        if (!$photo) {
            return $this->sendNoImageAvailable();
        }

        $file = $photo->getFilename();
        $path = $objet->getPhotoFilePath($imageOptimizer->getProjectDirectory());
        $filename = $path.$file;

        if (!file_exists($filename)) {
            if (null === $photo->getData()) {
                return $this->sendNoImageAvailable();
            }

            $photo->blobToFile($path);
            // If it's failed :
            if (!file_exists($filename)) {
                return $this->sendNoImageAvailable();
            }
        }

        if ($miniature) {
            try {
                $image = (new Imagine())->open($filename);
            } catch (\RuntimeException $e) {
                return $this->sendNoImageAvailable();
            }

            $response = new StreamedResponse();
            $response->headers->set('Content-Control', 'private');
            $response->headers->set('Content-Type', 'image/jpeg');
            $response->setCallback(static function () use ($image): void {
                echo $image->thumbnail(new Box(200, 200))->get('jpeg');
                flush();
            });
        } else {
            $response = new BinaryFileResponse($filename);
            $response->headers->set('Content-Control', 'private');
            $response->headers->set('Content-Type', 'image/'.$photo->getExtension());
        }

        return $response;
    }

    /**
     * Modification des tags d'un objet.
     */
    #[Route('/stock/objet/{objet}/tag', name: 'stockObjet.tag')]
    public function tagAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Objet $objet,
    ): RedirectResponse|Response {
        $form = $this->createForm(ObjetTagForm::class, $objet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $objet = $form->getData();

            $newTags = $form['news']->getData();
            foreach ($newTags as $tag) {
                $objet->addTag($tag);
                $entityManager->persist($tag);
            }

            $entityManager->persist($objet);
            $entityManager->flush();

            $this->addFlash('success', 'les tags ont été mis à jour');

            return $this->redirectToRoute('stockObjet.index');
        }

        return $this->render('stock/objet/tag.twig', ['objet' => $objet, 'form' => $form->createView()]);
    }

    /**
     * Mise à jour un objet.
     */
    #[Route('/stock/objet/{objet}/update', name: 'stockObjet.update')]
    public function updateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Objet $objet,
    ): RedirectResponse|Response {
        $form = $this->createForm(ObjetForm::class, $objet)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder et fermer'])
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $objet = $form->getData();

            if ($form->get('update')->isClicked()) {
                $this->handleObjetPost($form, $entityManager, 'L\'objet a été mis à jour');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($objet);
                $entityManager->flush();
                $this->addFlash('success', 'L\'objet a été supprimé');
            }

            return $this->redirectToRoute('stockObjet.index');
        }

        return $this->render('stock/objet/update.twig', ['objet' => $objet, 'form' => $form->createView()]);
    }
}
