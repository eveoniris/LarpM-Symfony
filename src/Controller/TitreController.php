<?php


namespace App\Controller;

use App\Entity\Titre;
use App\Form\TitreDeleteForm;
use App\Form\TitreForm;
use App\Repository\TitreRepository;
use App\Service\PagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class TitreController extends AbstractController
{
    /**
     * Liste des titres.
     */
    #[Route('/titre', name: 'titre.list')]
    public function adminListAction(Request $request, PagerService $pagerService, TitreRepository $titreRepository): Response
    {
        $pagerService->setRequest($request)->setRepository($titreRepository)->setLimit(50);

        return $this->render('titre/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $titreRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Detail d'un titre.
     */
    #[Route('/titre/{titre}/detail', name: 'titre.detail')]
    public function adminDetailAction(Request $request,  EntityManagerInterface $entityManager, Titre $titre): Response
    {
        return $this->render('titre/detail.twig', [
            'titre' => $titre,
        ]);
    }

    /**
     * Ajoute un titre.
     */
    #[Route('/titre/add', name: 'titre.add')]
    public function adminAddAction(Request $request,  EntityManagerInterface $entityManager): Response|RedirectResponse
    {
        $titre = new Titre();

        $form = $this->createForm(TitreForm::class, $titre)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->getData();

            $entityManager->persist($titre);
            $entityManager->flush();

           $this->addFlash('success', 'Le titre a été ajouté');

            return $this->redirectToRoute('titre.detail', ['titre' => $titre->getId()], 303);
        }

        return $this->render('titre/add.twig', [
            'titre' => $titre,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un titre.
     */
    #[Route('/titre/{titre}/update', name: 'titre.update')]
    public function adminUpdateAction(Request $request,  EntityManagerInterface $entityManager, Titre $titre): Response|RedirectResponse
    {
        $form = $this->createForm(TitreForm::class, $titre)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->getData();

            $entityManager->persist($titre);
            $entityManager->flush();

           $this->addFlash('success', 'Le titre a été sauvegardé');

            return $this->redirectToRoute('titre.detail', ['titre' => $titre->getId()], 303);
        }

        return $this->render('titre/update.twig', [
            'titre' => $titre,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un titre.
     */
    #[Route('/titre/{titre}/delete', name: 'titre.delete')]
    public function adminDeleteAction(Request $request,  EntityManagerInterface $entityManager, Titre $titre): Response|RedirectResponse
    {
        $form = $this->createForm(TitreDeleteForm::class, $titre)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->getData();

            $entityManager->remove($titre);
            $entityManager->flush();

           $this->addFlash('success', 'Le titre a été suprimé');

            return $this->redirectToRoute('titre.list', [], 303);
        }

        return $this->render('titre/delete.twig', [
            'titre' => $titre,
            'form' => $form->createView(),
        ]);
    }
}
