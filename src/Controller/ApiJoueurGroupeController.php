<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\GnRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/joueur', name: 'api.joueur.')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ApiJoueurGroupeController extends AbstractController
{
    #[OA\Get(
        summary: 'Mon groupe pour un GN',
        description: 'Groupe de l\'utilisateur connecté pour un GN (?gn=<id>, sinon le prochain GN actif, à défaut le dernier) avec le nom des personnages de ses membres. Aucune information sur les joueurs (noms réels, e-mails).',
        tags: ['Joueur'],
        parameters: [new OA\Parameter(name: 'gn', in: 'query', description: 'Id du GN. Absent : prochain GN actif, à défaut le dernier.', schema: new OA\Schema(type: 'integer', example: 10))],
        responses: [
            new OA\Response(response: 200, description: 'Groupe', content: new OA\JsonContent(example: [
                'gn' => ['id' => 10, 'label' => 'GN 2026'],
                'groupe' => ['id' => 85, 'nom' => 'Les Loups', 'numero' => 12],
                'est_responsable' => false,
                'membres' => [
                    ['personnage_id' => 1234, 'nom' => 'Kheldar', 'responsable' => true],
                    ['personnage_id' => 1301, 'nom' => 'Zora', 'responsable' => false],
                ],
            ])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 404, description: 'GN inconnu, ou vous n\'avez pas de groupe pour ce GN'),
        ],
    )]
    #[Route('/groupe', name: 'groupe', methods: ['GET'])]
    public function groupe(Request $request, GnRepository $gns): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $param = $request->query->get('gn');
        $gn = $gns->findFromFilter(\is_string($param) && '' !== $param ? $param : null);
        if (null === $gn) {
            return $this->json(['error' => 'GN non trouvé'], 404);
        }

        $participant = $user->getParticipant($gn);
        $groupeGn = $participant?->getGroupeGn();
        if (null === $participant || null === $groupeGn) {
            return $this->json(['error' => 'Aucun groupe pour ce GN'], 404);
        }

        $responsableId = $groupeGn->getResponsable()?->getId();
        $membres = [];
        foreach ($groupeGn->getParticipants() as $membre) {
            $personnage = $membre->getPersonnage();
            if (null === $personnage) {
                continue;
            }
            $membres[] = [
                'personnage_id' => $personnage->getId(),
                'nom' => $personnage->getNom(),
                'responsable' => null !== $responsableId && $membre->getId() === $responsableId,
            ];
        }
        usort($membres, static fn (array $a, array $b): int => strcmp((string) $a['nom'], (string) $b['nom']));

        $groupe = $groupeGn->getGroupe();

        return $this->json([
            'gn' => ['id' => $gn->getId(), 'label' => $gn->getLabel()],
            'groupe' => ['id' => $groupe->getId(), 'nom' => $groupe->getNom(), 'numero' => $groupe->getNumero()],
            'est_responsable' => null !== $responsableId && $participant->getId() === $responsableId,
            'membres' => $membres,
        ]);
    }
}
