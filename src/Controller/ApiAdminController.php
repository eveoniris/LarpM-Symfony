<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ApiClientRepository;
use App\Repository\GroupeRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API d'administration pour une application tierce (compagnon).
 * ROLE_API_ADMIN : tous les GN. ROLE_API_READ : lecture seule, limitée au GN de l'application.
 */
#[Route('/api/admin', name: 'api.admin.')]
#[IsGranted('ROLE_API_READ')]
final class ApiAdminController extends AbstractController
{
    private const int MAX_PAR_PAGE = 200;

    /**
     * Liste des groupes avec leur numéro. Filtre optionnel ?gn=<id> et pagination ?page=&limit=.
     */
    #[Route('/groupes', name: 'groupes', methods: ['GET'])]
    #[OA\Tag(name: 'Administration')]
    #[OA\Get(
        summary: 'Liste des groupes avec leur numéro',
        description: 'ROLE_API_ADMIN : tous les GN (filtre ?gn= optionnel). ROLE_API_READ : lecture seule, uniquement le GN de l\'application (?gn= absent ou égal à ce GN). Triée par numéro.',
    )]
    #[OA\Parameter(name: 'gn', in: 'query', description: 'Filtrer sur un GN (id)', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1))]
    #[OA\Parameter(name: 'limit', in: 'query', description: '200 maximum', schema: new OA\Schema(type: 'integer', default: 50, minimum: 1, maximum: 200))]
    #[OA\Response(response: 200, description: 'Page de groupes', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'page', type: 'integer'),
        new OA\Property(property: 'limit', type: 'integer'),
        new OA\Property(property: 'total', type: 'integer'),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'numero', type: 'integer'),
            new OA\Property(property: 'nom', type: 'string'),
            new OA\Property(property: 'code', type: 'string', nullable: true),
        ], type: 'object')),
    ]))]
    #[OA\Response(response: 401, description: 'Jeton absent ou invalide')]
    #[OA\Response(response: 403, description: 'Rôle insuffisant, application sans GN, ou GN demandé hors du périmètre de l\'application')]
    public function groupes(Request $request, GroupeRepository $repo, ApiClientRepository $clients): JsonResponse
    {
        $gnParam = $request->query->getInt('gn');
        $gnId = 0 !== $gnParam ? $gnParam : null;

        if (!$this->isGranted('ROLE_API_ADMIN')) {
            // Lecture seule : l'application ne voit que son GN.
            $client = $clients->findOneByUser($this->getUser());
            $scope = $client?->isActive() ? $client->getGn()?->getId() : null;
            if (null === $scope || null !== $gnId && $gnId !== $scope) {
                return $this->json(['error' => 'Accès refusé : GN hors du périmètre de l\'application'], 403);
            }
            $gnId = $scope;
        }
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(self::MAX_PAR_PAGE, max(1, $request->query->getInt('limit', 50)));

        $qb = $repo->createQueryBuilder('g')->orderBy('g.numero', 'ASC');

        if (null !== $gnId) {
            $qb
                ->innerJoin('g.groupeGns', 'gg')
                ->andWhere('IDENTITY(gg.gn) = :gn')
                ->setParameter('gn', $gnId);
        }

        $total = (int) (clone $qb)->select('COUNT(DISTINCT g.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        $groupes = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $items = [];
        foreach ($groupes as $groupe) {
            $items[] = [
                'id' => $groupe->getId(),
                'numero' => $groupe->getNumero(),
                'nom' => $groupe->getNom(),
                'code' => $groupe->getCode(),
            ];
        }

        return $this->json([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'items' => $items,
        ]);
    }
}
