<?php


namespace App\Controller;

use App\Entity\PersonnageSecondaire;
use App\Form\PersonnageSecondaireDeleteForm;
use App\Form\PersonnageSecondaireForm;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_REGLE')]
class PersonnageSecondaireController extends AbstractController
{
    /**
     * affiche la liste des personnages secondaires.
     */
    #[Route('/personnageSecondaire', name: 'personnageSecondaire.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\PersonnageSecondaire::class);
        $personnageSecondaires = $repo->findAll();

        return $this->render('admin/personnageSecondaire/index.twig', ['personnageSecondaires' => $personnageSecondaires]);
    }

    /**
     * Detail d'un personnage secondaire.
     */
    #[Route('/personnageSecondaire/detail/{id}', name: 'personnageSecondaire.detail')]
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, #[MapEntity] PersonnageSecondaire $personnageSecondaire)
    {
        return $this->render('admin/personnageSecondaire/detail.twig', ['personnageSecondaire' => $personnageSecondaire]);
    }

    /**
     * Ajout d'un personnage secondaire.
     */
    #[Route('/personnageSecondaire/add', name: 'personnageSecondaire.add')]
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(PersonnageSecondaireForm::class, new PersonnageSecondaire())
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnageSecondaire = $form->getData();

            /*
             * Pour toutes les compétences de la classe
             */
            foreach ($personnageSecondaire->getPersonnageSecondaireCompetences() as $personnageSecondaireCompetence) {
                $personnageSecondaireCompetence->setPersonnageSecondaire($personnageSecondaire);
            }

            $entityManager->persist($personnageSecondaire);
            $entityManager->flush();

           $this->addFlash('success', 'Le personnage secondaire été sauvegardé');

            return $this->redirectToRoute('personnageSecondaire.list', [], 303);
        }

        return $this->render('admin/personnageSecondaire/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour d'un personnage secondaire.
     */
    #[Route('/personnageSecondaire/update/{id}', name: 'personnageSecondaire.update')]
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, #[MapEntity] PersonnageSecondaire $personnageSecondaire)
    {
        /**
         *  Crée un tableau contenant les objets personnageSecondaireCompetences courants de la base de données.
         */
        $originalPersonnageSecondaireComptences = new ArrayCollection();
        foreach ($personnageSecondaire->getPersonnageSecondaireCompetences() as $personnageSecondaireCompetence) {
            $originalPersonnageSecondaireComptences->add($personnageSecondaireCompetence);
        }

        $form = $this->createForm(PersonnageSecondaireForm::class, $personnageSecondaire)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnageSecondaire = $form->getData();

            /*
             * Pour toutes les compétences de la classe
             */
            foreach ($personnageSecondaire->getPersonnageSecondaireCompetences() as $personnageSecondaireCompetence) {
                $personnageSecondaireCompetence->setPersonnageSecondaire($personnageSecondaire);
            }

            /*
             *  supprime la relation entre le groupeClasse et le groupe
             */
            foreach ($originalPersonnageSecondaireComptences as $personnageSecondaireCompetence) {
                if (false == $personnageSecondaire->getPersonnageSecondaireCompetences()->contains($personnageSecondaireCompetence)) {
                    $entityManager->remove($personnageSecondaireCompetence);
                }
            }

            $entityManager->persist($personnageSecondaire);
            $entityManager->flush();
           $this->addFlash('success', 'Le personnage secondaire a été mis à jour.');

            return $this->redirectToRoute('personnageSecondaire.list');
        }

        return $this->render('admin/personnageSecondaire/update.twig', [
            'personnageSecondaire' => $personnageSecondaire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un personnage secondaire.
     */
    #[Route('/personnageSecondaire/delete/{id}', name: 'personnageSecondaire.delete')]
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, #[MapEntity] PersonnageSecondaire $personnageSecondaire)
    {
        $form = $this->createForm(PersonnageSecondaireDeleteForm::class, $personnageSecondaire)
            ->add('delete', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnageSecondaire = $form->getData();

            foreach ($personnageSecondaire->getPersonnageSecondaireCompetences() as $personnageSecondaireCompetence) {
                $entityManager->remove($personnageSecondaireCompetence);
            }

            $entityManager->remove($personnageSecondaire);
            $entityManager->flush();

           $this->addFlash('success', 'Le personnage secondaire a été supprimé.');

            return $this->redirectToRoute('personnageSecondaire.list', [], 303);
        }

        return $this->render('admin/personnageSecondaire/delete.twig', [
            'personnageSecondaire' => $personnageSecondaire,
            'form' => $form->createView(),
        ]);
    }
}
