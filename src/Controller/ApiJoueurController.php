<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Personnage;
use App\Repository\GnRepository;
use App\Repository\PersonnageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/joueur', name: 'api.joueur.')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ApiJoueurController extends AbstractController
{
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $personnages = [];
        foreach ($user->getPersonnages() as $p) {
            $personnages[] = $this->summarizePersonnage($p);
        }

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'personnages' => $personnages,
        ]);
    }

    #[Route('/personnage/{id}', name: 'personnage', methods: ['GET'])]
    public function personnage(int $id, PersonnageRepository $repo): JsonResponse
    {
        $personnage = $repo->find($id);

        if (!$personnage) {
            return $this->json(['error' => 'Personnage non trouvé'], 404);
        }

        if ($personnage->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $competences = [];
        foreach ($personnage->getCompetences() as $c) {
            $competences[] = [
                'id' => $c->getId(),
                'famille' => $c->getCompetenceFamily()->getLabel(),
                'niveau' => $c->getLevel()?->getLabel(),
                'niveau_index' => $c->getLevel()?->getIndex(),
            ];
        }

        $langues = [];
        foreach ($personnage->getPersonnageLangues() as $pl) {
            $langue = $pl->getLangue();
            $langues[] = ['id' => $langue->getId(), 'label' => $langue->getLabel()];
        }

        $religions = [];
        foreach ($personnage->getPersonnagesReligions() as $pr) {
            $religion = $pr->getReligion();
            $religions[] = ['id' => $religion->getId(), 'label' => $religion->getLabel()];
        }

        return $this->json([
            ...$this->summarizePersonnage($personnage),
            'competences' => $competences,
            'langues' => $langues,
            'religions' => $religions,
        ]);
    }

    #[Route('/gn', name: 'gn', methods: ['GET'])]
    public function gn(GnRepository $repo): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $participantsByGn = [];
        foreach ($user->getParticipants() as $p) {
            $participantsByGn[$p->getGn()->getId()] = $p;
        }

        $gns = [];
        foreach ($repo->findBy([], ['date_debut' => 'DESC']) as $gn) {
            $gns[] = [
                'id' => $gn->getId(),
                'label' => $gn->getLabel(),
                'date_debut' => $gn->getDateDebut()?->format('Y-m-d'),
                'date_fin' => $gn->getDateFin()?->format('Y-m-d'),
                'actif' => $gn->getActif(),
                'inscription' => isset($participantsByGn[$gn->getId()]),
            ];
        }

        return $this->json($gns);
    }

    #[Route('/participation/{gnId}', name: 'participation', methods: ['GET'])]
    public function participation(int $gnId, GnRepository $gnRepo): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $gn = $gnRepo->find($gnId);
        if (!$gn) {
            return $this->json(['error' => 'GN non trouvé'], 404);
        }

        $participant = $user->getParticipant($gn);
        if (!$participant) {
            return $this->json(['error' => 'Aucune participation pour ce GN'], 404);
        }

        $billet = $participant->getBillet();
        $groupeGn = $participant->getGroupeGn();
        $personnage = $participant->getPersonnage();

        return $this->json([
            'id' => $participant->getId(),
            'subscription_date' => $participant->getSubscriptionDate()->format('Y-m-d'),
            'couchage' => $participant->getCouchage(),
            'billet' => $billet ? ['id' => $billet->getId(), 'label' => $billet->getLabel()] : null,
            'groupe' => $groupeGn?->getGroupe()
                ? [
                    'id' => $groupeGn->getGroupe()->getId(),
                    'nom' => $groupeGn->getGroupe()->getNom(),
                    'numero' => $groupeGn->getGroupe()->getNumero(),
                ] : null,
            'personnage' => $personnage ? $this->summarizePersonnage($personnage) : null,
        ]);
    }

    #[Route('/competences/{personnageId}', name: 'competences', methods: ['GET'])]
    public function competences(int $personnageId, PersonnageRepository $repo): JsonResponse
    {
        $personnage = $repo->find($personnageId);

        if (!$personnage) {
            return $this->json(['error' => 'Personnage non trouvé'], 404);
        }

        if ($personnage->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $competences = [];
        foreach ($personnage->getCompetences() as $c) {
            $competences[] = [
                'id' => $c->getId(),
                'famille_id' => $c->getCompetenceFamily()->getId(),
                'famille' => $c->getCompetenceFamily()->getLabel(),
                'niveau_id' => $c->getLevel()?->getId(),
                'niveau' => $c->getLevel()?->getLabel(),
                'niveau_index' => $c->getLevel()?->getIndex(),
            ];
        }

        usort($competences, static fn ($a, $b) => [$a['famille'], $a['niveau_index']] <=> [$b['famille'], $b['niveau_index']]);

        return $this->json($competences);
    }

    /** @return array<string, mixed> */
    private function summarizePersonnage(Personnage $p): array
    {
        return [
            'id' => $p->getId(),
            'nom' => $p->getNom(),
            'surnom' => $p->getSurnom(),
            'xp' => $p->getXp(),
            'renomme' => $p->getRenomme(),
            'richesse' => $p->getRichesse(),
            'heroisme' => $p->getHeroisme(),
            'vivant' => $p->getVivant(),
            'trombine_url' => $p->getTrombineUrl(),
            'classe' => [
                'id' => $p->getClasse()->getId(),
                'label_masculin' => $p->getClasse()->getLabelMasculin(),
                'label_feminin' => $p->getClasse()->getLabelFeminin(),
            ],
            'groupe' => $p->getGroupe()
                ? [
                    'id' => $p->getGroupe()->getId(),
                    'nom' => $p->getGroupe()->getNom(),
                    'numero' => $p->getGroupe()->getNumero(),
                ] : null,
        ];
    }
}
