<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Domaine;
use App\Entity\Sort;
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
 * Choix d'un sort ou d'un domaine de magie par le propriétaire du personnage.
 */
#[Route('/api/joueur/personnage/{id}', name: 'api.joueur.magie.', requirements: ['id' => '\d+'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ApiJoueurMagieController extends AbstractApiJoueurChoixController
{
    private const array SORTS = [
        1 => TriggerType::SORT_APPRENTI,
        2 => TriggerType::SORT_INITIE,
        3 => TriggerType::SORT_EXPERT,
        4 => TriggerType::SORT_MAITRE,
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly PersonnageService $personnageService,
    ) {
        parent::__construct($entityManager);
    }

    #[OA\Get(
        summary: 'Sorts que je peux choisir pour un niveau',
        description: 'Réservé au propriétaire. `niveau` de 1 (Apprenti) à 4 (Maître). Disponible seulement si un choix de sort de ce niveau est en attente (403 sinon).',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Sorts disponibles', content: new OA\JsonContent(example: ['sorts' => [['id' => 5, 'label' => 'Boule de feu', 'domaine' => 'Feu']]])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas votre personnage, groupe verrouillé (groupe_verrouille) ou aucun choix en attente (aucun_choix)'),
            new OA\Response(response: 404, description: 'Personnage inconnu'),
        ],
    )]
    #[Route('/sorts/{niveau}', name: 'sorts', requirements: ['niveau' => '[1-4]'], methods: ['GET'])]
    public function sorts(int $id, int $niveau, PersonnageRepository $personnages): JsonResponse
    {
        $personnage = $this->guardChoix($id, [self::SORTS[$niveau]], $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        $liste = [];
        foreach ($this->personnageService->getAvailableSorts($personnage, $niveau) as $sort) {
            $liste[] = ['id' => $sort->getId(), 'label' => $sort->getLabel(), 'domaine' => $sort->getDomaine()->getLabel()];
        }

        return $this->json(['sorts' => $liste]);
    }

    #[OA\Post(
        summary: 'Choisir un sort',
        description: 'Réservé au propriétaire. Corps JSON `{"sort_id": 5}`. Consomme le choix de sort en attente du niveau, comme sur le site.',
        tags: ['Joueur'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['sort_id' => 5])),
        responses: [
            new OA\Response(response: 201, description: 'Sort ajouté', content: new OA\JsonContent(example: ['sort_id' => 5])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas votre personnage, groupe verrouillé ou aucun choix en attente'),
            new OA\Response(response: 404, description: 'Personnage ou sort inconnu'),
            new OA\Response(response: 422, description: 'Sort déjà connu, d\'un autre niveau ou non disponible'),
        ],
    )]
    #[Route('/sorts/{niveau}', name: 'sort.choisir', requirements: ['niveau' => '[1-4]'], methods: ['POST'])]
    public function choisirSort(int $id, int $niveau, Request $request, PersonnageRepository $personnages): JsonResponse
    {
        $personnage = $this->guardChoix($id, [self::SORTS[$niveau]], $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        $sortId = $this->idDuCorps($request, 'sort_id');
        if ($sortId instanceof JsonResponse) {
            return $sortId;
        }

        $sort = $this->entityManager->find(Sort::class, $sortId);
        if (null === $sort) {
            return $this->json(['error' => 'Sort non trouvé'], 404);
        }
        if (!$this->personnageService->getAvailableSorts($personnage, $niveau)->contains($sort)) {
            return $this->json(['error' => 'Ce sort n\'est pas disponible pour ce personnage à ce niveau'], 422);
        }

        $personnage->addSort($sort);
        $personnage->getTrigger(self::SORTS[$niveau])?->consume(sort: $sort);
        $this->journaliser(LogActionType::ADD_SORT, ['personnage_id' => $personnage->getId(), 'niveau' => $niveau, 'sort_id' => $sort->getId()]);
        $this->entityManager->flush();

        return $this->json(['sort_id' => $sort->getId()], 201);
    }

    #[OA\Get(
        summary: 'Domaines de magie que je peux choisir',
        description: 'Réservé au propriétaire. Disponible si un choix de domaine de magie (niveau 1 ou Expert) est en attente.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Domaines disponibles', content: new OA\JsonContent(example: ['domaines' => [['id' => 2, 'label' => 'Feu']]])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas votre personnage, groupe verrouillé ou aucun choix en attente'),
            new OA\Response(response: 404, description: 'Personnage inconnu'),
        ],
    )]
    #[Route('/domaines', name: 'domaines', methods: ['GET'])]
    public function domaines(int $id, PersonnageRepository $personnages): JsonResponse
    {
        $personnage = $this->guardChoix($id, [TriggerType::DOMAINE_MAGIE, TriggerType::MAGIE_EXPERT], $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        $liste = [];
        foreach ($this->personnageService->getAvailableDomaines($personnage) as $domaine) {
            $liste[] = ['id' => $domaine->getId(), 'label' => $domaine->getLabel()];
        }

        return $this->json(['domaines' => $liste]);
    }

    #[OA\Post(
        summary: 'Choisir un domaine de magie',
        description: 'Réservé au propriétaire. Corps JSON `{"domaine_id": 2}`. Consomme le choix de domaine en attente.',
        tags: ['Joueur'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['domaine_id' => 2])),
        responses: [
            new OA\Response(response: 201, description: 'Domaine ajouté', content: new OA\JsonContent(example: ['domaine_id' => 2])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas votre personnage, groupe verrouillé ou aucun choix en attente'),
            new OA\Response(response: 404, description: 'Personnage ou domaine inconnu'),
            new OA\Response(response: 422, description: 'Domaine déjà connu ou non disponible'),
        ],
    )]
    #[Route('/domaines', name: 'domaine.choisir', methods: ['POST'])]
    public function choisirDomaine(int $id, Request $request, PersonnageRepository $personnages): JsonResponse
    {
        $personnage = $this->guardChoix($id, [TriggerType::DOMAINE_MAGIE, TriggerType::MAGIE_EXPERT], $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        $domaineId = $this->idDuCorps($request, 'domaine_id');
        if ($domaineId instanceof JsonResponse) {
            return $domaineId;
        }

        $domaine = $this->entityManager->find(Domaine::class, $domaineId);
        if (null === $domaine) {
            return $this->json(['error' => 'Domaine non trouvé'], 404);
        }
        if (!$this->personnageService->getAvailableDomaines($personnage)->contains($domaine)) {
            return $this->json(['error' => 'Ce domaine n\'est pas disponible pour ce personnage'], 422);
        }

        $personnage->addDomaine($domaine);
        $this->premierTrigger($personnage, TriggerType::DOMAINE_MAGIE, TriggerType::MAGIE_EXPERT)?->consume($domaine->getLabel());
        $this->journaliser(LogActionType::ADD_DOMAINE_MAGIE, ['personnage_id' => $personnage->getId(), 'domaine_id' => $domaine->getId()]);
        $this->entityManager->flush();

        return $this->json(['domaine_id' => $domaine->getId()], 201);
    }
}
