<?php

namespace App\Controller;

use App\Entity\Chronologie;
use App\Entity\Territoire;
use App\Form\ChronologieForm;
use App\Form\ChronologieRemoveForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SCENARISTE')]
class ChronologieController extends AbstractController
{
    /**
     * API : mettre à jour un événement
     * POST /api/chronologies/{event}.
     */
    #[Route('/api/chronologies/{event}', name: 'api.chronologie.update')]
    public function apiUpdateAction(Request $request, EntityManagerInterface $entityManager, Chronologie $event): JsonResponse
    {
        $event = $request->get('event');

        $payload = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        $territoire = $entityManager->find(Territoire::class, $payload->territoire_id);

        $event->setTerritoire($territoire);
        $event->JsonUnserialize($payload);

        $entityManager->persist($event);
        $entityManager->flush();

        return new JsonResponse($payload);
    }

    /**
     * API : supprimer un événement
     * DELETE /api/chronologies/{event}.
     */
    #[Route('/api/chronologies/{event}', name: 'api.chronologie.delete')]
    public function apiDeleteAction(Request $request, EntityManagerInterface $entityManager, Chronologie $event): JsonResponse
    {
        $event = $request->get('event');
        $entityManager->remove($event);
        $entityManager->flush();

        return new JsonResponse();
    }

    /**
     * API : ajouter un événement
     * POST /api/chronologies.
     */
    #[Route('/api/chronologies', name: 'api.chronologie.add')]
    public function apiAddAction(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $payload = json_decode($request->getContent());

        $territoire = $entityManager->find(Territoire::class, $payload->territoire_id);

        $event = new Chronologie();

        $event->setTerritoire($territoire);
        $event->JsonUnserialize($payload);

        $entityManager->persist($event);
        $entityManager->flush();

        return new JsonResponse($payload);
    }

    #[Route('/chronologie', name: 'chronologie.index')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository(Chronologie::class);
        $chronologies = $repo->findPaginated(
            $this->getRequestPage(),
            $this->getRequestLimit(),
        );

        return $this->render('chronologie/index.twig', ['paginator' => $chronologies]);
    }

    #[Route('/chronologie/add', name: 'chronologie.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager)
    {
        $chronologie = new Chronologie();

        // Un territoire peut avoir été passé en paramètre
        $territoireId = $request->get('territoire');
        if ($territoireId) {
            $territoire = $entityManager->find(Territoire::class, $territoireId);
            if ($territoire) {
                $chronologie->setTerritoire($territoire);
            }
        }

        $form = $this->createForm(ChronologieForm::class, $chronologie, ['territoireId' => $territoireId])
            ->add('visibilite', ChoiceType::class, [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => array(
                    'Seul les scénaristes peuvent voir ceci' => 'PRIVATE',
                    'Tous les joueurs peuvent voir ceci' => 'PUBLIC',
                    'Seul les membres d\'un groupe lié à ce territoire peuvent voir ceci' => 'GROUPE_MEMBER',
                ),
            ])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $chronologie = $form->getData();

            $entityManager->persist($chronologie);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été ajouté.');

            return $this->redirectToRoute('chronologie.index');
        }

        return $this->render('chronologie/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/chronologie/{chronologie}/update', name: 'chronologie.update')]
    public function updateAction(Request $request, EntityManagerInterface $entityManager, Chronologie $chronologie)
    {
        if (!$chronologie) {
            return $this->redirectToRoute('chronologie.index');
        }

        $form = $this->createForm(ChronologieForm::class, $chronologie)
            ->add('visibilite', ChoiceType::class, [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => array(
                    'Seul les scénaristes peuvent voir ceci' => 'PRIVATE',
                    'Tous les joueurs peuvent voir ceci' => 'PUBLIC',
                    'Seul les membres d\'un groupe lié à ce territoire peuvent voir ceci' => 'GROUPE_MEMBER',
                ),
            ])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $chronologie = $form->getData();

            $entityManager->persist($chronologie);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été mis à jour.');

            return $this->redirectToRoute('chronologie.index');
        }

        return $this->render('chronologie/update.twig', [
            'form' => $form->createView(),
            'chronologie' => $chronologie,
        ]);
    }

    #[Route('/chronologie/{chronologie}/remove', name: 'chronologie.remove')]
    public function removeAction(Request $request, EntityManagerInterface $entityManager, Chronologie $chronologie)
    {
        if (!$chronologie) {
            return $this->redirectToRoute('chronologie.index');
        }

        $form = $this->createForm(ChronologieRemoveForm::class, $chronologie)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $chronologie = $form->getData();

            $entityManager->remove($chronologie);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été supprimé.');

            return $this->redirectToRoute('chronologie.index');
        }

        return $this->render('chronologie/remove.twig', [
            'chronologie' => $chronologie,
            'form' => $form->createView(),
        ]);
    }
}
