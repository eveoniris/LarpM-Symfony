<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Level;
use App\Form\NiveauForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
#[Route('/niveau', name: 'niveau.')]
class NiveauController extends AbstractController
{
    public function indexAction(EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository(Level::class);
        $niveaux = $repo->findAll();

        return $this->render('niveau/index.twig', ['niveaux' => $niveaux]);
    }

    public function addAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $niveau = new Level();

        $form = $this->createForm(NiveauForm::class, $niveau)->add('save', SubmitType::class, [
            'label' => 'Sauvegarder',
        ])->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $niveau = $form->getData();

            $entityManager->persist($niveau);
            $entityManager->flush();

            $this->addFlash('success', 'Le niveau a été ajouté.');

            if ($form->get('save') instanceof ClickableInterface && $form->get('save')->isClicked()) {
                return $this->redirectToRoute('niveau', [], 303);
            } elseif ($form->get('save_continue') instanceof ClickableInterface && $form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('niveau.add', [], 303);
            }
        }

        return $this->render('niveau/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function updateAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $id = $request->get('index');

        $niveau = $entityManager->find(Level::class, $id);

        $form = $this->createForm(NiveauForm::class, $niveau)->add('update', SubmitType::class, [
            'label' => 'Sauvegarder',
        ])->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $niveau = $form->getData();

            if ($form->get('update') instanceof ClickableInterface && $form->get('update')->isClicked()) {
                $entityManager->persist($niveau);
                $entityManager->flush();
                $this->addFlash('success', 'Le niveau a été mis à jour.');
            } elseif ($form->get('delete') instanceof ClickableInterface && $form->get('delete')->isClicked()) {
                $entityManager->remove($niveau);
                $entityManager->flush();
                $this->addFlash('success', 'Le niveau a été supprimé.');
            }

            return $this->redirectToRoute('niveau');
        }

        return $this->render('niveau/update.twig', [
            'niveau' => $niveau,
            'form' => $form->createView(),
        ]);
    }

    public function detailAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $id = $request->get('index');

        $niveau = $entityManager->find(Level::class, $id);

        if ($niveau) {
            return $this->render('niveau/detail.twig', ['niveau' => $niveau]);
        }
        $this->addFlash('error', 'La niveau n\'a pas été trouvé.');

        return $this->redirectToRoute('niveau');
    }

    public function detailExportAction(Request $request, EntityManagerInterface $entityManager): void
    {
    }

    public function exportAction(Request $request, EntityManagerInterface $entityManager): void
    {
    }
}
