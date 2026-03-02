<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Appelation;
use App\Form\AppelationForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SCENARISTE')]
class AppelationController extends AbstractController
{
    /**
     * affiche le tableau de bord de gestion des appelations.
     */
    #[Route('/appelation', name: 'appelation.index')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $appelations = $entityManager->getRepository('\\' . Appelation::class)->findAll();
        $appelations = $this->sortAppelation($appelations);

        return $this->render('appelation/index.twig', ['appelations' => $appelations]);
    }

    /**
     * Detail d'une appelation.
     */
    #[Route('/appelation/{appelation}/detail', name: 'appelation.detail')]
    public function detailAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Appelation $appelation,
    ): Response {
        return $this->render('appelation/detail.twig', ['appelation' => $appelation]);
    }

    /**
     * Ajoute une appelation.
     */
    #[Route('/appelation/add', name: 'appelation.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AppelationForm::class, new Appelation())->add('save', SubmitType::class, [
            'label' => 'Sauvegarder',
        ])->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appelation = $form->getData();
            $entityManager->persist($appelation);
            $entityManager->flush();

            $this->addFlash('success', 'L\'appelation a été ajoutée.');

            if ($form->get('save') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('save')->isClicked()) {
                return $this->redirectToRoute('appelation.index', [], 303);
            } elseif ($form->get('save_continue') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('appelation.add', [], 303);
            }
        }

        return $this->render('appelation/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie une appelation.
     */
    #[Route('/appelation/{appelation}/update', name: 'appelation.update')]
    public function updateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Appelation $appelation,
    ): Response {
        $form = $this->createForm(AppelationForm::class, $appelation)->add('update', SubmitType::class, [
            'label' => 'Sauvegarder',
        ])->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appelation = $form->getData();

            if ($form->get('update') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('update')->isClicked()) {
                $entityManager->persist($appelation);
                $entityManager->flush();
                $this->addFlash('success', 'L\'appelation a été mise à jour.');

                return $this->redirectToRoute('appelation.detail', ['appelation' => $appelation->getId()], 303);
            }
            if ($form->get('delete') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('delete')->isClicked()) {
                $entityManager->remove($appelation);
                $entityManager->flush();
                $this->addFlash('success', 'L\'appelation a été supprimée.');

                return $this->redirectToRoute('appelation', [], 303);
            }
        }

        return $this->render('appelation/update.twig', [
            'appelation' => $appelation,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Classement des appelations par groupe.
     *
     * @param array<int, Appelation> $appelations
     *
     * @return array<int, Appelation>
     */
    public function sortAppelation(array $appelations): array
    {
        $root = [];
        $result = [];

        // recherche des racines (appelations n'ayant pas de parent
        // dans la liste des appelations fournis)
        foreach ($appelations as $appelation) {
            if (\in_array($appelation->getAppelation(), $appelations, true)) {
                continue;
            }

            $root[] = $appelation;
        }

        foreach ($root as $appelation) {
            if (\count($appelation->getAppelations()) > 0) {
                $childs = array_merge([$appelation], $this->sortAppelation($appelation->getAppelations()->toArray()));

                $result = [...$result, ...$childs];
            } else {
                $result[] = $appelation;
            }
        }

        return $result;
    }
}
