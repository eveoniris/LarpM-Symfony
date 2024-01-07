<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_STOCK')]
class StockEtatController extends AbstractController
{
    #[Route('/stock/etat', name: 'stockEtat.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Etat::class);
        $etats = $repo->findAll();

        return $this->render('stock/etat/index.twig', ['etats' => $etats]);
    }

    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $etat = new \App\Entity\Etat();

        $form = $this->createForm(EtatType::class, $etat)
            ->add('save', 'submit');

        // on passe la requête de l'utilisateur au formulaire
        $form->handleRequest($request);

        // si la requête est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // on récupére les data de l'utilisateur
            $etat = $form->getData();
            $entityManager->persist($etat);
            $entityManager->flush();

            $this->addFlash('success', 'L\'état a été ajouté.');

            return $this->redirectToRoute('stock_etat_index');
        }

        return $this->render('stock/etat/add.twig', ['form' => $form->createView()]);
    }

    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $repo = $entityManager->getRepository('\\'.\App\Entity\Etat::class);
        $etat = $repo->find($id);

        $form = $this->createForm(EtatType::class, $etat)
            ->add('update', 'submit')
            ->add('delete', 'submit');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $etat = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($etat);
                $entityManager->flush();
                $this->addFlash('success', 'L\'état a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($etat);
                $entityManager->flush();
                $this->addFlash('success', 'L\'état a été supprimé.');
            }

            return $this->redirectToRoute('stock_etat_index');
        }

        return $this->render('stock/etat/update.twig', [
            'etat' => $etat,
            'form' => $form->createView()]);
    }
}
