<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PersonnageTrigger;
use App\Entity\User;
use App\Enum\TriggerType;
use App\Repository\PersonnageRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * Choix en attente d'un personnage (triggers), pour son propriétaire.
 */
#[Route('/api/joueur/personnage/{id}', name: 'api.joueur.trigger.', requirements: ['id' => '\d+'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ApiJoueurTriggerController extends AbstractController
{
    #[OA\Get(
        summary: 'Choix en attente de mon personnage',
        description: 'Réservé au propriétaire du personnage. Liste les droits de choix non encore utilisés (langue, sort, technologie, potion, religion...). `verrouille` est vrai quand le groupe est verrouillé : aucun choix n\'est alors possible.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Choix en attente', content: new OA\JsonContent(example: [
                'verrouille' => false,
                'triggers' => [['id' => 12, 'tag' => 'LANGUE COURANTE', 'description' => 'permet de choisir une langue commune', 'gn_id' => 10, 'source' => 'ACHAT_COMPETENCE']],
            ])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Ce personnage n\'est pas le vôtre'),
            new OA\Response(response: 404, description: 'Personnage inconnu'),
        ],
    )]
    #[Route('/triggers', name: 'liste', methods: ['GET'])]
    public function liste(int $id, PersonnageRepository $personnages): JsonResponse
    {
        $personnage = $personnages->find($id);
        if (null === $personnage) {
            return $this->json(['error' => 'Personnage non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User || $personnage->getUser() !== $user) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $triggers = [];
        foreach ($personnage->getActiveTriggers() as $trigger) {
            $triggers[] = $this->normalize($trigger);
        }

        return $this->json([
            'verrouille' => true === $personnage->getLastParticipantGnGroupe()?->getLock(),
            'triggers' => $triggers,
        ]);
    }

    /**
     * @return array{id: int, tag: string|null, description: string|null, gn_id: int|null, source: string|null}
     */
    private function normalize(PersonnageTrigger $trigger): array
    {
        $tag = $trigger->getTag();
        $type = $tag instanceof TriggerType ? $tag : TriggerType::tryFrom((string) $tag);

        try {
            $description = $type?->getDescription();
        } catch (Throwable) {
            $description = null; // tag sans description définie
        }

        return [
            'id' => $trigger->getId(),
            'tag' => $type?->value ?? (null === $tag ? null : (string) $tag),
            'description' => $description,
            'gn_id' => $trigger->getGn()?->getId(),
            'source' => $trigger->getSource(),
        ];
    }
}
