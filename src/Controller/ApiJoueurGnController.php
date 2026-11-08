<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\GnRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/joueur', name: 'api.joueur.')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ApiJoueurGnController extends AbstractController
{
    #[OA\Get(
        summary: 'Informations d\'un GN',
        description: 'Informations visibles de tout joueur connecté, les mêmes que sur la page du GN du site : dates, accueil des joueurs, adresse, description et conditions d\'inscription (textes en Markdown). `inscription` indique si l\'utilisateur y est inscrit.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'GN', content: new OA\JsonContent(example: [
                'id' => 10,
                'label' => 'GN 2026',
                'date_jeu' => 1432,
                'date_debut' => '2026-08-01',
                'date_fin' => '2026-08-05',
                'date_installation_joueur' => '2026-08-01T14:00:00+02:00',
                'adresse' => "Domaine de la Forêt\n12345 Lieu-dit",
                'description' => 'Un **grand** jeu...',
                'conditions_inscription' => 'Être majeur...',
                'actif' => true,
                'inscription' => true,
            ])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 404, description: 'GN inconnu'),
        ],
    )]
    #[Route('/gn/{gnId}', name: 'gn.detail', requirements: ['gnId' => '\d+'], methods: ['GET'])]
    public function detail(int $gnId, GnRepository $gns): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $gn = $gns->find($gnId);
        if (null === $gn) {
            return $this->json(['error' => 'GN non trouvé'], 404);
        }

        return $this->json([
            'id' => $gn->getId(),
            'label' => $gn->getLabel(),
            'date_jeu' => $gn->getDateJeu(),
            'date_debut' => $gn->getDateDebut()?->format('Y-m-d'),
            'date_fin' => $gn->getDateFin()?->format('Y-m-d'),
            'date_installation_joueur' => $gn->getDateInstallationJoueur()?->format(\DATE_ATOM),
            'adresse' => $gn->getAdresse(),
            'description' => $gn->getDescription(),
            'conditions_inscription' => $gn->getConditionsInscription(),
            'actif' => $gn->getActif(),
            'inscription' => null !== $user->getParticipant($gn),
        ]);
    }
}
