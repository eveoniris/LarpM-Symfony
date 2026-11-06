<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\GroupeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API d'administration pour une application tierce (compagnon).
 * Réservée aux comptes ayant ROLE_API_ADMIN.
 */
#[Route('/api/admin', name: 'api.admin.')]
#[IsGranted('ROLE_API_ADMIN')]
final class ApiAdminController extends AbstractController
{
    private const int MAX_PAR_PAGE = 200;

    /**
     * Liste des groupes avec leur numéro. Filtre optionnel ?gn=<id> et pagination ?page=&limit=.
     */
    #[Route('/groupes', name: 'groupes', methods: ['GET'])]
    public function groupes(Request $request, GroupeRepository $repo): JsonResponse
    {
        $gnParam = $request->query->getInt('gn');
        $gnId = 0 !== $gnParam ? $gnParam : null;
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
