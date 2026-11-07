<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ApiClientRepository;
use App\Repository\GnRepository;
use App\Repository\GroupeRepository;
use App\Repository\RessourceRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API lecture seule pour applications tierces : personnages par groupe et ressources.
 */
#[Route('/api/admin', name: 'api.admin.')]
#[IsGranted('ROLE_API_READ')]
final class ApiAdminDonneesController extends AbstractApiGnController
{
    /**
     * Groupes d'un GN avec le nom de leurs personnages (aucune donnée joueur).
     */
    #[Route('/groupes/personnages', name: 'groupes.personnages', methods: ['GET'])]
    #[OA\Tag(name: 'Administration')]
    #[OA\Get(
        summary: 'Groupes et noms de leurs personnages',
        description: 'ROLE_API_READ ou ROLE_API_ADMIN. Même règle de GN que /api/admin/groupes (GN de l\'application, sinon prochain GN actif, à défaut le dernier ; ?gn=all réservé à ROLE_API_ADMIN). Pour chaque groupe du GN, la liste des personnages des participants (id et nom du personnage uniquement : aucune information sur les joueurs). Triée par numéro de groupe puis nom de personnage.',
    )]
    #[OA\Parameter(name: 'gn', in: 'query', description: 'Id d\'un GN, ou "all" (ROLE_API_ADMIN seulement).', schema: new OA\Schema(type: 'string', example: '10'))]
    #[OA\Response(response: 200, description: 'Groupes avec personnages', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'gn', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'nom', type: 'string'),
        ]),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'numero', type: 'integer'),
            new OA\Property(property: 'nom', type: 'string'),
            new OA\Property(property: 'personnages', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'nom', type: 'string'),
            ], type: 'object')),
        ], type: 'object')),
    ]))]
    #[OA\Response(response: 401, description: 'Jeton absent ou invalide')]
    #[OA\Response(response: 403, description: 'Rôle insuffisant, application révoquée, ou GN hors du périmètre de l\'application')]
    public function groupesPersonnages(Request $request, GroupeRepository $repo, ApiClientRepository $clients, GnRepository $gns): JsonResponse
    {
        $gn = $this->resolveGn($request, $clients, $gns);
        if ($gn instanceof JsonResponse) {
            return $gn;
        }
        $tousLesGn = 'all' === $request->query->get('gn');
        if (null === $gn && !$tousLesGn) {
            return $this->json(['gn' => null, 'items' => []]);
        }

        // Une seule requête : groupes du GN, participants de ce GN et leur personnage.
        $qb = $repo
            ->createQueryBuilder('g')
            ->select('g.id AS gid', 'g.numero', 'g.nom AS gnom', 'p.id AS pid', 'p.nom AS pnom')
            ->innerJoin('g.groupeGns', 'gg')
            ->leftJoin('gg.participants', 'pa')
            ->leftJoin('pa.personnage', 'p')
            ->orderBy('g.numero', 'ASC')
            ->addOrderBy('p.nom', 'ASC');
        if (null !== $gn) {
            $qb->andWhere('IDENTITY(gg.gn) = :gn')->setParameter('gn', $gn->getId());
        }

        /** @var array<int, array{id: int, numero: int, nom: string, personnages: list<array{id: int, nom: string}>}> $groupes */
        $groupes = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $gid = (int) $row['gid'];
            $groupes[$gid] ??= ['id' => $gid, 'numero' => (int) $row['numero'], 'nom' => (string) $row['gnom'], 'personnages' => []];
            if (null !== $row['pid']) {
                $groupes[$gid]['personnages'][] = ['id' => (int) $row['pid'], 'nom' => (string) $row['pnom']];
            }
        }

        return $this->json([
            'gn' => null === $gn ? null : ['id' => $gn->getId(), 'nom' => $gn->getLabel()],
            'items' => array_values($groupes),
        ]);
    }

    /**
     * Ressources commerciales (hors ingrédients) avec leur rareté.
     */
    #[Route('/ressources', name: 'ressources', methods: ['GET'])]
    #[OA\Tag(name: 'Administration')]
    #[OA\Get(
        summary: 'Liste des ressources commerciales',
        description: 'ROLE_API_READ ou ROLE_API_ADMIN. Ressources commerciales uniquement (les ingrédients ne sont pas inclus), avec leur rareté. Triée par nom.',
    )]
    #[OA\Response(response: 200, description: 'Ressources', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'label', type: 'string'),
            new OA\Property(property: 'rarete', type: 'object', properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'label', type: 'string'),
                new OA\Property(property: 'value', type: 'integer'),
            ]),
        ], type: 'object')),
    ]))]
    #[OA\Response(response: 401, description: 'Jeton absent ou invalide')]
    #[OA\Response(response: 403, description: 'Rôle insuffisant')]
    public function ressources(RessourceRepository $repo): JsonResponse
    {
        $items = [];
        foreach ($repo->findBy([], ['label' => 'ASC']) as $ressource) {
            $rarete = $ressource->getRarete();
            $items[] = [
                'id' => $ressource->getId(),
                'label' => $ressource->getLabel(),
                'rarete' => ['id' => $rarete->getId(), 'label' => $rarete->getLabel(), 'value' => $rarete->getValue()],
            ];
        }

        return $this->json(['items' => $items]);
    }
}
