<?php

namespace App\Controller;

use App\Entity\Annonce;
use App\Form\AnnonceDeleteForm;
use App\Form\AnnonceForm;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse as RedirectResponseAlias;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REDACTEUR')]
class AnnonceController extends AbstractController
{
    /**
     * Présentation des annonces.
     */
    #[Route('/annonce', name: 'annonce.list')]
    public function listAction(Request $request, AnnonceRepository $repository): Response
    {
        $orderBy = $this->getRequestOrder(
            alias: 'a',
            allowedFields: $repository->getFieldNames()
        );

        $query = $repository->createQueryBuilder('a')
            ->orderBy(key($orderBy), current($orderBy));

        $paginator = $repository->findPaginatedQuery(
            $query->getQuery(), $this->getRequestLimit(), $this->getRequestPage()
        );

        return $this->render('annonce/list.twig', ['paginator' => $paginator]);
    }

    /**
     * Ajout d'une annonce.
     */
    #[Route('/annonce', name: 'annonce.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponseAlias|Response
    {
        $form = $this->createForm(AnnonceForm::class, new Annonce())
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $annonce = $form->getData();

            $entityManager->persist($annonce);
            $entityManager->flush();

            $this->addFlash('success', 'L\'annonce a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('annonce.list', [], 303);
            }
            if ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('annonce.add', [], 303);
            }
        }

        return $this->render('annonce/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour d'une annnonce.
     */
    #[Route('/annonce/{annonce}/update', name: 'annonce.update')]
    public function updateAction(Request $request, #[MapEntity] Annonce $annonce, EntityManagerInterface $entityManager): RedirectResponseAlias|Response
    {
        $form = $this->createForm(AnnonceForm::class, $annonce)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $annonce = $form->getData();

            $annonce->setUpdateDate(new \DateTime('NOW'));

            $entityManager->persist($annonce);
            $entityManager->flush();
            $this->addFlash('success', 'L\'annonce a été mise à jour.');

            return $this->redirectToRoute('annonce.list');
        }

        return $this->render('annonce/update.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une annonce.
     */
    #[Route('/annonce/{annonce}/detail', name: 'annonce.detail')]
    public function detailAction(Request $request, #[MapEntity] Annonce $annonce): Response
    {
        return $this->render('annonce/detail.twig', ['annonce' => $annonce]);
    }

    /**
     * Suppression d'une annonce.
     */
    #[Route('/annonce/{annonce}/delete', name: 'annonce.delete')]
    public function deleteAction(Request $request, EntityManagerInterface $entityManager, Annonce $annonce): RedirectResponseAlias|Response
    {
        $form = $this->createForm(AnnonceDeleteForm::class, $annonce)
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $annonce = $form->getData();

            $entityManager->remove($annonce);
            $entityManager->flush();

            $this->addFlash('success', 'L\'annonce a été supprimée.');

            return $this->redirectToRoute('annonce.list');
        }

        return $this->render('annonce/delete.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }
}
