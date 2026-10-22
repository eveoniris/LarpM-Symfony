<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Gn;
use App\Enum\DepouillementType;
use App\Form\Depouillement\DepouillementSimulationType;
use App\Service\DepouillementService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Dépouillement des enveloppes de retour de jeu (joueur et groupe).
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
#[Route('/depouillement')]
final class DepouillementController extends AbstractController
{
    /**
     * Page de travail d'un GN : simulations en cours, création et purge.
     */
    #[Route('/gn/{gn}', name: 'depouillement.index', requirements: ['gn' => '\d+'], methods: ['GET', 'POST'])]
    public function indexAction(Request $request, Gn $gn, DepouillementService $depouillementService): Response
    {
        $form = $this->createForm(DepouillementSimulationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{type: DepouillementType, numero: string} $data */
            $data = $form->getData();
            $depouillementService->creerSimulation($gn, $data['type'], $data['numero'], $this->getUser());

            $this->addFlash('success', 'La simulation a été créée.');

            return $this->redirectToRoute('depouillement.index', ['gn' => $gn->getId()], 303);
        }

        return $this->render('depouillement/index.twig', [
            'gn' => $gn,
            'form' => $form,
            'simulations' => $depouillementService->listerSimulations($gn),
        ]);
    }

    /**
     * Supprime les simulations du GN (les dépouillements réels ne sont jamais touchés).
     */
    #[Route('/gn/{gn}/purge', name: 'depouillement.purge', requirements: ['gn' => '\d+'], methods: ['POST'])]
    public function purgeAction(Request $request, Gn $gn, DepouillementService $depouillementService): Response
    {
        if (!$this->isCsrfTokenValid('depouillement_purge_' . $gn->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('depouillement.index', ['gn' => $gn->getId()], 303);
        }

        $nombre = $depouillementService->purgerSimulations($gn);
        $this->addFlash('success', \sprintf('%d simulation(s) supprimée(s).', $nombre));

        return $this->redirectToRoute('depouillement.index', ['gn' => $gn->getId()], 303);
    }
}
