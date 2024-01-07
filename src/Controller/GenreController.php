<?php


namespace App\Controller;

use App\Form\GenreForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_REGLE')]
class GenreController extends AbstractController
{
    /**
     * Présentation des genres.
     */
    #[Route('/genre', name: 'genre.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $genres = $entityManager->getRepository('\\'.\App\Entity\Genre::class)->findAll();

        return $this->render('admin/genre/index.twig', ['genres' => $genres]);
    }

    /**
     * Ajout d'un genre.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $genre = new \App\Entity\Genre();

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
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('genre.add', [], 303);
            }
        }

        return $this->render('admin/genre/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'un genre.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $genre = $entityManager->find('\\'.\App\Entity\Genre::class, $id);

        if ($genre) {
            return $this->render('admin/genre/detail.twig', ['genre' => $genre]);
        } else {
           $this->addFlash('error', 'Le genre n\'a pas été trouvé.');

            return $this->redirectToRoute('genre');
        }
    }

    /**
     * Met à jour un genre.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $genre = $entityManager->find('\\'.\App\Entity\Genre::class, $id);

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

        return $this->render('admin/genre/update.twig', [
            'genre' => $genre,
            'form' => $form->createView(),
        ]);
    }
}
