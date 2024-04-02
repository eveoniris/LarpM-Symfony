<?php

namespace App\Controller;

use App\Entity\PersonnageSecondaire;
use App\Form\PersonnageSecondaireDeleteForm;
use App\Form\PersonnageSecondaireForm;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
class PersonnageSecondaireController extends AbstractController
{
    /**
     * affiche la liste des personnages secondaires.
     */
    #[Route('/personnageSecondaire', name: 'personnageSecondaire.list')]
    public function indexAction(): Response
    {
        return $this->render(
            'personnageSecondaire/index.twig',
            ['personnageSecondaires' => $this->entityManager->getRepository(PersonnageSecondaire::class)->findAll()]
        );
    }

    /**
     * Detail d'un personnage secondaire.
     */
    #[Route('/personnageSecondaire/detail/{id}', name: 'personnageSecondaire.detail')]
    public function detailAction(#[MapEntity] PersonnageSecondaire $personnageSecondaire): Response
    {
        return $this->render('personnageSecondaire/detail.twig', ['personnageSecondaire' => $personnageSecondaire]);
    }

    /**
     * Ajout d'un personnage secondaire.
     */
    #[Route('/personnageSecondaire/add', name: 'personnageSecondaire.add')]
    public function addAction(Request $request): RedirectResponse|Response
    {
        $form = $this->createForm(PersonnageSecondaireForm::class, new PersonnageSecondaire())
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnageSecondaire = $form->getData();

            /*
             * Pour toutes les compétences de la classe
             */
            foreach ($personnageSecondaire->getPersonnageSecondaireCompetences() as $personnageSecondaireCompetence) {
                $personnageSecondaireCompetence->setPersonnageSecondaire($personnageSecondaire);
            }

            $this->entityManager->persist($personnageSecondaire);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le personnage secondaire été sauvegardé');

            return $this->redirectToRoute('personnageSecondaire.list', [], 303);
        }

        return $this->render('personnageSecondaire/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour d'un personnage secondaire.
     */
    #[Route('/personnageSecondaire/update/{personnageSecondaire}', name: 'personnageSecondaire.update')]
    public function updateAction(Request $request, #[MapEntity] PersonnageSecondaire $personnageSecondaire): RedirectResponse|Response
    {
        /**
         *  Crée un tableau contenant les objets personnageSecondaireCompetences courants de la base de données.
         */
        $originalPersonnageSecondaireComptences = new ArrayCollection();
        foreach ($personnageSecondaire->getPersonnageSecondaireCompetences() as $personnageSecondaireCompetence) {
            $originalPersonnageSecondaireComptences->add($personnageSecondaireCompetence);
        }

        $form = $this->createForm(PersonnageSecondaireForm::class, $personnageSecondaire)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

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
                if (false === $personnageSecondaire->getPersonnageSecondaireCompetences()->contains($personnageSecondaireCompetence)) {
                    $this->entityManager->remove($personnageSecondaireCompetence);
                }
            }

            $this->entityManager->persist($personnageSecondaire);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le personnage secondaire a été mis à jour.');

            return $this->redirectToRoute('personnageSecondaire.list');
        }

        return $this->render('personnageSecondaire/update.twig', [
            'personnageSecondaire' => $personnageSecondaire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un personnage secondaire.
     */
    #[Route('/personnageSecondaire/delete/{personnageSecondaire}', name: 'personnageSecondaire.delete')]
    public function deleteAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] PersonnageSecondaire $personnageSecondaire): RedirectResponse|Response
    {
        $form = $this->createForm(PersonnageSecondaireDeleteForm::class, $personnageSecondaire)
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

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

        return $this->render('personnageSecondaire/delete.twig', [
            'personnageSecondaire' => $personnageSecondaire,
            'form' => $form->createView(),
        ]);
    }
}
