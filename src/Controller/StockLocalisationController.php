<?php

namespace App\Controller;

use App\Entity\Localisation;
use App\Form\Type\LocalisationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STOCK')]
class StockLocalisationController extends AbstractController
{
    #[Route('/stock/localisation', name: 'stockLocalisation.index')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository(Localisation::class);
        $localisations = $repo->findAll();

        return $this->render('stock/localisation/index.twig', ['localisations' => $localisations]);
    }

    #[Route('/stock/localisation/add', name: 'stockLocalisation.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $localisation = new Localisation();

        $form = $this->createForm(LocalisationType::class, $localisation)
            ->add('save', SubmitType::class);

        // on passe la requête de l'utilisateur au formulaire
        $form->handleRequest($request);

        // si la requête est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // on récupére les data de l'utilisateur
            $localisation = $form->getData();
            $entityManager->persist($localisation);
            $entityManager->flush();

            $this->addFlash('success', 'La localisation a été ajoutée.');

            return $this->redirectToRoute('stockLocalisation.index');
        }

        return $this->render('stock/localisation/add.twig', ['form' => $form->createView()]);
    }

    #[Route('/stock/localisation/{localisation}/update', name: 'stockLocalisation.update')]
    public function updateAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Localisation $localisation): RedirectResponse|Response
    {
        $form = $this->createForm(LocalisationType::class, $localisation)
            ->add('update', SubmitType::class)
            ->add('delete', SubmitType::class);

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

            return $this->redirectToRoute('stockLocalisation.index');
        }

        return $this->render('stock/localisation/update.twig', [
            'localisation' => $localisation,
            'form' => $form->createView()]);
    }
}
