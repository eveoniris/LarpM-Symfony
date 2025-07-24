<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\Genre;
use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\Langue;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\Religion;
use App\Entity\User;
use App\Enum\Role;
use App\Manager\RandomColor;
use App\Repository\ReligionRepository;
use App\Security\MultiRolesExpression;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use JsonException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new MultiRolesExpression(Role::ADMIN, Role::SCENARISTE, Role::WARGAME, Role::ORGA))]
class StatistiqueController extends AbstractController
{
    #[Route('/stats/alchimieHerboriste/{gn}/csv', name: 'stats.alchimieHerboriste.gn.csv', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function alchimieHerboristeCsvStatsGnAction(#[MapEntity] Gn $gn): StreamedResponse
    {
        return $this->sendCsv(
            title: 'eveoniris_alchimieHerboriste_gn_' . $gn->getId() . '_' . date('Ymd'),
            query: $this->statsService->getAlchimieHerboristeGn($gn),
            header: ['personnageId', 'nom', 'competence', 'niveauMax'],
        );
    }

    #[Route('/stats/alchimieHerboriste/{gn}', name: 'stats.alchimieHerboriste.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function alchimieHerboristeStatsGnAction(#[MapEntity] Gn $gn): Response
    {
        return $this->render('statistique/alchimieHerboriste.twig', [
            'alchimieHerboristes' => $this->statsService->getAlchimieHerboristeGn($gn)->getResult(),
            'gn' => $gn,
        ]);
    }

    #[Route('/api/alchimieHerboriste/{gn}', name: 'api.alchimieHerboriste.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/alchimieHerboriste/{gn}/json', name: 'stats.alchimieHerboriste.gn.json', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function alchimieHerboristeStatsGnApiAction(#[MapEntity] Gn $gn): JsonResponse
    {
        return new JsonResponse($this->statsService->getAlchimieHerboristeGn($gn)->getResult());
    }

    #[Route('/api/bateaux/{gn}', name: 'api.bateaux.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/bateaux/{gn}', name: 'stats.bateaux.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/bateaux/{gn}/csv', name: 'stats.bateaux.gn.csv', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/bateaux/{gn}/json', name: 'stats.bateaux.gn.json', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function bateauxGnAction(#[MapEntity] Gn $gn, string $_route): Response|JsonResponse|StreamedResponse
    {
        $dataQuery = $this->statsService->getBateauxGn($gn);

        return match ($_route) {
            'api.bateaux.gn', 'stats.bateaux.gn.json' => new JsonResponse($dataQuery->getResult()),
            'stats.bateaux.gn.csv' => $this->sendCsv(
                title: 'eveoniris_bateaux_gn_' . $gn->getId() . '_' . date('Ymd'),
                query: $dataQuery,
                header: [
                    'groupe_id',
                    'groupe_numero',
                    'groupe_gn_id',
                    'groupe_nom',
                    'bateaux',
                    'emplacement',
                ],
            ),
            default => $this->render(
                'statistique/bateaux.twig',
                [
                    'bateaux' => $dataQuery->getResult(),
                    'gn' => $gn,
                ],
            ),
        };
    }

    #[Route('/api/bateaux-ordre/{gn}', name: 'api.bateaux-ordre.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/bateaux-ordre/{gn}', name: 'stats.bateaux-ordre.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/bateaux-ordre/{gn}/csv', name: 'stats.bateaux-ordre.gn.csv', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/bateaux-ordre/{gn}/json', name: 'stats.bateaux-ordre.gn.json', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function bateauxOrdreGnAction(#[MapEntity] Gn $gn, string $_route): Response|JsonResponse|StreamedResponse
    {
        $all = $this->statsService->getBateauxOrdreGn($gn)->getResult();
        $sub = [];
        $n = 0;
        foreach ($all as $k => $row) {
            $i = 1;
            while ($i <= $row['bateaux']) {
                $n++;
                $i++;
                $row['numero'] = $n;
                $sub[] = $row;
            }
            unset($all[$k]['bateaux']);
        }

        return match ($_route) {
            'api.bateaux-ordre.gn', 'stats.bateaux-ordre.gn.json' => new JsonResponse($sub),
            'stats.bateaux-ordre.gn.csv' => $this->sendCsv(
                title: 'eveoniris_bateaux_ordre_gn_' . $gn->getId() . '_' . date('Ymd'),
                header: [
                    'numero',
                    'initiative',
                    'groupe_numero',
                    'groupe_nom',
                    'emplacement',
                    'suzerain',
                    'navigateur',
                    'initiative',
                ],
                dataProvider: $sub,
            ),
            default => $this->render(
                'statistique/bateauxOrdre.twig',
                [
                    'bateaux' => $sub,
                    'gn' => $gn,
                ],
            ),
        };
    }

    #[Route('/api/fiefsState', name: 'api.fiefsState')]
    #[Route('/stats/fiefsState', name: 'stats.fiefsState')]
    #[Route('/stats/fiefsState/csv', name: 'stats.fiefsState.csv')]
    #[Route('/stats/fiefsState/json', name: 'stats.fiefsState.json')]
    #[IsGranted(new MultiRolesExpression(Role::ADMIN))]
    public function fiefsState(string $_route): Response|JsonResponse|StreamedResponse
    {
        $dataQuery = $this->statsService->getFiefsState();

        return match ($_route) {
            'api.fiefsState', 'stats.fiefsState.json' => new JsonResponse($dataQuery->getResult()),
            'stats.fiefsState.csv' => $this->sendCsv(
                title: 'eveoniris_fiefsState_' . date('Ymd'),
                query: $dataQuery,
                header: [
                    'id',
                    'fief',
                    'groupe_id',
                    'groupe',
                    'religion',
                    'defense',
                    'stable',
                    'instable',
                    'revenus',
                    'murailles',
                    'bastion',
                    'forteresse',
                    'temple',
                    'sanctuaire',
                    'comptoir',
                    'merveille',
                    'palais',
                    'route',
                    'port',
                    'total_defense',
                    'total_revenus',
                    'suzerain',
                    'renommee',
                    'technologies',
                    'exportations',
                    'ingredients',
                    '@export',
                ],
            ),
            default => $this->render(
                'statistique/fiefsState.twig',
                [
                    'fiefsStates' => $dataQuery->getResult(),
                ],
            ),
        };
    }

    #[Route('/api/{gn}/sumAll', name: 'api.sumAll.gn')]
    #[Route('/stats/{gn}/sumAll', name: 'stats.sumAll.gn')]
    #[Route('/stats/{gn}/sumAll/csv', name: 'stats.sumAll.gn.csv')]
    #[Route('/stats/{gn}/sumAll/json', name: 'stats.sumAll.gn.json')]
    #[IsGranted(new MultiRolesExpression(Role::ADMIN))]
    public function sumAll(#[MapEntity] Gn $gn, string $_route): Response|JsonResponse|StreamedResponse
    {
        $all = $this->groupeService->sumAll($gn, $this->personnageService);

        foreach ($all as $id => $groupe) {
            $ingredients = '';
            foreach ($groupe['ingredient'] as $ingredient) {
                $ingredients .= $ingredient['nb'] . ' ' . $ingredient['label'] . '.' . PHP_EOL;
            }
            $all[$id]['ingredient'] = $ingredients;

            $langues = '';
            foreach ($groupe['langues'] as $langue) {
                $langues .= $langue['nb'] . ' ' . $langue['label'] . '.' . PHP_EOL;
            }
            $all[$id]['langues'] = $langues;

            $ressources = '';
            foreach ($groupe['ressources'] as $ressource) {
                $ressources .= $ressource['nb'] . ' ' . $ressource['label'] . '.' . PHP_EOL;
            }
            $all[$id]['ressources'] = $ressources;
        }

        return match ($_route) {
            'api.sumAll.gn', 'stats.sumAll.gn.json' => new JsonResponse($all),
            'stats.sumAll.gn.csv' => $this->sendCsv(
                title: 'eveoniris_sumAll_' . date('Ymd'),
                header: [
                    'id',
                    'nom',
                    'richesse',
                    'renomme',
                    'ingredient',
                    'langues',
                    'ressources',
                ],
                dataProvider: $all,
            ),
            default => $this->render(
                'statistique/sumAll.twig',
                [
                    'all' => $all,
                ],
            ),
        };
    }

    #[Route('/stats/classes/{gn}/csv', name: 'stats.classes.gn.csv', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function classesCsvStatsGnAction(#[MapEntity] Gn $gn): StreamedResponse
    {
        return $this->sendCsv(
            title: 'eveoniris_classes_gn_' . $gn->getId() . '_' . date('Ymd'),
            query: $this->statsService->getClassesGn($gn),
            header: ['total', 'label', 'id'],
        );
    }

    #[Route('/stats/classes/{gn}', name: 'stats.classes.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function classesStatsGnAction(#[MapEntity] Gn $gn): Response
    {
        return $this->render('statistique/classes.twig', [
            'classes' => $this->statsService->getClassesGn($gn)->getResult(),
            'gn' => $gn,
        ]);
    }

    #[Route('/api/classes/{gn}', name: 'api.classes.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/classes/{gn}/json', name: 'stats.classes.gn.json', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function classesStatsGnApiAction(#[MapEntity] Gn $gn): JsonResponse
    {
        return new JsonResponse($this->statsService->getClassesGn($gn)->getResult());
    }

    #[Route(
        '/stats/competenceFamily/{competenceFamily}/gn/{gn}/csv',
        name: 'stats.competenceFamily.gn.csv',
        requirements: ['competenceFamily' => Requirement::DIGITS, 'gn' => Requirement::DIGITS]
    )]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function competenceFamilyCsvStatsGnAction(
        #[MapEntity] CompetenceFamily $competenceFamily,
        #[MapEntity] Gn               $gn,
    ): StreamedResponse
    {
        return $this->sendCsv(
            title: 'eveoniris_competenceFamily_gn_' . $gn->getId() . '_' . date('Ymd'),
            query: $this->statsService->getCompetenceFamilyGn($competenceFamily, $gn),
            header: ['total', 'niveau', 'indexNiveau'],
        );
    }

    #[Route(
        '/stats/competenceFamily/{competenceFamily}/gn/{gn}',
        name: 'stats.competenceFamily.gn',
        requirements: ['competenceFamily' => Requirement::DIGITS, 'gn' => Requirement::DIGITS]
    )]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role: Role::ORGA))]
    public function competenceFamilyStatsGnAction(
        #[MapEntity] CompetenceFamily $competenceFamily,
        #[MapEntity] Gn               $gn,
    ): Response
    {
        return $this->render('statistique/competenceFamily.twig', [
            'competences' => $this->statsService->getCompetenceFamilyGn($competenceFamily, $gn)->getResult(),
            'competenceFamily' => $competenceFamily,
            'gn' => $gn,
        ]);
    }

    #[Route(
        '/api/competenceFamily/{competenceFamily}/gn/{gn}',
        name: 'api.competenceFamily.gn',
        requirements: ['competenceFamily' => Requirement::DIGITS, 'gn' => Requirement::DIGITS]
    )]
    #[Route('/stats/competenceFamily/{competenceFamily}/gn/{gn}/json',
        name: 'stats.competenceFamily.gn.json',
        requirements: ['competenceFamily' => Requirement::DIGITS, 'gn' => Requirement::DIGITS]
    )]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role: Role::ORGA))]
    public function competenceFamilyStatsGnApiAction(
        #[MapEntity] CompetenceFamily $competenceFamily,
        #[MapEntity] Gn               $gn,
    ): JsonResponse
    {
        return new JsonResponse($this->statsService->getCompetenceFamilyGn($competenceFamily, $gn)->getResult());
    }

    #[Route('/stats/competences/{gn}/csv', name: 'stats.competences.gn.csv', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE, Role: Role::ORGA))]
    public function competencesCsvStatsGnAction(#[MapEntity] Gn $gn): StreamedResponse
    {
        return $this->sendCsv(
            title: 'eveoniris_competences_gn_' . $gn->getId() . '_' . date('Ymd'),
            query: $this->statsService->getCompetenceGn($gn),
            header: ['total', 'competence', 'niveau'],
        );
    }

    #[Route('/stats/competences/{gn}', name: 'stats.competences.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function competencesStatsGnAction(
        #[MapEntity] Gn $gn,
    ): Response
    {
        return $this->render('statistique/competencesGn.twig', [
            'competences' => $this->statsService->getCompetenceGn($gn)->getResult(),
            'gn' => $gn,
        ]);
    }

    #[Route('/api/competences/{gn}', name: 'api.competences.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/competences/{gn}/json', name: 'stats.competences.gn.json', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function competencesStatsGnApiAction(#[MapEntity] Gn $gn): JsonResponse
    {
        return new JsonResponse($this->statsService->getCompetenceGn($gn)->getResult());
    }

    #[Route('/api/constructions', name: 'api.constructions')]
    #[Route('/stats/constructions/json', name: 'stats.constructions.json')]
    #[IsGranted(new MultiRolesExpression(Role::WARGAME))]
    public function constructionsApiAction(): JsonResponse
    {
        return new JsonResponse($this->statsService->getConstructions()->getResult());
    }

    #[Route('/stats/constructions/csv', name: 'stats.constructions.csv')]
    #[IsGranted(new MultiRolesExpression(Role::WARGAME))]
    public function constructionsCsvStatsAction(): StreamedResponse
    {
        return $this->sendCsv(
            title: 'eveoniris_construction_' . date('Ymd'),
            query: $this->statsService->getConstructions(),
            header: ['fief', 'batiment', 'description', 'defense'],
        );
    }

    #[Route('/api/constructions/fiefs', name: 'api.constructions.fiefs')]
    #[Route('/stats/constructions/fiefs/json', name: 'stats.constructions.fiefs.json')]
    #[IsGranted(new MultiRolesExpression(Role::WARGAME))]
    public function constructionsFiefsApiAction(): JsonResponse
    {
        return new JsonResponse($this->statsService->getConstructionsFiefs()->getResult());
    }

    #[Route('/stats/constructions/fiefs.csv', name: 'stats.constructions.fiefs.csv')]
    #[IsGranted(new MultiRolesExpression(Role::WARGAME))]
    public function constructionsFiefsCsvStatsAction(): StreamedResponse
    {
        return $this->sendCsv(
            title: 'eveoniris_construction_fiefs_' . date('Ymd'),
            query: $this->statsService->getConstructionsFiefs(),
            header: ['fief', 'batiment', 'description', 'defense'],
        );
    }

    #[Route('/stats/constructions/fiefs', name: 'stats.constructions.fiefs')]
    #[IsGranted(new MultiRolesExpression(Role::WARGAME))]
    public function constructionsFiefsStatsAction(): Response
    {
        return $this->render('statistique/constructionsFiefs.twig', [
            'constructions' => $this->statsService->getConstructionsFiefs()->getResult(),
        ]);
    }

    #[Route('/stats/constructions', name: 'stats.constructions')]
    #[IsGranted(new MultiRolesExpression(Role::WARGAME))]
    public function constructionsStatsAction(): Response
    {
        return $this->render('statistique/constructions.twig', [
            'constructions' => $this->statsService->getConstructions()->getResult(),
        ]);
    }

    #[Route('/api/gameItemWithoutPersonnage', name: 'api.gameItemWithoutPersonnage')]
    #[Route('/stats/gameItemWithoutPersonnage/json', name: 'stats.gameItemWithoutPersonnage.json')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function gameItemWithoutPersonnageApiAction(): JsonResponse
    {
        return new JsonResponse($this->statsService->getGameItemWithoutPersonnage()->getResult());
    }

    #[Route('/stats/gameItemWithoutPersonnage/csv', name: 'stats.gameItemWithoutPersonnage.csv', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function gameItemWithoutPersonnageCsvStatsAction(): StreamedResponse
    {
        return $this->sendCsv(
            title: 'eveoniris_getGameItemWithoutPersonnage_' . date('Ymd'),
            query: $this->statsService->getGameItemWithoutPersonnage(),
            header: ['id', 'label', 'quality_id', 'numero', 'identification', 'couleur', 'quantite', 'objet_id'],
        );
    }

    #[Route('/stats/gameItemWitoutPersonnage', name: 'stats.gameItemWithoutPersonnage')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function gameItemWithoutPersonnageStatsAction(): Response
    {
        return $this->render('statistique/gameItemWithoutPersonnage.twig', [
            'gameItemWithoutPersonnages' => $this->statsService->getGameItemWithoutPersonnage()->getResult(),
        ]);
    }

    /**
     * @throws JsonException
     */
    #[Route('/statistique', name: 'statistique')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access the admin dashboard.')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository(Langue::class);
        $langues = $repo->findAll();
        $stats = [];
        foreach ($langues as $langue) {
            $colors = RandomColor::many(2, [
                'luminosity' => ['light', 'bright'],
                'hue' => 'random',
            ]);

            $stats[] = [
                'value' => $langue->getTerritoires()->count(),
                'color' => $colors[0],
                'highlight' => $colors[1],
                'label' => $langue->getLabel(),
            ];
        }

        $repo = $this->entityManager->getRepository(Classe::class);
        $classes = $repo->findAll();
        $statClasses = [];
        foreach ($classes as $classe) {
            $colors = RandomColor::many(2, [
                'luminosity' => ['light', 'bright'],
                'hue' => 'random',
            ]);
            $statClasses[] = [
                'value' => $classe->getPersonnages()->count(),
                'color' => $colors[0],
                'highlight' => $colors[1],
                'label' => $classe->getLabel(),
            ];
        }

        /**
         * TODO:
         * $repo = $entityManager->getRepository(Construction::class);
         * $constructions = $repo->findAll();
         * $statConstructions = [];
         * foreach ($constructions as $construction) {
         * $colors = RandomColor::many(2, [
         * 'luminosity' => ['light', 'bright'],
         * 'hue' => 'random',
         * ]);
         * $statConstructions[] = [
         * 'value' => $construction->getTerritoires()->count(),
         * 'color' => $colors[0],
         * 'highlight' => $colors[1],
         * 'label' => $construction->getLabel(),
         * ];
         * }
         * */
        $repo = $this->entityManager->getRepository(Competence::class);
        $competences = $repo->findAllOrderedByLabel();
        $statCompetences = [];
        $statCompetencesFamily = [];
        $valueFamily = 0;
        $previousFamily = '';
        foreach ($competences as $competence) {
            $colors = RandomColor::many(2, [
                'luminosity' => ['light', 'bright'],
                'hue' => 'random',
            ]);
            $statCompetences[] = [
                'value' => $competence->getPersonnages()->count(),
                'color' => $colors[0],
                'highlight' => $colors[1],
                'label' => $competence->getCompetenceFamily()->getLabel() . ' - ' . $competence->getLevel()->getLabel(),
            ];

            if ($previousFamily !== $competence->getCompetenceFamily()->getLabel()) {
                $statCompetencesFamily[] = [
                    'value' => $valueFamily,
                    'color' => $colors[0],
                    'highlight' => $colors[1],
                    'label' => $previousFamily,
                ];
                $valueFamily = $competence->getPersonnages()->count();
                $previousFamily = $competence->getCompetenceFamily()->getLabel();
            } else {
                $valueFamily += $competence->getPersonnages()->count();
            }
        }

        $repo = $entityManager->getRepository(Personnage::class);
        $personnages = $repo->findAll();

        $repo = $entityManager->getRepository(User::class);
        $users = $repo->findAll();

        $repo = $entityManager->getRepository(Participant::class);
        $participants = $repo->findAll();

        $repo = $entityManager->getRepository(Groupe::class);
        $groupes = $repo->findAll();
        $places = 0;
        foreach ($groupes as $groupe) {
            $places += $groupe->getClasseOpen();
        }

        $repo = $entityManager->getRepository(Genre::class);
        $genres = $repo->findAll();
        $statGenres = [];
        foreach ($genres as $genre) {
            $colors = RandomColor::many(2, [
                'luminosity' => ['light', 'bright'],
                'hue' => 'random',
            ]);
            $statGenres[] = [
                'value' => $genre->getPersonnages()->count(),
                'color' => $colors[0],
                'highlight' => $colors[1],
                'label' => $genre->getLabel(),
            ];
        }

        return $this->render('statistique/index.twig', [
            'langues' => json_encode($stats, JSON_THROW_ON_ERROR),
            'classes' => json_encode($statClasses, JSON_THROW_ON_ERROR),
            'genres' => json_encode($statGenres, JSON_THROW_ON_ERROR),
            'competences' => json_encode($statCompetences, JSON_THROW_ON_ERROR),
            'competencesFamily' => json_encode($statCompetencesFamily, JSON_THROW_ON_ERROR),
            'constructions' => [], // TODO json_encode($statConstructions, JSON_THROW_ON_ERROR),
            'personnageCount' => count($personnages),
            'userCount' => max(count($users), 1),
            'participantCount' => count($participants),
            'places' => $places,
        ]);
    }

    #[Route('/api/mineurs/{gn}', name: 'api.mineurs.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/mineurs/{gn}/json', name: 'stats.mineurs.gn.json', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA))]
    public function mineursApiGnAction(
        #[MapEntity] Gn $gn,
    ): JsonResponse
    {
        return new JsonResponse($this->statsService->getMineurs($gn)->getResult());
    }

    #[Route('/stats/mineurs/{gn}/csv', name: 'stats.mineurs.gn.csv', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA))]
    public function mineursCsvStatsGnAction(
        #[MapEntity] Gn $gn,
    ): StreamedResponse
    {
        return $this->sendCsv(
            title: 'eveoniris_mineurs_gn_' . $gn->getId() . '_' . date('Ymd'),
            query: $this->statsService->getMineurs($gn),
            header: [
                'nom',
                'prenom_usage',
                'prenom',
                'userId',
                'email',
                'email_contact',
                'groupeId',
                'groupe',
                'personnageId',
                'personnage',
                'sensible',
            ],
        );
    }

    #[Route('/stats/mineurs/{gn}', name: 'stats.mineurs.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA))]
    public function mineursStatsGnAction(
        #[MapEntity] Gn $gn,
    ): Response
    {
        return $this->render('statistique/mineursGn.twig', [
            'mineurs' => $this->statsService->getMineurs($gn)->getResult(),
            'gn' => $gn,
        ]);
    }

    #[Route('/api/nbClasseGroupe/classe/{classe}/gn/{gn}', name: 'api.nbClasseGroupe.gn', requirements: [
        'gn' => Requirement::DIGITS,
        'classe' => Requirement::DIGITS,
    ])]
    #[Route('/stats/nbClasseGroupe/classe/{classe}/gn/{gn}', name: 'stats.nbClasseGroupe.gn', requirements: [
        'gn' => Requirement::DIGITS,
        'classe' => Requirement::DIGITS,
    ])]
    #[Route('/stats/nbClasseGroupe/classe/{classe}/gn/{gn}/csv', name: 'stats.nbClasseGroupe.gn.csv', requirements: [
        'gn' => Requirement::DIGITS,
        'classe' => Requirement::DIGITS,
    ])]
    #[Route('/stats/nbClasseGroupe/classe/{classe}/gn/{gn}/json', name: 'stats.nbClasseGroupe.gn.json', requirements: [
        'gn' => Requirement::DIGITS,
        'classe' => Requirement::DIGITS,
    ])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function nbClasseGroupeGnAction(
        #[MapEntity] Gn     $gn,
        #[MapEntity] Classe $classe,
        string              $_route,
    ): Response|JsonResponse|StreamedResponse
    {
        $dataQuery = $this->statsService->nbClasseGroupeGn($gn, $classe);

        return match ($_route) {
            'api.nbClasseGroupe.gn', 'stats.nbClasseGroupe.gn.json' => new JsonResponse($dataQuery->getResult()),
            'stats.nbClasseGroupe.gn.csv' => $this->sendCsv(
                title: 'nbClasseGroupe' . $gn->getId() . '_' . date('Ymd'),
                query: $dataQuery,
                header: [
                    'total',
                    'id',
                    'label',
                    'level',
                ],
            ),
            default => $this->render(
                'statistique/nbClasseGroupe.twig',
                [
                    'nbClasseGroupes' => $dataQuery->getResult(),
                    'gn' => $gn,
                    'classe' => $classe,
                ],
            ),
        };
    }

    #[Route('/api/potionsDepart/{gn}', name: 'api.potionsDepart.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/potionsDepart/{gn}', name: 'stats.potionsDepart.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/potionsDepart/{gn}/csv', name: 'stats.potionsDepart.gn.csv', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/potionsDepart/{gn}/json', name: 'stats.potionsDepart.gn.json', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function potionsDepartGnAction(#[MapEntity] Gn $gn, string $_route): Response|JsonResponse|StreamedResponse
    {
        return match ($_route) {
            'api.potionsDepart.gn', 'stats.potionsDepart.gn.json' => new JsonResponse(
                $this->statsService->getPotionsDepartGn($gn)->getResult(),
            ),
            'stats.potionsDepart.gn.csv' => $this->sendCsv(
                title: 'eveoniris_potionsDepartGn_gn_' . $gn->getId() . '_' . date('Ymd'),
                query: $this->statsService->getPotionsDepartGn($gn),
                header: [
                    'potion_id',
                    'personnage_id',
                    'personnage',
                    'label',
                    'numero',
                    'niveau',
                ],
            ),
            default => $this->render('statistique/potionsDepart.twig', [
                'potionsDeparts' => $this->statsService->getPotionsDepartGn($gn)->getResult(),
                'gn' => $gn,
            ]),
        };
    }

    #[Route('/stats/religion/{religion}/gn/{gn}/personnage/csv', name: 'stats.religionPersonnage.gn.csv', requirements: [
        'gn' => Requirement::DIGITS,
        'personnage' => Requirement::DIGITS,
    ])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function religionPersonnageCsvStatsGnAction(
        #[MapEntity] Gn       $gn,
        #[MapEntity] Religion $religion,
        ReligionRepository    $religionRepository,
    ): StreamedResponse
    {
        return $this->sendCsv(
            title: 'eveoniris_religion_personnage_gn_' . $gn->getId() . '_' . date('Ymd'),
            query: $religionRepository->getPersonnagesByReligions($gn, $religion),
            header: ['religionId', 'label', 'level', 'personnage', 'personnageId', 'vivant', 'pnj', 'email'],
        );
    }

    #[Route('/stats/religion/{religion}/gn/{gn}/personnage', name: 'stats.religionPersonnage.gn', requirements: [
        'gn' => Requirement::DIGITS,
        'personnage' => Requirement::DIGITS,
    ])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function religionPersonnageStatsGnAction(
        #[MapEntity] Gn       $gn,
        #[MapEntity] Religion $religion,
        ReligionRepository    $religionRepository,
    ): Response
    {
        return $this->render('statistique/religionPersonnages.twig', [
            'religionPersonnages' => $religionRepository->getPersonnagesByReligions($gn, $religion)->getResult(),
            'gn' => $gn,
            'religion' => $religion,
        ]);
    }

    #[Route('/api/religion/{religion}/gn/{gn}/personnage', name: 'api.religionPersonnage.gn', requirements: [
        'gn' => Requirement::DIGITS,
        'personnage' => Requirement::DIGITS,
    ])]
    #[Route('/stats/religion/{religion}/gn/{gn}/personnage/json', name: 'stats.religionPersonnage.gn.json', requirements: [
        'gn' => Requirement::DIGITS,
        'personnage' => Requirement::DIGITS,
    ])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function religionPersonnageStatsGnApiAction(
        #[MapEntity] Gn       $gn,
        #[MapEntity] Religion $religion,
        ReligionRepository    $religionRepository,
    ): JsonResponse
    {
        return new JsonResponse($religionRepository->getPersonnagesByReligions($gn, $religion)->getResult());
    }

    #[Route('/api/religions/{gn}', name: 'api.religions.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/religions/{gn}', name: 'stats.religions.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/religions/{gn}/csv', name: 'stats.religions.gn.csv', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/religions/{gn}/json', name: 'stats.religions.gn.json', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function religionsGnAction(#[MapEntity] Gn $gn, string $_route): Response|JsonResponse|StreamedResponse
    {
        $dataQuery = $this->statsService->getReligionsLevelGn($gn);
        $chartData = [
            'labels' => [],
            'data' => [],
        ];
        foreach ($this->statsService->getReligionsGn($gn)->getResult() as $data) {
            $chartData['labels'][] = $data['label'];
            $chartData['values'][] = $data['total'];
        }

        return match ($_route) {
            'api.religions.gn', 'stats.religions.gn.json' => new JsonResponse($dataQuery->getResult()),
            'stats.religions.gn.csv' => $this->sendCsv(
                title: 'eveoniris_religions_gn_' . $gn->getId() . '_' . date('Ymd'),
                query: $dataQuery,
                header: [
                    'total',
                    'id',
                    'label',
                    'level',
                ],
            ),
            default => $this->render(
                'statistique/religions.twig',
                [
                    'religions' => $dataQuery->getResult(),
                    'gn' => $gn,
                    'chartData' => $chartData,
                ],
            ),
        };
    }

    #[Route('/api/religions/pratiquants/{gn}', name: 'api.religions.pratiquants', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function religionsPratiquantsAction(
        #[MapEntity] Gn $gn,
    ): JsonResponse
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('pratiquants', 'pratiquants', 'integer');
        $rsm->addScalarResult('religion', 'religion', 'string');
        $rsm->addScalarResult('niveau', 'niveau', 'string');

        /** @noinspection SqlNoDataSourceInspection */
        $query = $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT r.label as religion, count(perso.id) as pratiquants, rl.label as niveau
                FROM participant p
                         INNER JOIN personnages_religions pr ON p.personnage_id = pr.personnage_id
                         INNER JOIN religion r ON pr.religion_id = r.id
                         INNER JOIN personnage perso ON pr.personnage_id = perso.id
                         INNER JOIN religion_level rl ON pr.religion_level_id = rl.id
                WHERE perso.vivant = 1 and p.gn_id = :gnid
                GROUP BY pr.religion_id, rl.label
                ORDER BY pratiquants DESC;
                SQL,
            $rsm,
        )->setParameter('gnid', $gn->getId());

        return new JsonResponse($query->getResult());
    }

    #[Route('/stats/renomme/{gn}/csv', name: 'stats.renomme.gn.csv', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function renommeCsvStatsGnAction(#[MapEntity] Gn $gn): StreamedResponse
    {
        return $this->sendCsv(
            title: 'eveoniris_renomme_gn_' . $gn->getId() . '_' . date('Ymd'),
            query: $this->statsService->getRenommeGn($gn),
            header: ['total', 'grp_renome'],
        );
    }

    #[Route('/api/renommeGroupe/{gn}', name: 'api.renommeGroupe.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/renommeGroupe/{gn}', name: 'stats.renommeGroupe.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/renommeGroupe/{gn}/csv', name: 'stats.renommeGroupe.gn.csv', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/renommeGroupe/{gn}/json', name: 'stats.renommeGroupe.gn.json', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function renommeGroupeGnAction(#[MapEntity] Gn $gn, string $_route): Response|JsonResponse|StreamedResponse
    {
        $dataQuery = $this->statsService->renommeGroupeGn($gn, 10);

        return match ($_route) {
            'api.renommeGroupe.gn', 'stats.renommeGroupe.gn.json' => new JsonResponse($dataQuery->getResult()),
            'stats.renommeGroupe.gn.csv' => $this->sendCsv(
                title: 'eveoniris_renommeGroupe_gn_' . $gn->getId() . '_' . date('Ymd'),
                query: $dataQuery,
                header: [
                    'total',
                    'id',
                    'label',
                    'level',
                ],
            ),
            default => $this->render(
                'statistique/renommeGroupe.twig',
                [
                    'renommeGroupes' => $dataQuery->getResult(),
                    'gn' => $gn,
                ],
            ),
        };
    }

    #[Route('/stats/renomme/{gn}', name: 'stats.renomme.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function renommeStatsGnAction(#[MapEntity] Gn $gn): Response
    {
        return $this->render('statistique/renomme.twig', [
            'renommes' => $this->statsService->getRenommeGn($gn)->getResult(),
            'gn' => $gn,
        ]);
    }

    #[Route('/api/renomme/{gn}', name: 'api.renomme.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/renomme/{gn}/json', name: 'stats.renomme.gn.json', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::SCENARISTE))]
    public function renommeStatsGnApiAction(#[MapEntity] Gn $gn): JsonResponse
    {
        return new JsonResponse($this->statsService->getRenommeGn($gn)->getResult());
    }

    #[Route('/api/sensible/{gn}', name: 'api.sensible.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/sensible/{gn}', name: 'stats.sensible.gn', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/sensible/{gn}/csv', name: 'stats.sensible.gn.csv', requirements: ['gn' => Requirement::DIGITS])]
    #[Route('/stats/sensible/{gn}/json', name: 'stats.sensible.gn.json', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))]
    public function sensibleGnAction(#[MapEntity] Gn $gn, string $_route): Response|JsonResponse|StreamedResponse
    {
        $dataQuery = $this->statsService->sensibleGn($gn);

        return match ($_route) {
            'api.sensible.gn', 'stats.sensible.gn.json' => new JsonResponse($dataQuery->getResult()),
            'stats.sensible.gn.csv' => $this->sendCsv(
                title: 'sensible' . $gn->getId() . '_' . date('Ymd'),
                query: $dataQuery,
                header: [
                    'nom',
                    'prenom_usage',
                    'prenom',
                    'userId',
                    'email',
                    'email_contact',
                    'groupeId',
                    'groupe',
                    'personnageId',
                    'personnage',
                    'sensible',
                ],
            ),
            default => $this->render(
                'statistique/sensible.twig',
                [
                    'sensibles' => $dataQuery->getResult(),
                    'gn' => $gn,
                ],
            ),
        };
    }

    #[Route('/stats', name: 'stats')]
    #[Route('/stats/list', name: 'stats.list')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE, Role::ADMIN, Role::WARGAME))]
    public function statsAction(): Response
    {
        return $this->render('statistique/list.twig');
    }

    #[Route('/stats/users/roles', name: 'stats.users.roles')]
    #[IsGranted(new MultiRolesExpression(Role::ADMIN))]
    public function usersRolesAction(): Response
    {
        $roles = [];
        foreach (Role::toArray() as $simple => $role) {
            if (Role::USER->value === $role) {
                continue;
            }
            $roles[$simple] = $this->statsService->getUsersRole(Role::tryFrom($role))->getResult();
        }

        return $this->render('statistique/usersRoles.twig', [
            'roles' => $roles,
        ]);
    }

    #[Route('/api/xpGap/{gn}/{gap}', name: 'api.xpGap.gn', requirements: ['gn' => Requirement::DIGITS], defaults: ['gap' => 50])]
    #[Route('/stats/xpGap/{gn}/{gap}', name: 'stats.xpGap.gn', requirements: ['gn' => Requirement::DIGITS], defaults: ['gap' => 50])]
    #[Route('/stats/xpGap/{gn}/{gap}/csv', name: 'stats.xpGap.gn.csv', requirements: ['gn' => Requirement::DIGITS], defaults: ['gap' => 50])]
    #[Route('/stats/xpGap/{gn}/{gap}/json', name: 'stats.xpGap.gn.json', requirements: ['gn' => Requirement::DIGITS], defaults: ['gap' => 50])]
    #[IsGranted(new MultiRolesExpression(Role::ADMIN))]
    public function xpGapGnAction(
        #[MapEntity] Gn $gn,
        string          $_route,
        int             $gap = 50,
    ): Response|JsonResponse|StreamedResponse
    {
        $dataQuery = $this->statsService->getXpGap($gn, $gap);

        return match ($_route) {
            'api.xpGap.gn', 'stats.xpGap.gn.json' => new JsonResponse($dataQuery->getResult()),
            'stats.xpGap.gn.csv' => $this->sendCsv(
                title: 'xpGap_' . $gn->getId() . '_' . date('Ymd'),
                query: $dataQuery,
                header: [
                    'perso_id',
                    'perso_nom',
                    'xp_restant',
                    'total',
                    'gourpe_id',
                    'groupe_nom',
                ],
            ),
            default => $this->render(
                'statistique/xpGap.twig',
                [
                    'xpGaps' => $dataQuery->getResult(),
                    'gn' => $gn,
                    'gap' => $gap,
                ],
            ),
        };
    }
}
