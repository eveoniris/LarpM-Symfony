<?php

namespace App\Controller;

use App\Form\Type\RangementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_STOCK')]
class StockRangementController extends AbstractController
{
    #[Route('/stock/rangement', name: 'stockRangement.index')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Rangement::class);
        $rangements = $repo->findAll();

        return $this->render('stock/rangement/index.twig', ['rangements' => $rangements]);
    }

    public function addAction(Request $request,  EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $rangement = new \App\Entity\Rangement();

        $form = $this->createForm(RangementType::class, $rangement)
            ->add('save', SubmitType::class);

        // on passe la requête de l'utilisateur au formulaire
        $form->handleRequest($request);

        // si la requête est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // on récupére les data de l'utilisateur
            $rangement = $form->getData();
            $entityManager->persist($rangement);
            $entityManager->flush();

           $this->addFlash('success', 'Le rangement a été ajoutée.');

            return $this->redirectToRoute('stock_rangement_index');
        }

        return $this->render('stock/rangement/add.twig', ['form' => $form->createView()]);
    }

    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $repo = $entityManager->getRepository('\\'.\App\Entity\Rangement::class);
        $rangement = $repo->find($id);

        $form = $this->createForm(RangementType::class(), $rangement)
            ->add('update', 'submit')
            ->add('delete', 'submit');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rangement = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($rangement);
                $entityManager->flush();
               $this->addFlash('success', 'Le rangement a été mise à jour');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($rangement);
                $entityManager->flush();
               $this->addFlash('success', 'Le rangement a été suprimé');
            }

            return $this->redirectToRoute('stock_rangement_index');
        }

        return $this->render('stock/rangement/update.twig', [
            'rangement' => $rangement,
            'form' => $form->createView()]);
    }
}
