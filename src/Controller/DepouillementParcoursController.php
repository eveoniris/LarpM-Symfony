<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementEtape;
use App\Enum\DepouillementType;
use App\Enum\ActionFinOpus;
use App\Form\Depouillement\ContenuGroupeType;
use App\Form\Depouillement\ContenuJoueurType;
use App\Service\ContenuGroupe;
use App\Service\DepouillementGroupeService;
use App\Form\Depouillement\DepouillementEtiquetteType;
use App\Form\Depouillement\DepouillementRemarquesType;
use App\Service\CompteursEnveloppe;
use App\Service\DepouillementContenuService;
use App\Service\DepouillementService;
use App\Service\EtiquetteSaisie;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Parcours par étapes d'un dépouillement : reprise, étapes, fin de saisie.
 *
 * Fonctionnalité en cours de développement : réservée à ROLE_BETA_TEST (voir docs/plans/role-beta-test.md).
 */
// @mago-expect lint:cyclomatic-complexity
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
     * Une étape du parcours : identification, photos, contenu (étiquettes et enveloppe de joueur), remarques, récapitulatif.
     */
    // @mago-expect lint:excessive-parameter-list
    #[Route('/gn/{gn}/depouillement/{depouillement}/etape/{etape}', name: 'depouillement.etape', requirements: ['gn' => '\d+', 'depouillement' => '\d+'], methods: ['GET', 'POST'])]
    public function etapeAction(
        Request $request,
        Gn $gn,
        Depouillement $depouillement,
        DepouillementEtape $etape,
        DepouillementService $depouillementService,
        DepouillementContenuService $contenuService,
        DepouillementGroupeService $groupeService,
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
            'formEtiquette' => $this->formulaireEtiquette($depouillement, $etape),
            'avertissementGroupe' => $this->avertissementGroupe($depouillement, $depouillementService),
            ...$this->contenuJoueur($depouillement, $etape, $contenuService),
            ...$this->contenuGroupe($depouillement, $etape, $groupeService),
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
     * Variables de l'étape Contenu d'une enveloppe de joueur : contenu enregistré, formulaire (si modifiable) et contrôle des
     * six actions de fin d'Opus.
     *
     * @return array<string, mixed>
     */
    private function contenuJoueur(Depouillement $depouillement, DepouillementEtape $etape, DepouillementContenuService $contenuService): array
    {
        if (DepouillementEtape::CONTENU !== $etape || DepouillementType::JOUEUR !== $depouillement->getType()) {
            return [];
        }

        $contenu = $contenuService->lireContenuJoueur($depouillement);

        return [
            'contenuJoueur' => $contenu,
            'formContenuJoueur' => $depouillement->getStatut()->isEditable()
                ? $this->createForm(ContenuJoueurType::class, $contenu, [
                    'action' => $this->generateUrl('depouillement.contenu_joueur', ['gn' => $depouillement->getGn()->getId(), 'depouillement' => $depouillement->getId()]),
                ])
                : null,
            'surplusLibelles' => CompteursEnveloppe::nonNulles($contenu->surplus),
            'resultatsActions' => $contenuService->evaluerActions($contenu),
            'anomaliesContenu' => $contenuService->anomalies($contenu),
            'actionsFinOpus' => ActionFinOpus::cases(),
        ];
    }

    /**
     * Variables de l'étape Contenu d'une enveloppe de groupe : contenu enregistré, formulaire (si modifiable) et reliquat.
     *
     * @return array<string, mixed>
     */
    private function contenuGroupe(Depouillement $depouillement, DepouillementEtape $etape, DepouillementGroupeService $groupeService): array
    {
        if (DepouillementEtape::CONTENU !== $etape || DepouillementType::GROUPE !== $depouillement->getType()) {
            return [];
        }

        $contenu = $groupeService->lireContenuGroupe($depouillement);

        return [
            'contenuGroupe' => $contenu,
            'formContenuGroupe' => $depouillement->getStatut()->isEditable()
                ? $this->createForm(ContenuGroupeType::class, $contenu, [
                    'action' => $this->generateUrl('depouillement.contenu_groupe', ['gn' => $depouillement->getGn()->getId(), 'depouillement' => $depouillement->getId()]),
                ])
                : null,
            'reliquat' => $groupeService->reliquat($depouillement),
            'materielGroupe' => ContenuGroupe::MATERIEL,
        ];
    }

    /**
     * Formulaire d'ajout d'une étiquette, pour l'étape Contenu d'un dépouillement encore modifiable.
     */
    private function formulaireEtiquette(Depouillement $depouillement, DepouillementEtape $etape): ?FormInterface
    {
        if (DepouillementEtape::CONTENU !== $etape || !$depouillement->getStatut()->isEditable()) {
            return null;
        }

        return $this->createForm(DepouillementEtiquetteType::class, new EtiquetteSaisie(), [
            'action' => $this->generateUrl('depouillement.etiquette.add', ['gn' => $depouillement->getGn()->getId(), 'depouillement' => $depouillement->getId()]),
        ]);
    }

    /**
     * Nombre d'enveloppes de joueurs encore en cours, pour rappeler que celle du groupe se traite en dernier (0 = pas d'avertissement).
     */
    private function avertissementGroupe(Depouillement $depouillement, DepouillementService $depouillementService): int
    {
        if (DepouillementType::GROUPE !== $depouillement->getType() || !$depouillement->getStatut()->isEditable()) {
            return 0;
        }

        return $depouillementService->nombreEnveloppesJoueursEnCours($depouillement);
    }
}
