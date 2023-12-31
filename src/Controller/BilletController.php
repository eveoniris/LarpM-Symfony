<?php

namespace App\Controller;

use App\Entity\Billet;
use App\Form\BilletDeleteForm;
use App\Form\BilletForm;
use App\Repository\BilletRepository;
use App\Repository\ParticipantRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BilletController extends AbstractController
{
    /**
     * Liste des billets.
     */
    #[Route('/billet/list', name: 'billet.list')]
    public function listAction(Request $request, BilletRepository $billetRepository): Response
    {
        $orderBy = $this->getRequestOrder(
            alias: 'b',
            allowedFields: $billetRepository->getFieldNames()
        );

        $query = $billetRepository->createQueryBuilder('b')
            ->orderBy(key($orderBy), current($orderBy));

        $paginator = $billetRepository->findPaginatedQuery(
            $query->getQuery(), $this->getRequestLimit(), $this->getRequestPage()
        );

        return $this->render(
            'billet\list.twig', ['paginator' => $paginator]
        );
    }

    /**
     * Ajout d'un billet.
     */
    #[Route('/billet/add', name: 'billet.add')]
    public function addAction(Request $request, BilletRepository $repository, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $billet = new Billet();
        $gnId = $request->get('gn');

        if ($gnId) {
            $gn = $repository->find($gnId);
            $billet->setGn($gn);
        }

        $form = $this->createForm(BilletForm::class, $billet)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $billet = $form->getData();
            $billet->setCreateur($this->getUser());
            $entityManager->persist($billet);
            $entityManager->flush();

            $this->addFlash('success', 'Le billet a été ajouté.');

            return $this->redirectToRoute('billet.list', [], 303);
        }

        return $this->render(
            'billet\add.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Détail d'un billet.
     */
    #[Route('/billet/detail/{billet}', name: 'billet.detail')]
    public function detailAction(Request $request, #[MapEntity] Billet $billet): Response
    {
        return $this->render(
            'billet\detail.twig',
            ['billet' => $billet]
        );
    }

    /**
     * Mise à jour d'un billet.
     */
    #[Route('/billet/update/{billet}', name: 'billet.update')]
    public function updateAction(Request $request, #[MapEntity] Billet $billet, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $form = $this->createForm(BilletForm::class, $billet)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $billet = $form->getData();
            $entityManager->persist($billet);
            $entityManager->flush();

            $this->addFlash('success', 'Le billet a été mis à jour.');

            return $this->redirectToRoute('billet.list', [], 303);
        }

        return $this->render('billet\update.twig',
            [
                'form' => $form->createView(),
                'billet' => $billet,
            ]
        );
    }

    /**
     * Suppression d'un billet.
     */
    #[Route('/billet/delete/{billet}', name: 'billet.delete')]
    public function deleteAction(Request $request, Billet $billet, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $form = $this->createForm(BilletDeleteForm::class, $billet)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Valider']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $billet = $form->getData();
            $entityManager->remove($billet); // TODO constraint on Participants
            $entityManager->flush();

            $this->addFlash('success', 'Le billet a été supprimé.');

            return $this->redirectToRoute('billet.list', [], 303);
        }

        return $this->render(
            'billet\delete.twig',
            [
                'form' => $form->createView(),
                'billet' => $billet,
            ]
        );
    }

    /**
     * Liste des utilisateurs ayant ce billet.
     */
    #[Route('/billet/participants/{billet}', name: 'billet.participants')]
    public function participantsAction(Request $request, #[MapEntity] Billet $billet, EntityManagerInterface $entityManager): Response
    {
        $participantRepository = $entityManager->getRepository('\\'.\App\Entity\Participant::class);
        
        $alias = ParticipantRepository::getEntityAlias();
        
        $orderBy = $this->getRequestOrder(
            defOrderBy: 'id',
            alias: $alias,
            allowedFields: $participantRepository->getFieldNames()
        );

        $criterias[] = Criteria::create()->where(
            Criteria::expr()?->eq($alias.'.billet', $billet->getId())
        );

        $paginator = $participantRepository->getPaginator(
            limit: $this->getRequestLimit(),
            page: $this->getRequestPage(),
            orderBy: $orderBy,
            alias: $alias,
            criterias: $criterias
        );

        return $this->render(
            'billet\participants.twig', 
            [
                'billet' => $billet,
                'paginator' => $paginator
            ]
        );
    }
}
