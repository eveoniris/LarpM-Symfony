<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Participant;
use App\Enum\DepouillementType;
use App\Security\Voter\DepouillementVoter;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Exception\EnveloppeIntrouvableException;
use App\Service\DepouillementService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Entrée du joueur dans son propre retour de jeu : il ouvre (ou retrouve) le dossier réel de son personnage pour ce GN.
 * Le numéro n'est jamais choisi : c'est celui de son personnage. La suite est le parcours habituel du dépouillement,
 * dont les droits viennent de DepouillementVoter (le joueur saisit tant qu'aucun tiers n'a repris le dossier).
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
final class DepouillementJoueurController extends AbstractDepouillementController
{
    #[Route('/gn/{gn}/mon-retour', name: 'depouillement.mon_retour', requirements: ['gn' => '\d+'], methods: ['GET', 'POST'])]
    public function monRetourAction(Request $request, Gn $gn, DepouillementService $depouillementService): Response
    {
        $joueur = $this->utilisateur();
        $existant = $depouillementService->trouverPourJoueur($gn, $joueur);

        if (!$existant instanceof Depouillement && $request->isMethod('POST') && $this->jetonValide($request, 'depouillement_mon_retour_' . $gn->getId())) {
            try {
                $existant = $depouillementService->ouvrirPourJoueur($gn, $joueur);
            } catch (EnveloppeIntrouvableException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        if ($existant instanceof Depouillement) {
            // Le joueur entre par la première étape qui lui est ouverte (jamais par le contenu physique de l'enveloppe).
            $etape = $this->saisissable($existant) ? DepouillementEtape::pourType($existant->getType(), $this->declarant($existant))[0] : DepouillementEtape::RECAPITULATIF;

            return $this->redirectToRoute('depouillement.etape', $this->parametres($existant, $etape), 303);
        }

        return $this->render('depouillement/mon_retour.twig', [
            'gn' => $gn,
            'personnage' => $joueur->getParticipant($gn)?->getPersonnage(),
        ]);
    }

    /**
     * Lien depuis la fiche d'un participant vers son retour de jeu (staff) : ouvre le dossier réel s'il existe.
     */
    #[Route('/gn/{gn}/participant/{participant}/retour', name: 'depouillement.retour_participant', requirements: ['gn' => '\d+', 'participant' => '\d+'], methods: ['GET'])]
    public function retourParticipantAction(Gn $gn, Participant $participant, DepouillementService $depouillementService): Response
    {
        $this->denyAccessUnlessGranted(DepouillementVoter::VALIDER, $gn);
        if ($participant->getGn()?->getId() !== $gn->getId()) {
            throw $this->createNotFoundException();
        }

        $personnage = $participant->getPersonnage();
        $dossier = null === $personnage ? null : $depouillementService->trouverReel($gn, DepouillementType::JOUEUR, (string) $personnage->getId());
        if (!$dossier instanceof Depouillement) {
            $this->addFlash('warning', "Ce participant n'a pas encore de retour de jeu pour ce GN.");

            return $this->redirectToRoute('participant.detail', ['participant' => $participant->getId()], 303);
        }

        return $this->redirectToRoute('depouillement.etape', $this->parametres($dossier, DepouillementEtape::RECAPITULATIF), 303);
    }
}
