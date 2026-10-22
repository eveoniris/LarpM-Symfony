<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Exception\ContenuInvalideException;
use App\Form\Depouillement\ContenuJoueurType;
use App\Service\ContenuJoueur;
use App\Service\DepouillementContenuService;
use Symfony\Component\Form\FormError;
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
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::CONTENU), 303);

        $form = $this->createForm(ContenuJoueurType::class, new ContenuJoueur());
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            foreach ($form->getErrors(true) as $erreur) {
                $this->addFlash('error', $erreur instanceof FormError ? $erreur->getMessage() : 'Saisie invalide.');
            }

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
}
