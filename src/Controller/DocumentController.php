<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Technologie;
use App\Enum\DocumentType;
use App\Enum\FolderType;
use App\Form\DocumentDeleteForm;
use App\Form\DocumentForm;
use App\Form\Technologie\TechnologieForm;
use App\Repository\DocumentRepository;
use App\Service\PagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SCENARISTE')]
#[Route('/document', name: 'document.')]
class DocumentController extends AbstractController
{
    /**
     * Liste des documents.
     */
    #[Route('', name: 'index')]
    #[Route('', name: 'list')]
    public function indexAction(
        Request $request,
        PagerService $pagerService,
        DocumentRepository $documentRepository,
    ): Response {
        $pagerService->setRequest($request)->setRepository($documentRepository);

        return $this->render('document/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $documentRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Imprimer la liste des documents.
     */
    #[Route('/print', name: 'print')]
    public function printAction(DocumentRepository $documentRepository): Response
    {
        // TODO global
        $documents = $documentRepository->findAllOrderedByCode();

        return $this->render('document/print.twig', ['documents' => $documents]);
    }

    /**
     * Télécharger la liste des documents.
     */
    #[Route('/download', name: 'download')]
    public function downloadAction(DocumentRepository $documentRepositoryr): void
    {
        // TODO use abstract download mode
        $documents = $documentRepositoryr->findAllOrderedByCode();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_documents_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'wb');

        // header
        fputcsv($output,
            [
                'code',
                'titre',
                'impression',
                'description',
                'langues',
                'lieux',
                'groupes',
                'personnages',
                'createur',
                'date de création',
                'date de mise à jour'], ';');

        foreach ($documents as $document) {
            $line = [];
            $line[] = mb_convert_encoding((string) $document->getCode(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $document->getTitre(), 'ISO-8859-1');
            $line[] = $document->getImpression() ? mb_convert_encoding('Imprimé', 'ISO-8859-1') : mb_convert_encoding('Non imprimé', 'ISO-8859-1');

            $line[] = mb_convert_encoding((string) $document->getDescriptionRaw(), 'ISO-8859-1');

            $langues = '';
            foreach ($document->getLangues() as $langue) {
                $langues .= mb_convert_encoding((string) $langue->getLabel(), 'ISO-8859-1').', ';
            }

            $line[] = $langues;

            $lieux = '';
            foreach ($document->getLieus() as $lieu) {
                $lieux .= mb_convert_encoding((string) $lieu->getNom(), 'ISO-8859-1').', ';
            }

            $line[] = $lieux;

            $groupes = '';
            foreach ($document->getGroupes() as $groupe) {
                $groupes .= mb_convert_encoding((string) $groupe->getNom(), 'ISO-8859-1').', ';
            }

            $line[] = $groupes;

            $personnages = '';
            foreach ($document->getPersonnages() as $personnage) {
                $personnages .= mb_convert_encoding((string) $personnage->getNom(), 'ISO-8859-1').', ';
            }

            $line[] = $personnages;

            $line[] = $document->getUser();
            $line[] = $document->getCreationDate()->format('Y-m-d H:i:s');
            $line[] = $document->getUpdateDate()->format('Y-m-d H:i:s');

            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Téléchargement du fichier lié au document.
     *
     * @param unknown $document
     */
    #[Route('/get/{document}', name: 'get', requirements: ['document' => Requirement::DIGITS])]
    public function getAction(#[MapEntity] Document $document): BinaryFileResponse
    {
        // TODO
        $filename = __DIR__.'/../../private/documents/'.$document->getDocumentUrl();

        // return $app->sendFile($filename);
        return new BinaryFileResponse($filename);
    }

    /**
     * Ajouter un document.
     */
    #[Route('/add', name: 'add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            new Document(),
            DocumentForm::class
        );
    }

    /**
     * Détail d'un document.
     */
    #[Route('/{document}', name: 'detail', requirements: ['document' => Requirement::DIGITS])]
    public function detailAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Document $document): Response
    {
        return $this->render('document/detail.twig', ['document' => $document]);
    }

    /**
     * Mise à jour d'un document.
     */
    #[Route('/{document}/update', name: 'update')]
    public function updateAction(Request $request, #[MapEntity] Document $document): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate(
            $request,
            $document,
            DocumentForm::class
        );
    }

    /**
     * Suppression d'un document.
     */
    #[Route('/{document}/delete', name: 'delete', requirements: ['document' => Requirement::DIGITS])]
    public function deleteAction(
        #[MapEntity] Document $document
    ): RedirectResponse|Response {
        return $this->genericDelete(
            $document,
            'Supprimer un document',
            'Le document a été supprimé',
            'document.list',
            [
                ['route' => $this->generateUrl('document.list'), 'name' => 'Liste des documents'],
                [
                    'route' => $this->generateUrl('document.detail', ['document' => $document->getId()]),
                    'document' => $document->getId(),
                    'name' => $document->getLabel(),
                ],
                ['name' => 'Supprimer un document'],
            ]
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

            // TODO debug why we do not store the docUrl
            $entityCallback = function (Document $document, FormInterface $form): ?Document {
                $document->setUser($this->getUser());
                $document->handleUpload(
                    $this->fileUploader,
                    DocumentType::Documents,
                    FolderType::Private
                );

                return $document;
            };
        }

        return parent::handleCreateOrUpdate(
            request: $request,
            entity: $entity,
            formClass: $formClass,
            breadcrumb: $breadcrumb,
            routes: $routes,
            msg: [
                'entity' => $this->translator->trans('document'),
                'entity_added' => $this->translator->trans('Le document a été ajouté'),
                'entity_updated' => $this->translator->trans('Le document a été mis à jour'),
                'entity_deleted' => $this->translator->trans('Le document a été supprimé'),
                'entity_list' => $this->translator->trans('Liste des documents'),
                'title_add' => $this->translator->trans('Ajouter un document'),
                'title_update' => $this->translator->trans('Modifier un documents'),
                ...$msg,
            ],
            entityCallback: $entityCallback
        );
    }
}
