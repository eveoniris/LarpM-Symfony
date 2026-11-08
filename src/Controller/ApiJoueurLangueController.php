<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Langue;
use App\Entity\LogAction;
use App\Entity\Personnage;
use App\Entity\User;
use App\Enum\LangueSourceType;
use App\Enum\LogActionType;
use App\Enum\TriggerType;
use App\Repository\PersonnageRepository;
use App\Service\PersonnageService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Choix d'une langue (courante ou commune) avec le trigger LANGUE COURANTE, par le propriétaire du personnage.
 */
#[Route('/api/joueur/personnage/{id}/langues/{type}', name: 'api.joueur.langue.', requirements: ['id' => '\d+', 'type' => '[a-z0-9]+'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ApiJoueurLangueController extends AbstractController
{
    /** Diffusion des langues proposées selon le type, comme sur le site. */
    private const array DIFFUSIONS = ['courante' => 1, 'commune' => 2];

    public function __construct(
        private readonly PersonnageService $personnageService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[OA\Get(
        summary: 'Langues que je peux choisir',
        description: 'Réservé au propriétaire. `type` : `courante` ou `commune`. Disponible seulement si un choix de langue est en attente (403 sinon).',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Langues disponibles', content: new OA\JsonContent(example: [
                'langues' => [['id' => 4, 'label' => 'Aquilonien', 'description' => 'Langue du royaume d\'Aquilonie']],
            ])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas votre personnage, groupe verrouillé (code groupe_verrouille) ou aucun choix en attente (code aucun_choix)'),
            new OA\Response(response: 404, description: 'Personnage inconnu'),
        ],
    )]
    #[Route('', name: 'disponibles', methods: ['GET'])]
    public function disponibles(int $id, string $type, PersonnageRepository $personnages): JsonResponse
    {
        $personnage = $this->guard($id, $type, $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        $liste = [];
        foreach ($this->personnageService->getAvailableLangues($personnage, self::DIFFUSIONS[$type]) as $langue) {
            $liste[] = ['id' => $langue->getId(), 'label' => $langue->getLabel(), 'description' => $langue->getDescription()];
        }

        return $this->json(['langues' => $liste]);
    }

    #[OA\Post(
        summary: 'Choisir une langue',
        description: 'Réservé au propriétaire. Corps JSON `{"langue_id": 4}`. Consomme le choix de langue en attente, comme sur le site.',
        tags: ['Joueur'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['langue_id' => 4])),
        responses: [
            new OA\Response(response: 201, description: 'Langue ajoutée', content: new OA\JsonContent(example: ['langue_id' => 4])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas votre personnage, groupe verrouillé ou aucun choix en attente'),
            new OA\Response(response: 404, description: 'Personnage ou langue inconnu'),
            new OA\Response(response: 422, description: 'Langue déjà connue ou non disponible pour ce type'),
        ],
    )]
    #[Route('', name: 'choisir', methods: ['POST'])]
    public function choisir(int $id, string $type, Request $request, PersonnageRepository $personnages): JsonResponse
    {
        $personnage = $this->guard($id, $type, $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        $payload = json_decode($request->getContent(), true);
        $langueId = \is_array($payload) ? $payload['langue_id'] ?? null : null;
        if (!\is_int($langueId)) {
            return $this->json(['error' => 'langue_id (entier) requis'], 422);
        }

        $langue = $this->entityManager->find(Langue::class, $langueId);
        if (null === $langue) {
            return $this->json(['error' => 'Langue non trouvée'], 404);
        }
        if (!$this->personnageService->getAvailableLangues($personnage, self::DIFFUSIONS[$type])->contains($langue)) {
            return $this->json(['error' => 'Cette langue n\'est pas disponible pour ce personnage'], 422);
        }

        $personnageLangue = $personnage->addLangueSource($langue, LangueSourceType::LITTERATURE);
        if (null === $personnageLangue) {
            return $this->json(['error' => 'Le personnage connaît déjà cette langue'], 422);
        }

        $this->entityManager->persist($personnageLangue);
        $personnage->getTrigger(TriggerType::LANGUE_COURANTE)?->consume($langue->getLabel());

        $logAction = new LogAction();
        $logAction->setDate(new DateTime());
        $logAction->setUser($this->getUser());
        $logAction->setType(LogActionType::ADD_LANGUE);
        $logAction->setData(['personnage_id' => $personnage->getId(), 'langue' => $langue->getId()]);
        $this->entityManager->persist($logAction);
        $this->entityManager->flush();

        return $this->json(['langue_id' => $langue->getId()], 201);
    }

    private function guard(int $id, string $type, PersonnageRepository $personnages): Personnage|JsonResponse
    {
        // Le type est validé ici (et non dans la route) pour que toute route /api réponde 401 sans jeton.
        if (!isset(self::DIFFUSIONS[$type])) {
            return $this->json(['error' => 'Type de langue inconnu (courante ou commune)'], 404);
        }

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
        if (!$personnage->hasTrigger(TriggerType::LANGUE_COURANTE)) {
            return $this->json(['error' => 'Aucun choix de langue en attente.', 'code' => 'aucun_choix'], 403);
        }

        return $personnage;
    }
}
