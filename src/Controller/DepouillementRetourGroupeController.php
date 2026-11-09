<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Exception\ContenuInvalideException;
use App\Exception\EnveloppeIntrouvableException;
use App\Form\Depouillement\RetourGroupeType;
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
}
