<?php


namespace App\Controller;

use App\Entity\Document;
use JasonGrimes\Paginator;
use App\Form\DocumentDeleteForm;
use App\Form\DocumentFindForm;
use App\Form\DocumentForm;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\DocumentController.
 *
 * @author kevin
 */
class DocumentController extends AbstractController
{
    /**
     * Liste des documents.
     */
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $order_by = $request->get('order_by', 'titre');
        $order_dir = 'DESC' === $request->get('order_dir', 'ASC') ? 'DESC' : 'ASC';
        $limit = (int) $request->get('limit', 50);
        $page = (int) $request->get('page', 1);
        $offset = (int) (($page - 1) * $limit);
        $criteria = [];
        $type = null;
        $value = null;

        $form = $this->createForm(new DocumentFindForm())->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
        }

        $repo = $entityManager->getRepository('\\'.\App\Entity\Document::class);
        $documents = $repo->findList(
            $type,
            $value,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset
        );

        $numResults = $repo->findCount($type, $value);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('document').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $this->render(
            'admin/document/index.twig',
            [
                'documents' => $documents,
                'paginator' => $paginator,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Imprimer la liste des documents.
     */
    public function printAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $documents = $entityManager->getRepository('\\'.\App\Entity\Document::class)->findAllOrderedByCode();

        return $this->render('admin/document/print.twig', ['documents' => $documents]);
    }

    /**
     * Télécharger la liste des documents.
     */
    public function downloadAction(Request $request,  EntityManagerInterface $entityManager): void
    {
        $documents = $entityManager->getRepository('\\'.\App\Entity\Document::class)->findAllOrderedByCode();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_documents_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

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
    public function getAction(Request $request,  EntityManagerInterface $entityManager, $document)
    {
        $filename = __DIR__.'/../../../private/documents/'.$document;

        return $app->sendFile($filename);
    }

    /**
     * Ajouter un document.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(DocumentForm::class(), new Document())
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $document = $form->getData();
            $document->setUser($this->getUser());

            $files = $request->files->get($form->getName());

            $path = __DIR__.'/../../../private/documents/';
            $filename = $files['document']->getClientOriginalName();
            $extension = $files['document']->guessExtension();

            if (!$extension || 'pdf' != $extension) {
               $this->addFlash('error', 'Désolé, votre fichier ne semble pas valide (vérifiez le format de votre fichier)');

                return $this->redirectToRoute('document.add', [], 303);
            }

            $documentFilename = hash('md5', $this->getUser()->getUsername().$filename.time()).'.'.$extension;

            $files['document']->move($path, $documentFilename);

            $document->setDocumentUrl($documentFilename);

            $entityManager->persist($document);
            $entityManager->flush();

           $this->addFlash('success', 'Le document a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('document', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('document.add', [], 303);
            }
        }

        return $this->render('admin/document/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'un document.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Document $document)
    {
        return $this->render('admin/document/detail.twig', ['document' => $document]);
    }

    /**
     * Mise à jour d'un document.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Document $document)
    {
        $form = $this->createForm(DocumentForm::class(), $document)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $document = $form->getData();
            $document->setUpdateDate(new \DateTime('NOW'));

            $files = $request->files->get($form->getName());
            if ($files['document']) {
                $path = __DIR__.'/../../../private/documents/';
                $filename = $files['document']->getClientOriginalName();
                $extension = $files['document']->guessExtension();

                if (!$extension || 'pdf' != $extension) {
                   $this->addFlash('error', 'Désolé, votre fichier ne semble pas valide (vérifiez le format de votre fichier)');

                    return $this->redirectToRoute('document.add', [], 303);
                }

                $documentFilename = hash('md5', $this->getUser()->getUsername().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $document->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($document);
            $entityManager->flush();

           $this->addFlash('success', 'Le document a été modifié.');

            return $this->redirectToRoute('document', [], 303);
        }

        return $this->render('admin/document/update.twig', [
            'document' => $document,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un document.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Document $document)
    {
        $form = $this->createForm(DocumentDeleteForm::class(), $document)
            ->add('save', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $document = $form->getData();

            $entityManager->remove($document);
            $entityManager->flush();

           $this->addFlash('success', 'Le document a été supprimé.');

            return $this->redirectToRoute('document', [], 303);
        }

        return $this->render('admin/document/delete.twig', [
            'document' => $document,
            'form' => $form->createView(),
        ]);
    }
}
