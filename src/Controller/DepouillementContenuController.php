<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Exception\ContenuInvalideException;
use App\Form\Depouillement\ContenuGroupeType;
use App\Form\Depouillement\ContenuJoueurType;
use App\Form\Depouillement\DeclarationJoueurType;
use App\Service\ContenuGroupe;
use App\Service\ContenuJoueur;
use App\Service\DepouillementContenuService;
use App\Service\DepouillementDeclarationService;
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
        $this->verifier($gn, $depouillement, saisie: true);
        $portion = $request->query->getString('portion', ContenuJoueurType::PORTION_TOUT);
        if (!\in_array($portion, [ContenuJoueurType::PORTION_TOUT, ContenuJoueurType::PORTION_CONTENU, ContenuJoueurType::PORTION_ACTIONS, ContenuJoueurType::PORTION_DECLARATION], strict: true)) {
            throw $this->createNotFoundException();
        }

        // Seule la déclaration est ouverte au joueur : le contenu physique de l'enveloppe (pièces, surplus, actions) reste au staff.
        if (ContenuJoueurType::PORTION_DECLARATION !== $portion) {
            $this->verifierContenu($gn, $depouillement);
        }

        $etape = match ($portion) {
            ContenuJoueurType::PORTION_ACTIONS => DepouillementEtape::ACTIONS,
            ContenuJoueurType::PORTION_DECLARATION => DepouillementEtape::DECLARATION,
            default => DepouillementEtape::CONTENU,
        };
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
        if (\in_array($portion, [ContenuJoueurType::PORTION_TOUT, ContenuJoueurType::PORTION_ACTIONS], strict: true)) {
            // Les décisions sur les actions sont enregistrées : elles ne sont plus pré-sélectionnées d'après les ressources.
            $contenu->actionsDecidees = true;
        }

        try {
            $contenuService->enregistrerContenuJoueur($depouillement, $contenu, $this->utilisateur(), $this->declarant($depouillement));
        } catch (ContenuInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash(
            'success',
            ContenuJoueurType::PORTION_DECLARATION === $portion
                ? 'Votre déclaration est enregistrée.'
                : \sprintf('Contenu enregistré : %d action(s) de fin d\'Opus acceptée(s).', $contenuService->compterAcceptees($contenu)),
        );

        return $retour;
    }

    /**
     * Enregistre la partie « texte libre » de la déclaration du joueur (sortie du jeu, enseignements, constructions, renommée...).
     */
    #[Route('/gn/{gn}/depouillement/{depouillement}/declaration', name: 'depouillement.declaration', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function declarationAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementDeclarationService $declarationService): Response
    {
        $this->verifier($gn, $depouillement, saisie: true);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::DECLARATION), 303);

        $form = $this->createForm(DeclarationJoueurType::class, $declarationService->lire($depouillement));
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->flashErreurs($form);

            return $retour;
        }

        try {
            $declarationService->enregistrer($depouillement, $form->getData(), $this->utilisateur(), $this->declarant($depouillement));
        } catch (ContenuInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash('success', 'Votre déclaration est enregistrée.');

        return $retour;
    }

    #[Route('/gn/{gn}/depouillement/{depouillement}/contenu-groupe', name: 'depouillement.contenu_groupe', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function groupeAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementGroupeService $groupeService): Response
    {
        $this->verifierContenu($gn, $depouillement);
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
