<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Exception\ContenuInvalideException;
use App\Form\Depouillement\ContenuGroupeType;
use App\Form\Depouillement\ContenuJoueurType;
use App\Service\ContenuGroupe;
use App\Service\ContenuJoueur;
use App\Service\DepouillementContenuService;
use App\Service\DepouillementGroupeService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contenu constaté dans une enveloppe de joueur (pièces jointes, apprentissages, six actions de fin d'Opus, surplus).
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
final class DepouillementContenuController extends AbstractDepouillementController
{
    #[Route('/gn/{gn}/depouillement/{depouillement}/contenu-joueur', name: 'depouillement.contenu_joueur', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function joueurAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementContenuService $contenuService): Response
    {
        $this->verifier($gn, $depouillement);
        $portion = $request->query->getString('portion', ContenuJoueurType::PORTION_TOUT);
        if (!\in_array($portion, [ContenuJoueurType::PORTION_TOUT, ContenuJoueurType::PORTION_CONTENU, ContenuJoueurType::PORTION_ACTIONS], strict: true)) {
            throw $this->createNotFoundException();
        }

        $etape = ContenuJoueurType::PORTION_ACTIONS === $portion ? DepouillementEtape::ACTIONS : DepouillementEtape::CONTENU;
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, $etape), 303);

        // Le formulaire d'une étape ne touche que ses champs : on part du contenu déjà enregistré pour ne pas effacer le reste.
        $form = $this->createForm(ContenuJoueurType::class, $contenuService->lireContenuJoueur($depouillement), ['portion' => $portion]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->flashErreurs($form);

            return $retour;
        }

        /** @var ContenuJoueur $contenu */
        $contenu = $form->getData();

        try {
            $contenuService->enregistrerContenuJoueur($depouillement, $contenu, $this->utilisateur());
        } catch (ContenuInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash('success', \sprintf('Contenu enregistré : %d action(s) de fin d\'Opus acceptée(s).', $contenuService->compterAcceptees($contenu)));

        return $retour;
    }

    #[Route('/gn/{gn}/depouillement/{depouillement}/contenu-groupe', name: 'depouillement.contenu_groupe', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function groupeAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementGroupeService $groupeService): Response
    {
        $this->verifier($gn, $depouillement);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::CONTENU), 303);

        $form = $this->createForm(ContenuGroupeType::class, new ContenuGroupe());
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->flashErreurs($form);

            return $retour;
        }

        try {
            $groupeService->enregistrerContenuGroupe($depouillement, $form->getData(), $this->utilisateur());
        } catch (ContenuInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash('success', 'Contenu de l\'enveloppe de groupe enregistré.');

        return $retour;
    }
}
