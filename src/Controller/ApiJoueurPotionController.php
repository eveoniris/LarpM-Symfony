<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\LogAction;
use App\Entity\Personnage;
use App\Entity\Potion;
use App\Entity\User;
use App\Enum\LogActionType;
use App\Enum\TriggerType;
use App\Repository\PersonnageRepository;
use App\Repository\PotionRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Choix d'une potion (trigger alchimie) par le propriétaire du personnage.
 */
#[Route('/api/joueur/personnage/{id}/potions/{niveau}', name: 'api.joueur.potion.', requirements: ['id' => '\d+', 'niveau' => '[1-4]'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ApiJoueurPotionController extends AbstractController
{
    private const array TRIGGERS = [
        1 => TriggerType::ALCHIMIE_APPRENTI,
        2 => TriggerType::ALCHIMIE_INITIE,
        3 => TriggerType::ALCHIMIE_EXPERT,
        4 => TriggerType::ALCHIMIE_MAITRE,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[OA\Get(
        summary: 'Potions que je peux choisir pour un niveau',
        description: 'Réservé au propriétaire. `niveau` de 1 (Apprenti) à 4 (Maître). Disponible seulement si un choix de potion de ce niveau est en attente (403 sinon).',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Potions disponibles', content: new OA\JsonContent(example: ['potions' => [['id' => 3, 'label' => 'Soin', 'numero' => '12']]])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas votre personnage, groupe verrouillé (code groupe_verrouille) ou aucun choix en attente (code aucun_choix)'),
            new OA\Response(response: 404, description: 'Personnage inconnu'),
        ],
    )]
    #[Route('', name: 'disponibles', methods: ['GET'])]
    public function disponibles(int $id, int $niveau, PersonnageRepository $personnages, PotionRepository $potions): JsonResponse
    {
        $personnage = $this->guard($id, $niveau, $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        $liste = [];
        foreach ($this->potionsInconnues($personnage, $niveau, $potions) as $potion) {
            $liste[] = ['id' => $potion->getId(), 'label' => $potion->getLabel(), 'numero' => $potion->getNumero()];
        }

        return $this->json(['potions' => $liste]);
    }

    #[OA\Post(
        summary: 'Choisir une potion',
        description: 'Réservé au propriétaire. Corps JSON `{"potion_id": 3}`. Consomme le choix en attente du niveau, comme sur le site.',
        tags: ['Joueur'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['potion_id' => 3])),
        responses: [
            new OA\Response(response: 201, description: 'Potion ajoutée', content: new OA\JsonContent(example: ['potion_id' => 3])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas votre personnage, groupe verrouillé ou aucun choix en attente'),
            new OA\Response(response: 404, description: 'Personnage ou potion inconnu'),
            new OA\Response(response: 422, description: 'Potion déjà connue, d\'un autre niveau ou non disponible'),
        ],
    )]
    #[Route('', name: 'choisir', methods: ['POST'])]
    public function choisir(int $id, int $niveau, Request $request, PersonnageRepository $personnages, PotionRepository $potions): JsonResponse
    {
        $personnage = $this->guard($id, $niveau, $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        $payload = json_decode($request->getContent(), true);
        $potionId = \is_array($payload) ? $payload['potion_id'] ?? null : null;
        if (!\is_int($potionId)) {
            return $this->json(['error' => 'potion_id (entier) requis'], 422);
        }

        $potion = $this->entityManager->find(Potion::class, $potionId);
        if (null === $potion) {
            return $this->json(['error' => 'Potion non trouvée'], 404);
        }
        if (!$this->potionsInconnues($personnage, $niveau, $potions)->contains($potion)) {
            return $this->json(['error' => 'Cette potion n\'est pas disponible pour ce personnage à ce niveau'], 422);
        }

        $personnage->addPotion($potion);
        $personnage->getTrigger(self::TRIGGERS[$niveau])?->consume(potion: $potion);

        $logAction = new LogAction();
        $logAction->setDate(new DateTime());
        $logAction->setUser($this->getUser());
        $logAction->setType(LogActionType::ADD_POTION);
        $logAction->setData(['personnage_id' => $personnage->getId(), 'potion' => $potion->getId()]);
        $this->entityManager->persist($logAction);
        $this->entityManager->flush();

        return $this->json(['potion_id' => $potion->getId()], 201);
    }

    /**
     * @return ArrayCollection<int, Potion>
     */
    private function potionsInconnues(Personnage $personnage, int $niveau, PotionRepository $potions): ArrayCollection
    {
        return new ArrayCollection(array_values(array_filter($potions->findByNiveau($niveau), static fn (Potion $potion): bool => !$personnage->isKnownPotion($potion))));
    }

    private function guard(int $id, int $niveau, PersonnageRepository $personnages): Personnage|JsonResponse
    {
        $personnage = $personnages->find($id);
        if (null === $personnage) {
            return $this->json(['error' => 'Personnage non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User || $personnage->getUser() !== $user) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }
        if (true === $personnage->getLastParticipantGnGroupe()?->getLock()) {
            return $this->json(['error' => 'Le groupe est verrouillé : il n\'est plus possible de modifier ce personnage.', 'code' => 'groupe_verrouille'], 403);
        }
        if (!$personnage->hasTrigger(self::TRIGGERS[$niveau])) {
            return $this->json(['error' => 'Aucun choix de potion en attente à ce niveau.', 'code' => 'aucun_choix'], 403);
        }

        return $personnage;
    }
}
