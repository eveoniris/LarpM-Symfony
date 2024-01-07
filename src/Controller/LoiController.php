<?php


namespace App\Controller;

use App\Entity\Loi;
use App\Form\Loi\LoiDeleteForm;
use App\Form\Loi\LoiForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class LoiController extends AbstractController
{
    /**
     * Liste des loi.
     */
    #[Route('/loi', name: 'loi.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $lois = $entityManager->getRepository(\App\Entity\Loi::class)->findAll();

        return $this->render('admin\loi\index.twig', [
            'lois' => $lois,
        ]);
    }

    /**
     * Ajout d'une loi.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(LoiForm::class, new Loi())->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $loi = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('loi', [], 303);
                }

                $documentFilename = hash('md5', $loi->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $loi->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($loi);
            $entityManager->flush();

           $this->addFlash('success', 'La loi a été ajoutée.');

            return $this->redirectToRoute('loi', [], 303);
        }

        return $this->render('admin\loi\add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une loi.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Loi $loi)
    {
        return $this->render('admin\loi\detail.twig', [
            'loi' => $loi,
        ]);
    }

    /**
     * Mise à jour d'une loi.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Loi $loi)
    {
        $form = $this->createForm(LoiForm::class, $loi);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $loi = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('loi', [], 303);
                }

                $documentFilename = hash('md5', $loi->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $loi->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($loi);
            $entityManager->flush();

           $this->addFlash('success', 'La loi a été mise à jour.');

            return $this->redirectToRoute('loi', [], 303);
        }

        return $this->render('admin\loi\update.twig', [
            'form' => $form->createView(),
            'loi' => $loi,
        ]);
    }

    /**
     * Suppression d'une loi.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Loi $loi)
    {
        $form = $this->createForm(LoiDeleteForm::class, $loi)
            ->add('submit', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $loi = $form->getData();

            $entityManager->remove($loi);
            $entityManager->flush();

           $this->addFlash('success', 'La loi a été supprimée.');

            return $this->redirectToRoute('loi', [], 303);
        }

        return $this->render('admin\loi\delete.twig', [
            'form' => $form->createView(),
            'loi' => $loi,
        ]);
    }

    /**
     * Retire le document d'une competence.
     */
    public function removeDocumentAction(Request $request,  EntityManagerInterface $entityManager, Loi $loi)
    {
        $loi->setDocumentUrl(null);

        $entityManager->persist($loi);
        $entityManager->flush();
       $this->addFlash('success', 'La loi a été mise à jour.');

        return $this->redirectToRoute('loi');
    }

    /**
     * Téléchargement du document lié à une compétence.
     */
    public function getDocumentAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $loi = $request->get('loi');
        $document = $loi->getDocumentUrl();

        $file = __DIR__.'/../../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$loi->getLabel().'.pdf"',
        ]);
    }
}
