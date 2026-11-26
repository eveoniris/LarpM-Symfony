<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\InterJeu;
use App\Entity\InterJeuOrga;
use App\Entity\Personnage;
use App\Enum\InterJeuParticipantStatut;
use App\Repository\PersonnageRepository;
use App\Security\Voter\InterJeuVoter;
use App\Service\InterJeuStatistiqueService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Consultation d'un inter-jeu, partagée entre le rôle INTER_JEU et les orgas externes dont l'accès est validé :
 * statistiques (petit matériel), export CSV, liste des participants. Les orgas externes proposent des
 * changements de liste, que le rôle INTER_JEU valide depuis la page de gestion des participants.
 *
 * Exposition limitée aux personnages : identifiant, nom, classe, statut, niveaux de compétence.
 * Aucune donnée de compte utilisateur, d'état civil ni élément secret.
 */
#[IsGranted('ROLE_USER')]
#[Route('/inter-jeu', name: 'inter.')]
final class InterConsultationController extends AbstractController
{
    #[Route('/mes-inter-jeux', name: 'orga.list')]
    public function mesInterJeuxAction(): Response
    {
        $acces = $this->entityManager->getRepository(InterJeuOrga::class)->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']);

        return $this->render('inter/orga_list.html.twig', [
            'acces' => $acces,
        ]);
    }

    #[Route('/{inter}/statistiques', name: 'statistiques', requirements: ['inter' => Requirement::DIGITS])]
    #[IsGranted(InterJeuVoter::VIEW, subject: 'inter')]
    public function statistiquesAction(InterJeu $inter, InterJeuStatistiqueService $statistiqueService): Response
    {
        return $this->render('inter/statistiques.html.twig', [
            'interJeu' => $inter,
            'stats' => $statistiqueService->getStatistiques($inter),
            'niveaux' => InterJeuStatistiqueService::NIVEAUX,
            'gestionnaire' => $this->isGranted(InterJeuVoter::MANAGE, $inter),
        ]);
    }

    #[Route('/{inter}/statistiques.csv', name: 'statistiques.csv', requirements: ['inter' => Requirement::DIGITS])]
    #[IsGranted(InterJeuVoter::VIEW, subject: 'inter')]
    public function statistiquesCsvAction(InterJeu $inter, InterJeuStatistiqueService $statistiqueService): StreamedResponse
    {
        $lignes = $statistiqueService->getLignesCsv($inter);

        $response = new StreamedResponse(static function () use ($lignes): void {
            $sortie = fopen('php://output', 'w');
            if (!$sortie) {
                return;
            }
            fwrite($sortie, "\xEF\xBB\xBF"); // BOM : accents lisibles dans Excel
            foreach ($lignes as $ligne) {
                fputcsv($sortie, $ligne, ';', '"', '');
            }
            fclose($sortie);
        });

        $nom = preg_replace('/[^A-Za-z0-9_-]+/', '-', $inter->getNom()) ?: 'inter-jeu';
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', \sprintf('attachment; filename="competences-%s.csv"', trim($nom, '-')));

        return $response;
    }

    #[Route('/{inter}/consultation', name: 'consultation', requirements: ['inter' => Requirement::DIGITS])]
    #[IsGranted(InterJeuVoter::VIEW, subject: 'inter')]
    public function consultationAction(InterJeu $inter): Response
    {
        return $this->render('inter/consultation.html.twig', [
            'interJeu' => $inter,
            'statuts' => InterJeuParticipantStatut::cases(),
            'peutProposer' => $this->isGranted(InterJeuVoter::PROPOSE, $inter),
            'gestionnaire' => $this->isGranted(InterJeuVoter::MANAGE, $inter),
        ]);
    }

    /**
     * Proposition d'ajout : le personnage entre « en attente », la proposition « inscrit » attend la validation.
     */
    #[Route('/{inter}/proposition/ajout', name: 'proposition.ajout', requirements: ['inter' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(InterJeuVoter::PROPOSE, subject: 'inter')]
    public function propositionAjoutAction(Request $request, InterJeu $inter, PersonnageRepository $personnageRepository): RedirectResponse
    {
        $saisie = trim((string) $request->request->get('personnage'));
        $personnage = ctype_digit($saisie) ? $personnageRepository->find((int) $saisie) : null;

        if (!$this->isCsrfTokenValid('proposition_ajout_' . $inter->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, recommencez.');
        } elseif (!$personnage instanceof Personnage) {
            $this->addFlash('error', 'Aucun personnage avec cet identifiant.');
        } elseif (null !== $inter->getParticipation($personnage)) {
            $this->addFlash('warning', 'Ce personnage est déjà dans la liste : proposez plutôt un changement de statut.');
        } else {
            // Le nom du personnage n'est montré à l'orga qu'après validation (pas de lecture de noms par identifiant)
            $inter->addPersonnage($personnage, InterJeuParticipantStatut::EN_ATTENTE)->proposerAjout($this->getUser());
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('Ajout du personnage #%d proposé, en attente de validation par la gestion des inter-jeux.', $personnage->getId()));
        }

        return $this->redirectToRoute('inter.consultation', ['inter' => $inter->getId()], 303);
    }

    #[Route('/{inter}/proposition/{personnage}/statut', name: 'proposition.statut', requirements: ['inter' => Requirement::DIGITS, 'personnage' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(InterJeuVoter::PROPOSE, subject: 'inter')]
    public function propositionStatutAction(Request $request, InterJeu $inter, Personnage $personnage): RedirectResponse
    {
        $participation = $inter->getParticipation($personnage);
        $statut = InterJeuParticipantStatut::tryFrom((string) $request->request->get('statut'));

        if (!$this->isCsrfTokenValid('proposition_statut_' . $inter->getId() . '_' . $personnage->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, recommencez.');
        } elseif (null === $participation || null === $statut) {
            $this->addFlash('error', 'Participant ou statut inconnu.');
        } elseif ($participation->isAjoutPropose()) {
            // Ajout encore non validé : l'orga peut seulement le retirer (statut « en attente » choisi)
            if (InterJeuParticipantStatut::EN_ATTENTE === $statut) {
                $inter->removePersonnage($personnage);
                $this->entityManager->flush();
                $this->addFlash('success', 'Proposition d\'ajout retirée.');
            } else {
                $this->addFlash('warning', 'Cet ajout attend encore la validation de la gestion des inter-jeux.');
            }
        } elseif ($participation->getStatut() === $statut) {
            $participation->annulerProposition();
            $this->entityManager->flush();
            $this->addFlash('success', 'Proposition retirée.');
        } else {
            $participation->proposer($statut, $this->getUser());
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('Changement proposé (%s), en attente de validation.', $statut->getLabel()));
        }

        return $this->redirectToRoute('inter.consultation', ['inter' => $inter->getId()], 303);
    }
}
