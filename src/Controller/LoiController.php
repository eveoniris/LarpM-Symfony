<?php


namespace App\Controller;

use App\Entity\Loi;
use App\Form\Loi\LoiDeleteForm;
use App\Form\Loi\LoiForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[isGranted('ROLE_SCENARISTE')]
class LoiController extends AbstractController
{
    /**
     * Liste des loi.
     */
    #[Route('/loi', name: 'loi.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager): Response
    {
        $lois = $entityManager->getRepository(\App\Entity\Loi::class)->findAll();

        return $this->render('admin\loi\index.twig', [
            'lois' => $lois,
        ]);
    }

    /**
     * Ajout d'une loi.
     */
    #[Route('/loi/add', name: 'loi.add')]
    public function addAction(Request $request,  EntityManagerInterface $entityManager): Response|RedirectResponse
    {
        $form = $this->createForm(LoiForm::class, new Loi());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $loi = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../private/documents/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('loi.index', [], 303);
                }

                $documentFilename = hash('md5', $loi->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $loi->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($loi);
            $entityManager->flush();

           $this->addFlash('success', 'La loi a été ajoutée.');

            return $this->redirectToRoute('loi.index', [], 303);
        }

        return $this->render('admin\loi\add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une loi.
     */
    #[Route('/loi/{loi}', name: 'loi.detail')]
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, #[MapEntity] Loi $loi): Response
    {
        return $this->render('admin\loi\detail.twig', [
            'loi' => $loi,
        ]);
    }

    /**
     * Mise à jour d'une loi.
     */
    #[Route('/loi/{loi}/update', name: 'loi.update')]
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, #[MapEntity] Loi $loi): Response|RedirectResponse
    {
        $form = $this->createForm(LoiForm::class, $loi);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $loi = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../private/documents/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('loi.index', [], 303);
                }

                $documentFilename = hash('md5', $loi->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $loi->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($loi);
            $entityManager->flush();

           $this->addFlash('success', 'La loi a été mise à jour.');

            return $this->redirectToRoute('loi.index', [], 303);
        }

        return $this->render('admin\loi\update.twig', [
            'form' => $form->createView(),
            'loi' => $loi,
        ]);
    }

    /**
     * Suppression d'une loi.
     */
    #[Route('/loi/{loi}/delete', name: 'loi.delete')]
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, #[MapEntity] Loi $loi): Response|RedirectResponse
    {
        $form = $this->createForm(LoiDeleteForm::class, $loi)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $loi = $form->getData();

            $entityManager->remove($loi);
            $entityManager->flush();

           $this->addFlash('success', 'La loi a été supprimée.');

            return $this->redirectToRoute('loi.index', [], 303);
        }

        return $this->render('admin\loi\delete.twig', [
            'form' => $form->createView(),
            'loi' => $loi,
        ]);
    }

    /**
     * Retire le document d'une competence.
     */
    #[Route('/loi/{loi}/removeDocument', name: 'loi.document.remove')]
    public function removeDocumentAction(Request $request,  EntityManagerInterface $entityManager, #[MapEntity] Loi $loi): RedirectResponse
    {
        $loi->setDocumentUrl(null);

        $entityManager->persist($loi);
        $entityManager->flush();
        $this->addFlash('success', 'La loi a été mise à jour.');

        return $this->redirectToRoute('loi.index');
    }

    /**
     * Téléchargement du document lié à une compétence.
     */
    #[Route('/loi/{loi}/document', name: 'loi.document')]
    public function getDocumentAction(Request $request,  EntityManagerInterface $entityManager, #[MapEntity] Loi $loi): BinaryFileResponse
    {
        $document = $loi->getDocumentUrl();

        $file = __DIR__.'/../../private/documents/'.$document;

        return new BinaryFileResponse($file);
        
        /*
        $stream = static function () use ($file): void {
            readfile($file);
        };
        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$loi->getLabel().'.pdf"',
        ]);*/
    }
}
