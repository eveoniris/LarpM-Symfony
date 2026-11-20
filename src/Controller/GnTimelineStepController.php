<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Gn;
use App\Entity\GnTimelineStep;
use App\Form\Gn\GnTimelineStepType;
use App\Repository\GnTimelineStepRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gestion des étapes de la chronologie d'un GN (administrateurs).
 */
#[IsGranted('ROLE_ADMIN')]
final class GnTimelineStepController extends AbstractController
{
    #[Route('/gn/{gn}/etapes', name: 'gn.etapes')]
    public function listAction(
        Request $request,
        Gn $gn,
        GnTimelineStepRepository $repository,
        EntityManagerInterface $entityManager,
    ): Response {
        $etape = (new GnTimelineStep())->setGn($gn);
        $form = $this->createForm(GnTimelineStepType::class, $etape);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($etape);
            $entityManager->flush();
            $this->addFlash('success', "L'étape a été ajoutée.");

            return $this->redirectToRoute('gn.etapes', ['gn' => $gn->getId()]);
        }

        return $this->render('gn_chronologie/etapes.twig', [
            'gn' => $gn,
            'etapes' => $repository->findByGn($gn),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/gn/etape/{etape}/update', name: 'gn.etape.update')]
    public function updateAction(Request $request, GnTimelineStep $etape, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GnTimelineStepType::class, $etape);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', "L'étape a été mise à jour.");

            return $this->redirectToRoute('gn.etapes', ['gn' => $etape->getGn()?->getId()]);
        }

        return $this->render('gn_chronologie/etape_update.twig', ['etape' => $etape, 'form' => $form->createView()]);
    }

    #[Route('/gn/etape/{etape}/delete', name: 'gn.etape.delete', methods: ['POST'])]
    public function deleteAction(Request $request, GnTimelineStep $etape, EntityManagerInterface $entityManager): Response
    {
        $gnId = $etape->getGn()?->getId();
        if ($this->isCsrfTokenValid('gn_etape_delete_' . $etape->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($etape);
            $entityManager->flush();
            $this->addFlash('success', "L'étape a été supprimée.");
        }

        return $this->redirectToRoute('gn.etapes', ['gn' => $gnId]);
    }
}
