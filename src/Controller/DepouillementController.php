<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Enum\DepouillementType;
use App\Exception\EnveloppeIntrouvableException;
use App\Form\Depouillement\DepouillementSimulationType;
use App\Service\DepouillementService;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Dépouillement des enveloppes de retour de jeu : page de travail d'un GN (liste, démarrage, suppression).
 * Le parcours par étapes est dans DepouillementParcoursController.
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
final class DepouillementController extends AbstractDepouillementController
{
    /**
     * Page de travail d'un GN : simulations en cours, démarrage d'un dépouillement et purge.
     * Si l'enveloppe est déjà ouverte, on propose de la reprendre au lieu d'en créer une seconde.
     */
    #[Route('/gn/{gn}/depouillement', name: 'depouillement.index', requirements: ['gn' => '\d+'], methods: ['GET', 'POST'])]
    public function indexAction(Request $request, Gn $gn, DepouillementService $depouillementService): Response
    {
        $form = $this->createForm(DepouillementSimulationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{type: DepouillementType, numero: string} $data */
            $data = $form->getData();

            try {
                return $this->demarrer($gn, $data['type'], $data['numero'], $depouillementService);
            } catch (EnveloppeIntrouvableException $exception) {
                $form->get('numero')->addError(new FormError($exception->getMessage()));
            }
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
    #[Route('/gn/{gn}/depouillement/purge', name: 'depouillement.purge', requirements: ['gn' => '\d+'], methods: ['POST'])]
    public function purgeAction(Request $request, Gn $gn, DepouillementService $depouillementService): Response
    {
        $redirection = $this->redirectToRoute('depouillement.index', ['gn' => $gn->getId()], 303);

        if (!$this->isCsrfTokenValid('depouillement_purge_' . $gn->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $redirection;
        }

        $this->addFlash('success', \sprintf('%d simulation(s) supprimée(s).', $depouillementService->purgerSimulations($gn)));

        return $redirection;
    }

    /**
     * Supprime une seule simulation. Un dépouillement réel ne peut jamais être supprimé ici.
     */
    #[Route('/gn/{gn}/depouillement/{depouillement}/supprimer', name: 'depouillement.delete', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function deleteAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementService $depouillementService): Response
    {
        $this->verifier($gn, $depouillement);
        $redirection = $this->redirectToRoute('depouillement.index', ['gn' => $gn->getId()], 303);

        if (!$this->isCsrfTokenValid('depouillement_delete_' . $depouillement->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $redirection;
        }

        if (!$depouillementService->supprimerSimulation($depouillement)) {
            $this->addFlash('error', 'Seule une simulation peut être supprimée.');

            return $redirection;
        }

        $this->addFlash('success', 'La simulation a été supprimée.');

        return $redirection;
    }

    /**
     * Ouvre l'enveloppe si elle l'est déjà, sinon en démarre une nouvelle.
     *
     * @throws EnveloppeIntrouvableException si le numéro ne correspond à rien dans ce GN
     */
    private function demarrer(Gn $gn, DepouillementType $type, string $numero, DepouillementService $depouillementService): Response
    {
        $existant = $depouillementService->trouverSimulation($gn, $type, $numero);

        if ($existant instanceof Depouillement) {
            return $this->ouvrir($existant);
        }

        $depouillement = $depouillementService->creerSimulation($gn, $type, $numero, $this->utilisateur());

        return $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::IDENTIFICATION), 303);
    }

    /**
     * Ouvre une enveloppe déjà ouverte : demande de reprise si un autre dépouilleur l'a en cours,
     * sinon le récapitulatif si elle n'est plus modifiable, sinon l'identification.
     */
    private function ouvrir(Depouillement $depouillement): Response
    {
        $autreDepouilleur = $depouillement->getDepouilleur()?->getId() !== $this->utilisateur()->getId();
        $modifiable = $depouillement->getStatut()->isEditable();

        if ($modifiable && $autreDepouilleur) {
            return $this->redirectToRoute('depouillement.reprise', ['gn' => $depouillement->getGn()->getId(), 'depouillement' => $depouillement->getId()], 303);
        }

        $etape = $modifiable ? DepouillementEtape::IDENTIFICATION : DepouillementEtape::RECAPITULATIF;

        return $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, $etape), 303);
    }
}
