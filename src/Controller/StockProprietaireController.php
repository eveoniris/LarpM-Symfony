<?php

namespace App\Controller;

use App\Form\Type\ProprietaireType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_STOCK')]
class StockProprietaireController extends AbstractController
{
    #[Route('/stock/proprietaire', name: 'stockProprietaire.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Proprietaire::class);
        $proprietaires = $repo->findAll();

        return $this->render('stock/proprietaire/index.twig', ['proprietaires' => $proprietaires]);
    }

    public function addAction(Request $request, EntityManagerInterface $entityManager)
    {
        $proprietaire = new \App\Entity\Proprietaire();

        $form = $this->createForm(ProprietaireType::class, $proprietaire)
            ->add('save', 'submit');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $proprietaire = $form->getData();
            $entityManager->persist($proprietaire);
            $entityManager->flush();

            $this->addFlash('success', 'Le propriétaire a été ajouté');

            return $this->redirectToRoute('stock_proprietaire_index');
        }

        return $this->render('stock/proprietaire/add.twig', ['form' => $form->createView()]);
    }

    public function updateAction(Request $request, EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $repo = $entityManager->getRepository('\\'.\App\Entity\Proprietaire::class);
        $proprietaire = $repo->find($id);

        $form = $this->createForm(ProprietaireType::class, $proprietaire)
            ->add('update', 'submit')
            ->add('delete', 'submit');

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

            return $this->redirectToRoute('stock_proprietaire_index');
        }

        return $this->render('stock/proprietaire/update.twig', ['proprietaire' => $proprietaire, 'form' => $form->createView()]);
    }
}
