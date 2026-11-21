<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Gn;
use App\Entity\GnTimelineStep;
use App\Form\Gn\GnTimelineStepType;
use App\Enum\GnTimelineType;
use App\Repository\GnTimelineStepRepository;
use App\Service\GnTimeline\GnTimelineExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
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

        if ($form->isSubmitted() && $form->isValid() && $this->verifier($form, $etape)) {
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

        if ($form->isSubmitted() && $form->isValid() && $this->verifier($form, $etape)) {
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

    #[Route('/gn/etape/{etape}/execute', name: 'gn.etape.execute', methods: ['POST'])]
    public function executeAction(Request $request, GnTimelineStep $etape, GnTimelineExecutor $executor): Response
    {
        if ($this->isCsrfTokenValid('gn_etape_execute_' . $etape->getId(), (string) $request->request->get('_token'))) {
            $resultat = $executor->executer($etape);
            $this->addFlash($resultat['ok'] ? 'success' : 'error', $resultat['message']);
        }

        return $this->redirectToRoute('gn.etapes', ['gn' => $etape->getGn()?->getId()]);
    }

    /**
     * Règles de cohérence : XP obligatoire pour l'XP de participation, pas d'exécution automatique pour les types risqués.
     */
    private function verifier(FormInterface $form, GnTimelineStep $etape): bool
    {
        if (GnTimelineType::XP_PARTICIPATION === $etape->getType() && ($etape->getXp() ?? 0) <= 0) {
            $form->get('xp')->addError(new FormError("Indiquez l'XP à donner (supérieur à 0)."));

            return false;
        }

        if ($etape->isAuto() && !$etape->getType()->autoAutorise()) {
            $form->get('auto')->addError(new FormError("Ce type d'étape ne peut pas être exécuté automatiquement."));

            return false;
        }

        return true;
    }
}
