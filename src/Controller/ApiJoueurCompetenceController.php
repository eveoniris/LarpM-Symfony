<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Competence;
use App\Entity\Personnage;
use App\Entity\User;
use App\Repository\PersonnageRepository;
use App\Service\PersonnageService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Achat de compétences par le propriétaire du personnage (même service métier que le site).
 */
#[Route('/api/joueur/personnage/{id}', name: 'api.joueur.competence.', requirements: ['id' => '\d+'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ApiJoueurCompetenceController extends AbstractController
{
    public function __construct(
        private readonly PersonnageService $personnageService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[OA\Get(
        summary: 'Compétences que je peux acheter',
        description: 'Réservé au propriétaire du personnage. Chaque compétence disponible avec son coût en XP ; `achetable` est faux si les XP sont insuffisantes. `verrouille` est vrai quand le groupe du personnage est verrouillé : aucun achat n\'est alors possible.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Compétences disponibles', content: new OA\JsonContent(example: [
                'xp' => 12,
                'verrouille' => false,
                'competences' => [['id' => 8, 'famille' => 'Combat', 'niveau' => 'Initié', 'niveau_index' => 2, 'cout' => 10, 'achetable' => true]],
            ])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Ce personnage n\'est pas le vôtre'),
            new OA\Response(response: 404, description: 'Personnage inconnu'),
        ],
    )]
    #[Route('/competences/disponibles', name: 'disponibles', methods: ['GET'])]
    public function disponibles(int $id, PersonnageRepository $personnages): JsonResponse
    {
        $personnage = $this->ownedPersonnage($id, $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        $competences = [];
        foreach ($this->personnageService->getAvailableCompetences($personnage) as $competence) {
            $cout = $this->personnageService->getCompetenceCout($personnage, $competence);
            $competences[] = [
                'id' => $competence->getId(),
                'famille' => $competence->getCompetenceFamily()?->getLabel(),
                'niveau' => $competence->getLevel()?->getLabel(),
                'niveau_index' => $competence->getLevel()?->getIndex(),
                'cout' => $cout,
                'achetable' => $cout <= $personnage->getXp(),
            ];
        }

        return $this->json([
            'xp' => $personnage->getXp(),
            'verrouille' => $this->isLocked($personnage),
            'competences' => $competences,
        ]);
    }

    #[OA\Post(
        summary: 'Acheter une compétence',
        description: 'Réservé au propriétaire du personnage. Corps JSON `{"competence_id": 8}`. Débite les XP, applique les bonus et journalise l\'achat, comme sur le site. La compétence doit figurer dans les compétences disponibles. Refusé si le groupe est verrouillé.',
        tags: ['Joueur'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['competence_id' => 8])),
        responses: [
            new OA\Response(response: 201, description: 'Compétence acquise', content: new OA\JsonContent(example: ['competence_id' => 8, 'cout' => 10, 'xp' => 2])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Ce personnage n\'est pas le vôtre, ou son groupe est verrouillé (code groupe_verrouille)'),
            new OA\Response(response: 404, description: 'Personnage ou compétence inconnu'),
            new OA\Response(response: 422, description: 'Compétence non disponible ou XP insuffisants'),
        ],
    )]
    #[Route('/competences', name: 'acheter', methods: ['POST'])]
    public function acheter(int $id, Request $request, PersonnageRepository $personnages): JsonResponse
    {
        $personnage = $this->ownedPersonnage($id, $personnages);
        if ($personnage instanceof JsonResponse) {
            return $personnage;
        }

        if ($this->isLocked($personnage)) {
            return $this->json(['error' => 'Le groupe est verrouillé : il n\'est plus possible de modifier ce personnage.', 'code' => 'groupe_verrouille'], 403);
        }

        $payload = json_decode($request->getContent(), true);
        $competenceId = \is_array($payload) ? $payload['competence_id'] ?? null : null;
        if (!\is_int($competenceId)) {
            return $this->json(['error' => 'competence_id (entier) requis'], 422);
        }

        $competence = $this->entityManager->find(Competence::class, $competenceId);
        if (null === $competence) {
            return $this->json(['error' => 'Compétence non trouvée'], 404);
        }
        if (!$this->personnageService->getAvailableCompetences($personnage)->contains($competence)) {
            return $this->json(['error' => 'Cette compétence n\'est pas disponible pour ce personnage'], 422);
        }

        $cout = $this->personnageService->getCompetenceCout($personnage, $competence);
        $resultat = $this->personnageService->addCompetence($personnage, $competence, false);
        if ($resultat->hasErrors()) {
            return $this->json(['error' => $resultat->getErrorsAsString()], 422);
        }

        return $this->json(['competence_id' => $competence->getId(), 'cout' => $cout, 'xp' => $personnage->getXp()], 201);
    }

    private function ownedPersonnage(int $id, PersonnageRepository $personnages): Personnage|JsonResponse
    {
        $personnage = $personnages->find($id);
        if (null === $personnage) {
            return $this->json(['error' => 'Personnage non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User || $personnage->getUser() !== $user) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        return $personnage;
    }

    private function isLocked(Personnage $personnage): bool
    {
        return true === $personnage->getLastParticipantGnGroupe()?->getLock();
    }
}
