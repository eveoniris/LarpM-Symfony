<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Personnage;
use App\Repository\GnRepository;
use App\Repository\PersonnageRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/joueur', name: 'api.joueur.')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ApiJoueurController extends AbstractController
{
    #[OA\Get(
        summary: 'Mon profil et mes personnages',
        description: 'Utilisateur connecté (jeton obtenu par /api/login_check). Renvoie son identité et un résumé de chacun de ses personnages.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Profil', content: new OA\JsonContent(example: [
                'id' => 42,
                'username' => 'conan',
                'email' => 'conan@example.com',
                'personnages' => [[
                    'id' => 1234,
                    'nom' => 'Kheldar',
                    'surnom' => null,
                    'xp' => 120,
                    'renomme' => 3,
                    'richesse' => 0,
                    'heroisme' => 1,
                    'vivant' => true,
                    'trombine_url' => null,
                    'classe' => ['id' => 2, 'label_masculin' => 'Guerrier', 'label_feminin' => 'Guerrière'],
                    'groupe' => ['id' => 85, 'nom' => 'Les Loups', 'numero' => 12],
                ]],
            ])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
        ],
    )]
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

    #[OA\Get(
        summary: 'Détail d\'un de mes personnages',
        description: 'Réservé au propriétaire du personnage (403 sinon). Ajoute compétences, langues et religions au résumé.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Personnage', content: new OA\JsonContent(example: [
                'id' => 1234,
                'nom' => 'Kheldar',
                'surnom' => null,
                'xp' => 120,
                'renomme' => 3,
                'richesse' => 0,
                'heroisme' => 1,
                'vivant' => true,
                'trombine_url' => null,
                'classe' => ['id' => 2, 'label_masculin' => 'Guerrier', 'label_feminin' => 'Guerrière'],
                'groupe' => ['id' => 85, 'nom' => 'Les Loups', 'numero' => 12],
                'competences' => [['id' => 7, 'famille' => 'Combat', 'niveau' => 'Apprenti', 'niveau_index' => 1]],
                'langues' => [['id' => 3, 'label' => 'Commun']],
                'religions' => [['id' => 2, 'label' => 'Mitra']],
            ])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Ce personnage n\'est pas le vôtre'),
            new OA\Response(response: 404, description: 'Personnage inconnu'),
        ],
    )]
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

    #[OA\Get(
        summary: 'Liste des GN et mon inscription',
        description: 'Tous les GN, du plus récent au plus ancien, avec un indicateur d\'inscription de l\'utilisateur connecté.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'GN', content: new OA\JsonContent(example: [
                ['id' => 10, 'label' => 'GN 2026', 'date_debut' => '2026-08-01', 'date_fin' => '2026-08-05', 'actif' => true, 'inscription' => true],
            ])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
        ],
    )]
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

    #[OA\Get(
        summary: 'Ma participation à un GN',
        description: 'Billet, couchage, groupe du GN et personnage joué par l\'utilisateur connecté pour le GN demandé.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Participation', content: new OA\JsonContent(example: [
                'id' => 555,
                'subscription_date' => '2026-03-14',
                'couchage' => 'Tente',
                'billet' => ['id' => 4, 'label' => 'Joueur'],
                'groupe' => ['id' => 85, 'nom' => 'Les Loups', 'numero' => 12],
                'personnage' => ['id' => 1234, 'nom' => 'Kheldar', 'xp' => 120, 'vivant' => true],
            ])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 404, description: 'GN inconnu ou aucune participation'),
        ],
    )]
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

    #[OA\Get(
        summary: 'Compétences d\'un de mes personnages',
        description: 'Réservé au propriétaire du personnage (403 sinon). Triées par famille puis niveau.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Compétences', content: new OA\JsonContent(example: [
                ['id' => 7, 'famille_id' => 1, 'famille' => 'Combat', 'niveau_id' => 1, 'niveau' => 'Apprenti', 'niveau_index' => 1],
            ])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Ce personnage n\'est pas le vôtre'),
            new OA\Response(response: 404, description: 'Personnage inconnu'),
        ],
    )]
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
