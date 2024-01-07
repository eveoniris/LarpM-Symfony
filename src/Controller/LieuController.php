<?php


namespace App\Controller;

use App\Entity\Lieu;
use App\Form\LieuDeleteForm;
use App\Form\LieuDocumentForm;
use App\Form\LieuForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class LieuController extends AbstractController
{
    /**
     * Liste des lieux.
     */
    #[Route('/lieu', name: 'lieu.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $lieux = $entityManager->getRepository('\\'.\App\Entity\Lieu::class)->findAllOrderedByNom();

        return $this->render('admin/lieu/index.twig', ['lieux' => $lieux]);
    }

    /**
     * Imprimer la liste des documents.
     */
    public function printAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $lieux = $entityManager->getRepository('\\'.\App\Entity\Lieu::class)->findAllOrderedByNom();

        return $this->render('admin/lieu/print.twig', ['lieux' => $lieux]);
    }

    /**
     * Télécharger la liste des lieux.
     */
    public function downloadAction(Request $request,  EntityManagerInterface $entityManager): void
    {
        $lieux = $entityManager->getRepository('\\'.\App\Entity\Lieu::class)->findAllOrderedByNom();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_lieux_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv($output,
            [
                'nom',
                'description',
                'documents'], ';');

        foreach ($lieux as $lieu) {
            $line = [];
            $line[] = mb_convert_encoding((string) $lieu->getNom(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $lieu->getDescriptionRaw(), 'ISO-8859-1');

            $documents = '';
            foreach ($lieu->getDocuments() as $document) {
                $documents .= mb_convert_encoding((string) $document->getIdentity(), 'ISO-8859-1').', ';
            }

            $line[] = $documents;

            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Ajouter un lieu.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(LieuForm::class, new Lieu())
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lieu = $form->getData();

            $entityManager->persist($lieu);
            $entityManager->flush();

           $this->addFlash('success', 'Le lieu a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('lieu', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('lieu.add', [], 303);
            }
        }

        return $this->render('admin/lieu/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'un lieu.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Lieu $lieu)
    {
        return $this->render('admin/lieu/detail.twig', ['lieu' => $lieu]);
    }

    /**
     * Mise à jour d'un lieu.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Lieu $lieu)
    {
        $form = $this->createForm(LieuForm::class, $lieu)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lieu = $form->getData();
            $entityManager->persist($lieu);
            $entityManager->flush();

           $this->addFlash('success', 'Le lieu a été modifié.');

            return $this->redirectToRoute('lieu', [], 303);
        }

        return $this->render('admin/lieu/update.twig', [
            'lieu' => $lieu,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un lieu.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Lieu $lieu)
    {
        $form = $this->createForm(LieuDeleteForm::class, $lieu)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lieu = $form->getData();

            $entityManager->remove($lieu);
            $entityManager->flush();

           $this->addFlash('success', 'Le lieu a été supprimé.');

            return $this->redirectToRoute('lieu', [], 303);
        }

        return $this->render('admin/lieu/delete.twig', [
            'lieu' => $lieu,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gestion de la liste des documents lié à un lieu.
     */
    public function documentAction(Request $request,  EntityManagerInterface $entityManager, Lieu $lieu)
    {
        $form = $this->createForm(LieuDocumentForm::class, $lieu)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lieu = $form->getData();
            $entityManager->persist($lieu);
            $entityManager->flush();

           $this->addFlash('success', 'Le document a été ajouté au lieu.');

            return $this->redirectToRoute('lieu.detail', ['lieu' => $lieu->getId()]);
        }

        return $this->render('admin/lieu/documents.twig', [
            'lieu' => $lieu,
            'form' => $form->createView(),
        ]);
    }
}
