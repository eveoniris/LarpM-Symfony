<?php

namespace App\Controller;

use App\Entity\Rangement;
use App\Form\Type\RangementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STOCK')]
class StockRangementController extends AbstractController
{
    #[Route('/stock/rangement', name: 'stockRangement.index')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository(Rangement::class);
        $rangements = $repo->findAll();

        return $this->render('stock/rangement/index.twig', ['rangements' => $rangements]);
    }

    #[Route('/stock/rangement/add', name: 'stockRangement.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $rangement = new Rangement();

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

            return $this->redirectToRoute('stockRangement.index');
        }

        return $this->render('stock/rangement/add.twig', ['form' => $form->createView()]);
    }

    #[Route('/stock/rangement/{rangement}/update', name: 'stockRangement.update')]
    public function updateAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Rangement $rangement): RedirectResponse|Response
    {
        $form = $this->createForm(RangementType::class, $rangement)
            ->add('update', SubmitType::class)
            ->add('delete', SubmitType::class);

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

            return $this->redirectToRoute('stockRangement.index');
        }

        return $this->render('stock/rangement/update.twig', [
            'rangement' => $rangement,
            'form' => $form->createView()]);
    }
}
