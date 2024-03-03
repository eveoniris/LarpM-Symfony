<?php

namespace App\Controller;

use App\Entity\Genre;
use App\Form\GenreForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
class GenreController extends AbstractController
{
    /**
     * Présentation des genres.
     */
    #[Route('/genre', name: 'genre.index')]
    public function indexAction(EntityManagerInterface $entityManager): Response
    {
        $genres = $entityManager->getRepository(Genre::class)->findAll();

        return $this->render('genre/index.twig', ['genres' => $genres]);
    }

    /**
     * Ajout d'un genre.
     */
    #[Route('/genre/add', name: 'genre.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $genre = new Genre();

        $form = $this->createForm(GenreForm::class, $genre)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $genre = $form->getData();

            $entityManager->persist($genre);
            $entityManager->flush();

            $this->addFlash('success', 'Le genre a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('genre', [], 303);
            }

            if ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('genre.add', [], 303);
            }
        }

        return $this->render('genre/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'un genre.
     */
    #[Route('/genre/{genre}', name: 'genre.detail')]
    public function detailAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] ?Genre $genre): RedirectResponse|Response
    {
        if (null !== $genre) {
            return $this->render('genre/detail.twig', ['genre' => $genre]);
        }

        $this->addFlash('error', 'Le genre n\'a pas été trouvé.');
        $this->createNotFoundException(); // Todo render 404 ?

        return $this->redirectToRoute('genre');
    }

    /**
     * Met à jour un genre.
     */
    #[Route('/genre/{genre}/update', name: 'genre.update')]
    public function updateAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] ?Genre $genre): RedirectResponse|Response
    {
        $form = $this->createForm(GenreForm::class, $genre)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $genre = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($genre);
                $entityManager->flush();
                $this->addFlash('success', 'Le genre a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($genre);
                $entityManager->flush();
                $this->addFlash('success', 'Le genre a été supprimé.');
            }

            return $this->redirectToRoute('genre');
        }

        return $this->render('genre/update.twig', [
            'genre' => $genre,
            'form' => $form->createView(),
        ]);
    }
}
