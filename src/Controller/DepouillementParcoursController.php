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
use App\Service\ContenuJoueur;
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

        if (!$this->jetonValide($request, 'depouillement_reprendre_' . $depouillement->getId())) {
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

        $etapes = DepouillementEtape::pourType($depouillement->getType());
        if (!\in_array($etape, $etapes, strict: true)) {
            throw $this->createNotFoundException();
        }

        $modifiable = $depouillement->getStatut()->isEditable();
        $form = null;

        if (DepouillementEtape::REMARQUES === $etape && $modifiable) {
            $form = $this->createForm(DepouillementRemarquesType::class, ['remarques' => $depouillement->getRemarques()]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var array{remarques: ?string} $data */
                $data = $form->getData();
                $depouillementService->enregistrerRemarques($depouillement, $data['remarques'], $this->utilisateur());

                return $this->redirectToRoute('depouillement.etape', $this->parametres($depouillement, $etape->suivante($depouillement->getType()) ?? $etape), 303);
            }
        }

        return $this->render('depouillement/etape.twig', [
            'gn' => $gn,
            'depouillement' => $depouillement,
            'etape' => $etape,
            'etapes' => $etapes,
            'position' => $etape->position($depouillement->getType()),
            'precedente' => $etape->precedente($depouillement->getType()),
            'suivante' => $etape->suivante($depouillement->getType()),
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

        if (!$this->jetonValide($request, 'depouillement_terminer_' . $depouillement->getId())) {
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
        if (!in_array($etape, [DepouillementEtape::CONTENU, DepouillementEtape::ACTIONS, DepouillementEtape::RECAPITULATIF], strict: true) || DepouillementType::JOUEUR !== $depouillement->getType()) {
            return [];
        }

        $contenu = $contenuService->lireContenuJoueur($depouillement);

        return [
            'contenuJoueur' => $contenu,
            'formContenuJoueur' => $this->formulaireContenuJoueur($depouillement, $etape, $contenu),
            'surplusLibelles' => CompteursEnveloppe::nonNulles($contenu->surplus),
            'resultatsActions' => $contenuService->evaluerActions($contenu),
            'anomaliesContenu' => $contenuService->anomalies($contenu),
            'actionsFinOpus' => ActionFinOpus::cases(),
        ];
    }

    /**
     * Formulaire de l'étape Contenu ou Actions de fin d'Opus (chacune ne modifie que ses champs), si la saisie est modifiable.
     */
    private function formulaireContenuJoueur(Depouillement $depouillement, DepouillementEtape $etape, ContenuJoueur $contenu): ?FormInterface
    {
        $portion = match ($etape) {
            DepouillementEtape::CONTENU => ContenuJoueurType::PORTION_CONTENU,
            DepouillementEtape::ACTIONS => ContenuJoueurType::PORTION_ACTIONS,
            default => null,
        };
        if (null === $portion || !$depouillement->getStatut()->isEditable()) {
            return null;
        }

        return $this->createForm(ContenuJoueurType::class, $contenu, [
            'portion' => $portion,
            'action' => $this->generateUrl('depouillement.contenu_joueur', ['gn' => $depouillement->getGn()->getId(), 'depouillement' => $depouillement->getId(), 'portion' => $portion]),
        ]);
    }

    /**
     * Variables de l'étape Contenu d'une enveloppe de groupe : contenu enregistré, formulaire (si modifiable) et reliquat.
     *
     * @return array<string, mixed>
     */
    private function contenuGroupe(Depouillement $depouillement, DepouillementEtape $etape, DepouillementGroupeService $groupeService): array
    {
        if (!in_array($etape, [DepouillementEtape::CONTENU, DepouillementEtape::RECAPITULATIF], strict: true) || DepouillementType::GROUPE !== $depouillement->getType()) {
            return [];
        }

        $contenu = $groupeService->lireContenuGroupe($depouillement);

        return [
            'contenuGroupe' => $contenu,
            'formContenuGroupe' => DepouillementEtape::CONTENU === $etape && $depouillement->getStatut()->isEditable()
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
        if (DepouillementEtape::ETIQUETTES !== $etape || !$depouillement->getStatut()->isEditable()) {
            return null;
        }

        return $this->createForm(DepouillementEtiquetteType::class, new EtiquetteSaisie(), [
            'recherche_url' => $this->generateUrl('depouillement.etiquette.recherche', ['gn' => $depouillement->getGn()->getId()]),
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
