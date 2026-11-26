<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\InterJeu;
use App\Entity\Personnage;
use App\Enum\InterJeuParticipantStatut;
use App\Enum\Role;
use App\Form\InterJeuPersonnagesImportType;
use App\Security\MultiRolesExpression;
use App\Service\InterJeuImportService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gestion de la liste des participants d'un inter-jeu : import, statuts, validation des propositions
 * des orgas externes. Réservée au rôle INTER_JEU (seul en écriture) et aux admins.
 */
#[IsGranted(new MultiRolesExpression(Role::INTER_JEU, Role::ADMIN))]
#[Route('/inter-jeu', name: 'inter.')]
final class InterParticipantController extends AbstractController
{
    /**
     * Import d'une liste d'identifiants (saisie ou CSV). Le CSV accepte une 2e colonne statut facultative
     * (inscrit, en attente, désisté) ; un personnage déjà présent voit son statut mis à jour.
     */
    #[Route('/{inter}/personnages/import', name: 'personnages.import', requirements: ['inter' => Requirement::DIGITS])]
    public function personnagesImportAction(
        Request $request,
        InterJeu $inter,
        InterJeuImportService $importService,
    ): RedirectResponse|Response {
        $form = $this->createForm(InterJeuPersonnagesImportType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('inter/personnages_import.html.twig', [
                'interJeu' => $inter,
                'form' => $form->createView(),
            ]);
        }

        /** @var ?UploadedFile $file */
        $file = $form->get('fichier')->getData();
        $bilan = $importService->importer($inter, (string) $form->get('ids')->getData(), $file?->getPathname(), $form->get('statut')->getData());
        $this->entityManager->flush();

        $this->addFlash('success', \sprintf('%d personnage(s) ajouté(s), %d mis à jour.', $bilan['ajoutes'], $bilan['misAJour']));
        $avertissements = array_filter([
            $bilan['propositionsTranchees'] > 0 ? \sprintf('%d proposition(s) d\'orga externe remplacée(s) par le statut importé.', $bilan['propositionsTranchees']) : null,
            [] !== $bilan['introuvables'] ? \sprintf('Identifiant(s) introuvable(s), ignoré(s) : %s.', implode(', ', $bilan['introuvables'])) : null,
            [] !== $bilan['statutsInconnus'] ? \sprintf('Statut non reconnu, statut par défaut appliqué pour : %s.', implode(', ', $bilan['statutsInconnus'])) : null,
        ]);
        foreach ($avertissements as $avertissement) {
            $this->addFlash('warning', $avertissement);
        }

        return $this->redirectToRoute('inter.personnages', ['inter' => $inter->getId()], 303);
    }

    #[Route('/{inter}/personnage/{personnage}/statut', name: 'personnage.statut', requirements: ['inter' => Requirement::DIGITS, 'personnage' => Requirement::DIGITS], methods: ['POST'])]
    public function personnageStatutAction(Request $request, InterJeu $inter, Personnage $personnage): RedirectResponse
    {
        $participation = $inter->getParticipation($personnage);
        $statut = InterJeuParticipantStatut::tryFrom((string) $request->request->get('statut'));

        if (!$this->isCsrfTokenValid('statut_personnage_' . $personnage->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, recommencez.');
        } elseif (null === $participation || null === $statut) {
            $this->addFlash('error', 'Participant ou statut inconnu.');
        } else {
            $participation->setStatut($statut)->annulerProposition();
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('%s : %s.', $personnage->getNom(), $statut->getLabel()));
        }

        return $this->redirectToRoute('inter.personnages', ['inter' => $inter->getId()], 303);
    }

    /**
     * Validation ou refus d'une proposition d'un orga externe.
     */
    #[Route('/{inter}/personnage/{personnage}/proposition', name: 'personnage.proposition', requirements: ['inter' => Requirement::DIGITS, 'personnage' => Requirement::DIGITS], methods: ['POST'])]
    public function propositionAction(Request $request, InterJeu $inter, Personnage $personnage): RedirectResponse
    {
        $participation = $inter->getParticipation($personnage);

        if (!$this->isCsrfTokenValid('proposition_' . $personnage->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, recommencez.');
        } elseif (null === $participation || !$participation->hasProposition()) {
            $this->addFlash('error', 'Aucune proposition en attente pour ce personnage.');
        } elseif ('valider' === $request->request->get('decision')) {
            $participation->validerProposition();
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('Proposition validée : %s, %s.', $personnage->getNom(), mb_strtolower($participation->getStatut()->getLabel())));
        } elseif ($participation->isAjoutPropose()) {
            // Ajout refusé : la ligne n'existait que par la proposition
            $inter->removePersonnage($personnage);
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('Ajout refusé : %s retiré de la liste.', $personnage->getNom()));
        } else {
            $participation->annulerProposition();
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('Proposition refusée pour %s.', $personnage->getNom()));
        }

        return $this->redirectToRoute('inter.personnages', ['inter' => $inter->getId()], 303);
    }
}
