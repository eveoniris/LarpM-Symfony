<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Competence;
use App\Entity\Construction;
use App\Entity\Genre;
use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\Langue;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\User;
use App\Enum\Role;
use App\Manager\RandomColor;
use App\Security\MultiRolesExpression;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new MultiRolesExpression(Role::ADMIN))]
class StatistiqueController extends AbstractController
{
    /**
     * @throws \JsonException
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

        $repo = $entityManager->getRepository(Classe::class);
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
        $repo = $entityManager->getRepository(Competence::class);
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
                'label' => $competence->getCompetenceFamily()->getLabel().' - '.$competence->getLevel()->getLabel(),
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
        $Users = $repo->findAll();

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
            'UserCount' => count($Users),
            'participantCount' => count($participants),
            'places' => $places,
        ]);
    }


    #[Route('/api/religions/pratiquants/{gn}', name: 'api.religions.pratiquants', requirements: ['gn' => Requirement::DIGITS])]
    #[IsGranted('ROLE_SCENARISTE', message: 'You are not allowed to access the admin dashboard.')]
    public function religionsPratiquantsAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Gn $gn,
    ): JsonResponse {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('pratiquants', 'pratiquants', 'integer');
        $rsm->addScalarResult('religion', 'religion', 'string');
        $rsm->addScalarResult('niveau', 'niveau', 'string');

        /** @noinspection SqlNoDataSourceInspection */
        $query = $entityManager->createNativeQuery(
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
}
