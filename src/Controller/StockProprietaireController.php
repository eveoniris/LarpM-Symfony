<?php

namespace App\Controller;

use App\Entity\Proprietaire;
use App\Form\Type\ProprietaireType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STOCK')]
class StockProprietaireController extends AbstractController
{
    #[Route('/stock/proprietaire', name: 'stockProprietaire.index')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository(Proprietaire::class);
        $proprietaires = $repo->findAll();

        return $this->render('stock/proprietaire/index.twig', ['proprietaires' => $proprietaires]);
    }

    #[Route('/stock/proprietaire/add', name: 'stockProprietaire.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $proprietaire = new Proprietaire();

        $form = $this->createForm(ProprietaireType::class, $proprietaire)
            ->add('save', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $proprietaire = $form->getData();
            $entityManager->persist($proprietaire);
            $entityManager->flush();

            $this->addFlash('success', 'Le propriétaire a été ajouté');

            return $this->redirectToRoute('stockProprietaire.index');
        }

        return $this->render('stock/proprietaire/add.twig', ['form' => $form->createView()]);
    }

    #[Route('/stock/proprietaire/{proprietaire}/update', name: 'stockProprietaire.update')]
    public function updateAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Proprietaire $proprietaire): RedirectResponse|Response
    {
        $form = $this->createForm(ProprietaireType::class, $proprietaire)
            ->add('update', SubmitType::class)
            ->add('delete', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // on récupére les data de l'utilisateur
            $proprietaire = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($proprietaire);
                $entityManager->flush();
                $this->addFlash('success', 'Le propriétaire a été mis à jour');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($proprietaire);
                $entityManager->flush();
                $this->addFlash('success', 'Le proprietaire a été supprimé');
            }

            return $this->redirectToRoute('stockProprietaire.index');
        }

        return $this->render('stock/proprietaire/update.twig', ['proprietaire' => $proprietaire, 'form' => $form->createView()]);
    }
}
