<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Exception\ContenuInvalideException;
use App\Exception\EnveloppeIntrouvableException;
use App\Form\Depouillement\RetourGroupeType;
use App\Security\Voter\DepouillementVoter;
use App\Entity\Participant;
use App\Service\DepouillementOrdresService;
use App\Service\DepouillementSuiviGroupeService;
use App\Service\DepouillementRetourGroupeService;
use App\Service\DepouillementService;
use App\Service\RetourGroupe;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Retour de jeu d'un groupe : le chef de groupe ou le joueur d'un titre ouvre le dossier de son groupe (le numéro est celui du
 * groupe, jamais choisi), puis le complète. Tous les titulaires saisissent le même retour tant qu'aucun tiers n'a repris le dossier.
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
// @mago-expect lint:cyclomatic-complexity
final class DepouillementRetourGroupeController extends AbstractDepouillementController
{
    #[Route('/gn/{gn}/mon-groupe', name: 'depouillement.mon_groupe', requirements: ['gn' => '\d+'], methods: ['GET', 'POST'])]
    public function monGroupeAction(Request $request, Gn $gn, DepouillementService $depouillementService): Response
    {
        $joueur = $this->utilisateur();
        $groupeGn = $depouillementService->groupeDuJoueur($gn, $joueur);
        $existant = null === $groupeGn ? null : $depouillementService->trouverReel($gn, \App\Enum\DepouillementType::GROUPE, (string) $groupeGn->getGroupe()->getNumero());

        if (!$existant instanceof Depouillement && null !== $groupeGn && $request->isMethod('POST') && $this->jetonValide($request, 'depouillement_mon_groupe_' . $gn->getId())) {
            try {
                $existant = $depouillementService->ouvrirPourGroupe($gn, $joueur);
            } catch (EnveloppeIntrouvableException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        if ($existant instanceof Depouillement) {
            $etape = $this->saisissable($existant) ? DepouillementEtape::pourType($existant->getType(), $this->declarant($existant))[0] : DepouillementEtape::RECAPITULATIF;

            return $this->redirectToRoute('depouillement.etape', $this->parametres($existant, $etape), 303);
        }

        return $this->render('depouillement/mon_groupe.twig', ['gn' => $gn, 'groupeGn' => $groupeGn]);
    }

    /**
     * Enregistre le retour du groupe : titres, ordres des phases et précisions.
     */
    #[Route('/gn/{gn}/depouillement/{depouillement}/retour-groupe', name: 'depouillement.retour_groupe', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['POST'])]
    public function retourGroupeAction(Request $request, Gn $gn, Depouillement $depouillement, DepouillementRetourGroupeService $retourService): Response
    {
        $this->verifier($gn, $depouillement, saisie: true);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::RETOUR_GROUPE), 303);

        $form = $this->createForm(RetourGroupeType::class, $retourService->lire($depouillement));
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->flashErreurs($form);

            return $retour;
        }

        /** @var RetourGroupe $donnees */
        $donnees = $form->getData();

        try {
            $retourService->enregistrer($depouillement, $donnees, $this->utilisateur());
        } catch (ContenuInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash('success', 'Le retour du groupe est enregistré.');

        return $retour;
    }

    /**
     * Le wargame valide, ou retire la validation, des ordres d'une phase du retour d'un groupe.
     */
    // @mago-expect lint:excessive-parameter-list
    #[Route(
        '/gn/{gn}/depouillement/{depouillement}/ordres/{phase}/{decision}',
        name: 'depouillement.ordres',
        requirements: ['gn' => '\d+', 'depouillement' => '\d+', 'phase' => '[1-3]', 'decision' => 'valider|rouvrir'],
        methods: ['POST'],
    )]
    public function ordresAction(Request $request, Gn $gn, Depouillement $depouillement, int $phase, string $decision, DepouillementOrdresService $ordresService): Response
    {
        $this->verifier($gn, $depouillement);
        $this->denyAccessUnlessGranted(DepouillementVoter::VALIDER_ORDRES, $depouillement);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::RETOUR_GROUPE), 303);

        if (!$this->jetonValide($request, 'depouillement_ordres_' . $depouillement->getId())) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $retour;
        }

        try {
            'valider' === $decision
                ? $ordresService->valider($depouillement, $phase, $this->utilisateur())
                : $ordresService->rouvrir($depouillement, $phase, $this->utilisateur());
        } catch (ContenuInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash('success', 'valider' === $decision ? \sprintf('Ordres de la phase %d validés.', $phase) : \sprintf('Validation des ordres de la phase %d retirée.', $phase));

        return $retour;
    }

    /**
     * Le staff clôture (pas de retour, n'a pas participé, avec motif) ou rouvre le retour d'un membre du groupe.
     */
    // @mago-expect lint:excessive-parameter-list
    #[Route(
        '/gn/{gn}/depouillement/{depouillement}/cloture/{participant}/{decision}',
        name: 'depouillement.cloture',
        requirements: ['gn' => '\d+', 'depouillement' => '\d+', 'participant' => '\d+', 'decision' => 'cloturer|rouvrir'],
        methods: ['POST'],
    )]
    public function clotureAction(Request $request, Gn $gn, Depouillement $depouillement, Participant $participant, string $decision, DepouillementSuiviGroupeService $suiviService): Response
    {
        $this->verifier($gn, $depouillement);
        $this->denyAccessUnlessGranted(DepouillementVoter::VALIDER, $depouillement);
        $retour = $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, DepouillementEtape::RETOUR_GROUPE), 303);

        if (!$this->jetonValide($request, 'depouillement_cloture_' . $depouillement->getId())) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $retour;
        }

        try {
            'cloturer' === $decision
                ? $suiviService->cloturer($depouillement, $participant, (string) $request->request->get('statut'), (string) $request->request->get('motif'), $this->utilisateur())
                : $suiviService->rouvrir($depouillement, $participant, $this->utilisateur());
        } catch (ContenuInvalideException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $retour;
        }

        $this->addFlash('success', 'cloturer' === $decision ? 'Retour clôturé.' : 'Clôture retirée.');

        return $retour;
    }
}
