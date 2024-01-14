<?php


namespace App\Controller;

use App\Form\TitreDeleteForm;
use App\Form\TitreForm;
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
    public function adminListAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Titre::class);
        $titres = $repo->findAll();

        return $this->render('titre/list.twig', ['titres' => $titres]);
    }

    /**
     * Detail d'un titre.
     */
    public function adminDetailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $titre = $request->get('titre');

        return $this->render('titre/detail.twig', [
            'titre' => $titre,
        ]);
    }

    /**
     * Ajoute un titre.
     */
    public function adminAddAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $titre = new \App\Entity\Titre();

        $form = $this->createForm(TitreForm::class, $titre)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->getData();

            $entityManager->persist($titre);
            $entityManager->flush();

           $this->addFlash('success', 'Le titre a été ajouté');

            return $this->redirectToRoute('titre.admin.detail', ['titre' => $titre->getId()], [], 303);
        }

        return $this->render('titre/add.twig', [
            'titre' => $titre,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un titre.
     */
    public function adminUpdateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $titre = $request->get('titre');

        $form = $this->createForm(TitreForm::class, $titre)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->getData();

            $entityManager->persist($titre);
            $entityManager->flush();

           $this->addFlash('success', 'Le titre a été sauvegardé');

            return $this->redirectToRoute('titre.admin.detail', ['titre' => $titre->getId()], [], 303);
        }

        return $this->render('titre/update.twig', [
            'titre' => $titre,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un titre.
     */
    public function adminDeleteAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $titre = $request->get('titre');

        $form = $this->createForm(TitreDeleteForm::class, $titre)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->getData();

            $entityManager->remove($titre);
            $entityManager->flush();

           $this->addFlash('success', 'Le titre a été suprimé');

            return $this->redirectToRoute('titre.admin.list', [], 303);
        }

        return $this->render('titre/delete.twig', [
            'titre' => $titre,
            'form' => $form->createView(),
        ]);
    }
}
