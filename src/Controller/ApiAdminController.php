<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ApiClientRepository;
use App\Repository\GnRepository;
use App\Repository\GroupeRepository;
use OpenApi\Attributes as OA;
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
final class ApiAdminController extends AbstractApiGnController
{
    private const int MAX_PAR_PAGE = 200;

    /**
     * Liste des groupes avec leur numéro. GN par défaut : prochain GN actif, à défaut le dernier. ?gn=<id> ou ?gn=all, pagination ?page=&limit=.
     */
    #[Route('/groupes', name: 'groupes', methods: ['GET'])]
    #[OA\Tag(name: 'Administration')]
    #[OA\Get(
        summary: 'Liste des groupes avec leur numéro',
        description: 'ROLE_API_READ ou ROLE_API_ADMIN. Sans ?gn=, le GN est le prochain GN actif (à défaut le dernier GN) ; le GN utilisé est renvoyé dans le champ gn. ROLE_API_ADMIN : ?gn=<id> ou ?gn=all (tous les GN). ROLE_API_READ : lecture seule, uniquement le GN de l\'application (?gn= absent ou égal à ce GN). Triée par numéro.',
    )]
    #[OA\Parameter(
        name: 'gn',
        in: 'query',
        description: 'Id d\'un GN, ou "all" pour tous (ROLE_API_ADMIN seulement). Absent : prochain GN actif, à défaut le dernier.',
        schema: new OA\Schema(type: 'string', example: '12'),
    )]
    #[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1))]
    #[OA\Parameter(name: 'limit', in: 'query', description: '200 maximum', schema: new OA\Schema(type: 'integer', default: 50, minimum: 1, maximum: 200))]
    #[OA\Response(response: 200, description: 'Page de groupes', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'gn', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'nom', type: 'string'),
        ]),
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
    #[OA\Response(response: 403, description: 'Rôle insuffisant, application révoquée, ou GN demandé hors du périmètre de l\'application')]
    public function groupes(Request $request, GroupeRepository $repo, ApiClientRepository $clients, GnRepository $gns): JsonResponse
    {
        $gn = $this->resolveGn($request, $clients, $gns);
        if ($gn instanceof JsonResponse) {
            return $gn;
        }
        $tousLesGn = 'all' === $request->query->get('gn');
        $gnId = $gn?->getId();
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(self::MAX_PAR_PAGE, max(1, $request->query->getInt('limit', 50)));

        $qb = $repo->createQueryBuilder('g')->orderBy('g.numero', 'ASC');

        if (null !== $gnId) {
            $qb
                ->innerJoin('g.groupeGns', 'gg')
                ->andWhere('IDENTITY(gg.gn) = :gn')
                ->setParameter('gn', $gnId);
        } elseif (!$tousLesGn) {
            // Aucun GN en base : rien à lister (et surtout pas tous les groupes).
            $qb->andWhere('1 = 0');
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
            'gn' => null === $gn ? null : ['id' => $gn->getId(), 'nom' => $gn->getLabel()],
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'items' => $items,
        ]);
    }
}
