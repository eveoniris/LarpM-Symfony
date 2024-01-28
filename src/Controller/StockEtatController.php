<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Form\Type\EtatType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STOCK')]
class StockEtatController extends AbstractController
{
    #[Route('/stock/etat', name: 'stockEtat.index')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository(Etat::class);

        return $this->render('stock/etat/index.twig', ['etats' => $repo->findAll()]);
    }

    #[Route('/stock/etat/add', name: 'stockEtat.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $etat = new Etat();

        $form = $this->createForm(EtatType::class, $etat)
            ->add('save', SubmitType::class);

        // on passe la requête de l'utilisateur au formulaire
        $form->handleRequest($request);

        // si la requête est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // on récupère les data de l'utilisateur
            $etat = $form->getData();
            $entityManager->persist($etat);
            $entityManager->flush();

            $this->addFlash('success', 'L\'état a été ajouté.');

            return $this->redirectToRoute('stockEtat.index');
        }

        return $this->render('stock/etat/add.twig', ['form' => $form->createView()]);
    }

    #[Route('/stock/etat/{etat}/update', name: 'stockEtat.update')]
    public function updateAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Etat $etat): RedirectResponse|Response
    {
        $id = $request->get('index');

        $form = $this->createForm(EtatType::class, $etat)
            ->add('update', SubmitType::class)
            ->add('delete', SubmitType::class);

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

            return $this->redirectToRoute('stockEtat.index');
        }

        return $this->render('stock/etat/update.twig', [
            'etat' => $etat,
            'form' => $form->createView()]);
    }
}
