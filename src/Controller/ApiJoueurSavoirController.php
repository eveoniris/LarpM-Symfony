<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Religion;
use App\Entity\Technologie;
use App\Enum\LogActionType;
use App\Enum\TriggerType;
use App\Repository\PersonnageRepository;
use App\Service\PersonnageService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Choix d'une technologie ou d'un descriptif de religion par le propriétaire du personnage.
 */
#[Route('/api/joueur/personnage/{id}', name: 'api.joueur.savoir.', requirements: ['id' => '\d+'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ApiJoueurSavoirController extends AbstractApiJoueurChoixController
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly PersonnageService $personnageService,
    ) {
        parent::__construct($entityManager);
    }

    #[OA\Get(
        summary: 'Technologies que je peux choisir',
        description: 'Réservé au propriétaire. Disponible seulement si un choix de technologie est en attente (403 sinon).',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Technologies disponibles', content: new OA\JsonContent(example: ['technologies' => [['id' => 7, 'label' => 'Forge']]])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas votre personnage, groupe verrouillé (groupe_verrouille) ou aucun choix en attente (aucun_choix)'),
            new OA\Response(response: 404, description: 'Personnage inconnu'),
        ],
    )]
    #[Route('/technologies', name: 'technologies', methods: ['GET'])]
    public function technologies(int $id, PersonnageRepository $personnages): JsonResponse
    {
        $personnage = $this->guardChoix($id, [TriggerType::TECHNOLOGIE], $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        $liste = [];
        foreach ($this->personnageService->getAvailableTechnologies($personnage) as $technologie) {
            $liste[] = ['id' => $technologie->getId(), 'label' => $technologie->getLabel()];
        }

        return $this->json(['technologies' => $liste]);
    }

    #[OA\Post(
        summary: 'Choisir une technologie',
        description: 'Réservé au propriétaire. Corps JSON `{"technologie_id": 7}`. Consomme le choix de technologie en attente.',
        tags: ['Joueur'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['technologie_id' => 7])),
        responses: [
            new OA\Response(response: 201, description: 'Technologie ajoutée', content: new OA\JsonContent(example: ['technologie_id' => 7])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas votre personnage, groupe verrouillé ou aucun choix en attente'),
            new OA\Response(response: 404, description: 'Personnage ou technologie inconnu'),
            new OA\Response(response: 422, description: 'Technologie déjà connue ou non disponible'),
        ],
    )]
    #[Route('/technologies', name: 'technologie.choisir', methods: ['POST'])]
    public function choisirTechnologie(int $id, Request $request, PersonnageRepository $personnages): JsonResponse
    {
        $personnage = $this->guardChoix($id, [TriggerType::TECHNOLOGIE], $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        $technologieId = $this->idDuCorps($request, 'technologie_id');
        if ($technologieId instanceof JsonResponse) {
            return $technologieId;
        }

        $technologie = $this->entityManager->find(Technologie::class, $technologieId);
        if (null === $technologie) {
            return $this->json(['error' => 'Technologie non trouvée'], 404);
        }
        if (!$this->personnageService->getAvailableTechnologies($personnage)->contains($technologie)) {
            return $this->json(['error' => 'Cette technologie n\'est pas disponible pour ce personnage'], 422);
        }

        $personnage->addTechnologie($technologie);
        $personnage->getTrigger(TriggerType::TECHNOLOGIE)?->consume($technologie->getLabel());
        $this->entityManager->flush();

        return $this->json(['technologie_id' => $technologie->getId()], 201);
    }

    #[OA\Get(
        summary: 'Descriptifs de religion que je peux choisir',
        description: 'Réservé au propriétaire. Religions non secrètes que le personnage ne connaît pas encore, disponibles si un choix de descriptif (prêtrise) est en attente.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Religions disponibles', content: new OA\JsonContent(example: ['religions' => [['id' => 3, 'label' => 'Mitra']]])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas votre personnage, groupe verrouillé ou aucun choix en attente'),
            new OA\Response(response: 404, description: 'Personnage inconnu'),
        ],
    )]
    #[Route('/religions/descriptifs', name: 'religions', methods: ['GET'])]
    public function religions(int $id, PersonnageRepository $personnages): JsonResponse
    {
        $personnage = $this->guardChoix($id, [TriggerType::PRETRISE_INITIE], $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        $liste = [];
        foreach ($this->personnageService->getAvailableDescriptionReligion($personnage) as $religion) {
            $liste[] = ['id' => $religion->getId(), 'label' => $religion->getLabel()];
        }

        return $this->json(['religions' => $liste]);
    }

    #[OA\Post(
        summary: 'Choisir un descriptif de religion',
        description: 'Réservé au propriétaire. Corps JSON `{"religion_id": 3}`. Consomme un choix de descriptif de prêtrise.',
        tags: ['Joueur'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['religion_id' => 3])),
        responses: [
            new OA\Response(response: 201, description: 'Religion ajoutée', content: new OA\JsonContent(example: ['religion_id' => 3])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas votre personnage, groupe verrouillé ou aucun choix en attente'),
            new OA\Response(response: 404, description: 'Personnage ou religion inconnu'),
            new OA\Response(response: 422, description: 'Religion déjà connue, secrète ou non disponible'),
        ],
    )]
    #[Route('/religions/descriptifs', name: 'religion.choisir', methods: ['POST'])]
    public function choisirReligion(int $id, Request $request, PersonnageRepository $personnages): JsonResponse
    {
        $personnage = $this->guardChoix($id, [TriggerType::PRETRISE_INITIE], $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        $religionId = $this->idDuCorps($request, 'religion_id');
        if ($religionId instanceof JsonResponse) {
            return $religionId;
        }

        $religion = $this->entityManager->find(Religion::class, $religionId);
        if (null === $religion) {
            return $this->json(['error' => 'Religion non trouvée'], 404);
        }
        if (!$this->personnageService->getAvailableDescriptionReligion($personnage)->contains($religion)) {
            return $this->json(['error' => 'Cette religion n\'est pas disponible pour ce personnage'], 422);
        }

        $personnage->addReligion($religion);
        $personnage->getTrigger(TriggerType::PRETRISE_INITIE)?->consume($religion->getLabel());
        $this->journaliser(LogActionType::ADD_RELIGION, ['personnage_id' => $personnage->getId(), 'religion_id' => $religion->getId()]);
        $this->entityManager->flush();

        return $this->json(['religion_id' => $religion->getId()], 201);
    }
}
