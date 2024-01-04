<?php


namespace App\Controller;

use App\Form\ChronologieForm;
use App\Form\ChronologieRemoveForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\ChronologieController.
 */
class ChronologieController extends AbstractController
{
    /**
     * API : mettre à jour un événement
     * POST /api/chronologies/{event}.
     */
    public function apiUpdateAction(Request $request,  EntityManagerInterface $entityManager): JsonResponse
    {
        $event = $request->get('event');

        $payload = json_decode($request->getContent());

        $territoire = $entityManager->find('\\'.\App\Entity\Territoire::class, $payload->territoire_id);

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
    public function apiDeleteAction(Request $request,  EntityManagerInterface $entityManager): JsonResponse
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
    public function apiAddAction(Request $request,  EntityManagerInterface $entityManager): JsonResponse
    {
        $payload = json_decode($request->getContent());

        $territoire = $entityManager->find('\\'.\App\Entity\Territoire::class, $payload->territoire_id);

        $event = new \App\Entity\Chronologie();

        $event->setTerritoire($territoire);
        $event->JsonUnserialize($payload);

        $entityManager->persist($event);
        $entityManager->flush();

        return new JsonResponse($payload);
    }

    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Chronologie::class);
        $chronologies = $repo->findAll();

        return $this->render('admin/chronologie/index.twig', ['chronologies' => $chronologies]);
    }

    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $chronologie = new \App\Entity\Chronologie();

        // Un territoire peut avoir été passé en paramètre
        $territoireId = $request->get('territoire');

        if ($territoireId) {
            $territoire = $entityManager->find('\\'.\App\Entity\Territoire::class, $territoireId);
            if ($territoire) {
                $chronologie->setTerritoire($territoire);
            }
        }

        $form = $this->createForm(ChronologieForm::class(), $chronologie)
            ->add('visibilite', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getChronologieVisibility(),
            ])
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $chronologie = $form->getData();

            $entityManager->persist($chronologie);
            $entityManager->flush();

           $this->addFlash('success', 'L\'événement a été ajouté.');

            return $this->redirectToRoute('chronologie');
        }

        return $this->render('admin/chronologie/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $chronologie = $entityManager->find('\\'.\App\Entity\Chronologie::class, $id);
        if (!$chronologie) {
            return $this->redirectToRoute('chronologie');
        }

        $form = $this->createForm(ChronologieForm::class(), $chronologie)
            ->add('visibilite', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getChronologieVisibility(),
            ])
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $chronologie = $form->getData();

            $entityManager->persist($chronologie);
            $entityManager->flush();

           $this->addFlash('success', 'L\'événement a été mis à jour.');

            return $this->redirectToRoute('chronologie');
        }

        return $this->render('admin/chronologie/update.twig', [
            'form' => $form->createView(),
            'chronologie' => $chronologie,
        ]);
    }

    public function removeAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $chronologie = $entityManager->find('\\'.\App\Entity\Chronologie::class, $id);
        if (!$chronologie) {
            return $this->redirectToRoute('chronologie');
        }

        $form = $this->createForm(ChronologieRemoveForm::class(), $chronologie)
            ->add('save', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $chronologie = $form->getData();

            $entityManager->remove($chronologie);
            $entityManager->flush();

           $this->addFlash('success', 'L\'événement a été supprimé.');

            return $this->redirectToRoute('chronologie');
        }

        return $this->render('admin/chronologie/remove.twig', [
            'chronologie' => $chronologie,
            'form' => $form->createView(),
        ]);
    }
}
