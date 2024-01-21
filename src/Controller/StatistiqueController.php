<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Competence;
use App\Entity\Construction;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Langue;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\User;
use App\Manager\RandomColor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
}
