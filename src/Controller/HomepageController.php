<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Gn;
use App\Entity\Territoire;
use App\Repository\TerritoireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Exception;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomepageController extends AbstractController
{
    /**
     * Fourni la liste des pays, leur geographie et leur description.
     */
    #[Route('/world/countries.json', name: 'world.countries.json')]
    public function countriesAction(): JsonResponse
    {
        return $this->getWorldTerritoireGeoData('countries');
    }

    private function getWorldTerritoireGeoData(string $type): JsonResponse
    {
        /** @var TerritoireRepository $repoTerritoire */
        $repoTerritoire = $this->entityManager->getRepository(Territoire::class);

        $territoires = match ($type) {
            'fiefs' => $repoTerritoire->findFiefs(),
            'countries' => $repoTerritoire->findRoot(),
            'regions' => $repoTerritoire->findRegions(),
            default => throw new Exception('Unknown territoire type : ' . $type),
        };

        $data = [];
        foreach ($territoires as $territoire) {
            $data[] = $this->addGeoData($territoire);
        }

        return new JsonResponse($data);
    }

    /** @return array<string, mixed> */
    private function addGeoData(Territoire $data): array
    {
        return [
            'id' => $data->getId(),
            'geom' => $data->getGeojson(),
            'name' => $data->getNom(),
            'color' => $data->getColor(),
            'description' => strip_tags((string) $data->getDescription()),
            'groupes' => array_values($data->getGroupesPj()),
            'desordre' => $data->getStatutIndex(),
            'langue' => $data->getLanguePrincipale(),
        ];
    }

    /**
     * Affiche les informations de dev.
     */
    #[Route('/dev', name: 'dev')]
    #[When(env: 'dev')]
    public function devAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('homepage/dev.twig');
    }

    /**
     * Fourni la liste des fiefs.
     */
    #[Route('/world/fiefs.json', name: 'world.fiefs.json')]
    public function fiefsAction(): JsonResponse
    {
        return $this->getWorldTerritoireGeoData('fiefs');
    }

    #[OA\Get(summary: 'Donnees globales du GN', description: 'Heritage. Role requis : SCENARISTE.', tags: ['Anciennes routes'])]
    #[Route('/api/{gn}/gdata', methods: ['GET'], name: 'api.gdata', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted('ROLE_SCENARISTE')]
    public function gdataAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity]
        Gn $gn,
    ): JsonResponse {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('IdGroup', 'IdGroup', 'integer');
        $rsm->addScalarResult('IdPj', 'IdPj', 'integer');
        $rsm->addScalarResult('NomGroupe', 'NomGroupe', 'string');
        $rsm->addScalarResult('Personnage', 'Personnage', 'string');
        $rsm->addScalarResult('ChefEmail', 'ChefEmail', 'string');
        $rsm->addScalarResult('ScenaristeEmail', 'ScenaristeEmail', 'string');

        /** @noinspection SqlNoDataSourceInspection */
        $query = $entityManager->createNativeQuery(<<<SQL
            SELECT g.numero         as IdGroup,
                   g.nom            as NomGroupe,
                   pr.id            as IdPj,
                   pr.nom           as Personnage,
                   chef.email       as ChefEmail,
                   scenariste.email as ScenaristeEmail
            FROM groupe_gn grgn
                     INNER JOIN participant p ON grgn.responsable_id = p.id
                     INNER JOIN personnage pr ON p.personnage_id = pr.id
                     INNER JOIN groupe g ON grgn.groupe_id = g.id
                     INNER JOIN `user` chef ON p.user_id = chef.id
                     INNER JOIN `user` scenariste ON g.scenariste_id = scenariste.id

            WHERE p.gn_id = :gnid
            ORDER BY g.numero;
            SQL, $rsm)->setParameter('gnid', $gn->getId());

        $results = $query->getResult();

        return new JsonResponse($results, empty($results) ? 204 : 200);
    }

    /**
     * Affiche les mentions légales.
     */
    #[Route('/legal', name: 'legal')]
    public function legalAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('homepage/legal.twig');
    }

    /**
     * Statistiques du projet.
     */
    #[Route('/metrics', name: 'dev.metrics')]
    #[IsGranted('ROLE_ADMIN')]
    public function metricsAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('homepage/metrics/report.html');
    }

    /**
     * Fourni la liste des régions.
     */
    #[Route('/world/regions.json', name: 'world.regions.json')]
    public function regionsAction(): JsonResponse
    {
        return $this->getWorldTerritoireGeoData('regions');
    }

    /**
     * Met à jour la geographie d'un pays.
     */
    #[Route('/world/countries/{territoire}/update', name: 'world.countries.update', requirements: ['territoire' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted('ROLE_TERRITOIRE')]
    public function updateCountryGeomAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity]
        Territoire $territoire,
    ): JsonResponse {
        $geom = (string) $request->request->get('geom');
        $color = (string) $request->request->get('color');

        // Le zonage doit être un objet GeoJSON valide
        $decoded = json_decode($geom, associative: true);
        if (!\is_array($decoded) || !\array_key_exists('type', $decoded)) {
            return new JsonResponse(['error' => 'Zonage invalide'], Response::HTTP_BAD_REQUEST);
        }

        $territoire->setGeojson($geom);
        if (1 === preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
            $territoire->setColor($color);
        }

        $entityManager->persist($territoire);
        $entityManager->flush();

        return new JsonResponse($this->addGeoData($territoire));
    }

    /**
     * Affiche une carte du monde.
     */
    #[Route('/world', name: 'world')]
    public function worldAction(): Response
    {
        return $this->render('world.twig');
    }
}
