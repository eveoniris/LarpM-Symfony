<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Enum\DepouillementType;
use App\Form\Depouillement\DepouillementRemarquesType;
use App\Service\DepouillementService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Parcours par étapes d'un dépouillement : reprise, étapes, fin de saisie.
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
final class DepouillementParcoursController extends AbstractDepouillementController
{
    /**
     * Demande de reprise : un autre dépouilleur a cette enveloppe en cours.
     */
    #[Route('/gn/{gn}/depouillement/{depouillement}/reprise', name: 'depouillement.reprise', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['GET'])]
    public function repriseAction(Gn $gn, Depouillement $depouillement): Response
    {
        $this->verifier($gn, $depouillement);

        return $this->render('depouillement/reprise.twig', [
            'gn' => $gn,
            'depouillement' => $depouillement,
        ]);
    }

    /**
     * Le dépouilleur courant prend la suite (l'ancien reste dans l'historique).
     */
    #[Route('/gn/{gn}/depouillement/{depouillement}/reprendre', name: 'depouillement.reprendre', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function reprendreAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementService $depouillementService): Response
    {
        $this->verifier($gn, $depouillement);
        $liste = $this->redirectToRoute('depouillement.index', ['gn' => $gn->getId()], 303);

        if (!$this->isCsrfTokenValid('depouillement_reprendre_' . $depouillement->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $liste;
        }

        if (!$depouillementService->reprendre($depouillement, $this->utilisateur())) {
            $this->addFlash('error', 'Ce dépouillement n\'est plus en cours de saisie.');

            return $liste;
        }

        return $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::IDENTIFICATION), 303);
    }

    /**
     * Une étape du parcours. Les étapes photos et contenu sont des emplacements pour l'instant.
     */
    #[Route('/gn/{gn}/depouillement/{depouillement}/etape/{etape}', name: 'depouillement.etape', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['GET', 'POST'])]
    public function etapeAction(
        Request $request,
        Gn $gn,
        Depouillement $depouillement,
        DepouillementEtape $etape,
        DepouillementService $depouillementService,
    ): Response {
        $this->verifier($gn, $depouillement);

        $modifiable = $depouillement->getStatut()->isEditable();
        $form = null;

        if (DepouillementEtape::REMARQUES === $etape && $modifiable) {
            $form = $this->createForm(DepouillementRemarquesType::class, ['remarques' => $depouillement->getRemarques()]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var array{remarques: ?string} $data */
                $data = $form->getData();
                $depouillementService->enregistrerRemarques($depouillement, $data['remarques'], $this->utilisateur());

                return $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, $etape->suivante() ?? $etape), 303);
            }
        }

        return $this->render('depouillement/etape.twig', [
            'gn' => $gn,
            'depouillement' => $depouillement,
            'etape' => $etape,
            'etapes' => DepouillementEtape::cases(),
            'modifiable' => $modifiable,
            'form' => $form,
            'avertissementGroupe' => $this->avertissementGroupe($depouillement, $depouillementService),
        ]);
    }

    /**
     * Fin de saisie : le dépouillement passe en "à valider".
     */
    #[Route('/gn/{gn}/depouillement/{depouillement}/terminer', name: 'depouillement.terminer', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function terminerAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementService $depouillementService): Response
    {
        $this->verifier($gn, $depouillement);
        $redirection = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::RECAPITULATIF), 303);

        if (!$this->isCsrfTokenValid('depouillement_terminer_' . $depouillement->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $redirection;
        }

        if (!$depouillementService->terminerSaisie($depouillement, $this->utilisateur())) {
            $this->addFlash('error', 'Ce dépouillement n\'est plus en cours de saisie.');

            return $redirection;
        }

        $this->addFlash('success', 'Dépouillement terminé : il est en attente de validation.');

        return $redirection;
    }

    /**
     * Nombre d'enveloppes de joueurs encore en cours, pour rappeler que celle du groupe se traite en dernier (0 = pas d'avertissement).
     */
    private function avertissementGroupe(Depouillement $depouillement, DepouillementService $depouillementService): int
    {
        if (DepouillementType::GROUPE !== $depouillement->getType() || !$depouillement->getStatut()->isEditable()) {
            return 0;
        }

        return $depouillementService->nombreEnveloppesJoueursEnCours($depouillement->getGn(), $depouillement->isSimulation());
    }
}
