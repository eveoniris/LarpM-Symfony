<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_STOCK')]
class StockLocalisationController extends AbstractController
{
    #[Route('/stock/localisation', name: 'stockLocalisation.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Localisation::class);
        $localisations = $repo->findAll();

        return $this->render('stock/localisation/index.twig', ['localisations' => $localisations]);
    }

    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $localisation = new \App\Entity\Localisation();

        $form = $this->createForm(LocalisationType::class(), $localisation)
            ->add('save', 'submit');

        // on passe la requête de l'utilisateur au formulaire
        $form->handleRequest($request);

        // si la requête est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // on récupére les data de l'utilisateur
            $localisation = $form->getData();
            $entityManager->persist($localisation);
            $entityManager->flush();

           $this->addFlash('success', 'La localisation a été ajoutée.');

            return $this->redirectToRoute('stock_localisation_index');
        }

        return $this->render('stock/localisation/add.twig', ['form' => $form->createView()]);
    }

    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $repo = $entityManager->getRepository('\\'.\App\Entity\Localisation::class);
        $localisation = $repo->find($id);

        $form = $this->createForm(LocalisationType::class(), $localisation)
            ->add('update', 'submit')
            ->add('delete', 'submit');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $localisation = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($localisation);
                $entityManager->flush();
               $this->addFlash('success', 'La localisation a été mise à jour');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($localisation);
                $entityManager->flush();
               $this->addFlash('success', 'La localisation a été suprimée');
            }

            return $this->redirectToRoute('stock_localisation_index');
        }

        return $this->render('stock/localisation/update.twig', [
            'localisation' => $localisation,
            'form' => $form->createView()]);
    }
}
