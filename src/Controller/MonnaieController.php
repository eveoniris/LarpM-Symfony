<?php


namespace App\Controller;

use App\Entity\Monnaie;
use App\Form\Monnaie\MonnaieDeleteForm;
use App\Form\Monnaie\MonnaieForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class MonnaieController extends AbstractController
{
    /**
     * Liste les monnaies.
     */
    #[Route('/monnaie', name: 'monnaie.list')]
    public function listAction( EntityManagerInterface $entityManager, Request $request)
    {
        $monnaies = $entityManager->getRepository('\\'.\App\Entity\Monnaie::class)->findAll();

        return $this->render('admin/monnaie/list.twig', [
            'monnaies' => $monnaies,
        ]);
    }

    /**
     * Ajoute une monnaie.
     */
    public function addAction( EntityManagerInterface $entityManager, Request $request)
    {
        $form = $this->createForm(MonnaieForm::class, new Monnaie())
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $monnaie = $form->getData();
            $entityManager->persist($monnaie);
            $entityManager->flush();

           $this->addFlash('success', 'La monnaie a été enregistrée.');

            return $this->redirectToRoute('monnaie', [], 303);
        }

        return $this->render('admin/monnaie/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une monnaie.
     */
    public function updateAction( EntityManagerInterface $entityManager, Request $request, Monnaie $monnaie)
    {
        $form = $this->createForm(MonnaieForm::class, $monnaie)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $monnaie = $form->getData();
            $entityManager->persist($monnaie);
            $entityManager->flush();

           $this->addFlash('success', 'La monnaie a été enregistrée.');

            return $this->redirectToRoute('monnaie', [], 303);
        }

        return $this->render('admin/monnaie/update.twig', [
            'monnaie' => $monnaie,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une monnaie.
     */
    public function deleteAction( EntityManagerInterface $entityManager, Request $request, Monnaie $monnaie)
    {
        $form = $this->createForm(MonnaieDeleteForm::class, $monnaie)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $monnaie = $form->getData();
            $entityManager->remove($monnaie);
            $entityManager->flush();

           $this->addFlash('success', 'La monnaie a été supprimée.');

            return $this->redirectToRoute('monnaie', [], 303);
        }

        return $this->render('admin/monnaie/delete.twig', [
            'monnaie' => $monnaie,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Fourni le détail d'une monnaie.
     */
    public function detailAction( EntityManagerInterface $entityManager, Request $request, Monnaie $monnaie)
    {
        return $this->render('admin/monnaie/detail.twig', [
            'monnaie' => $monnaie,
        ]);
    }
}
