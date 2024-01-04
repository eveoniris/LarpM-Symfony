<?php


namespace App\Controller;

use LarpManager\Services\RandomColor\RandomColor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * LarpManager\Controllers\StatistiqueController.
 *
 * @author kevin
 */
class StatistiqueController extends AbstractController
{
    // TODO : move to admin dashboard
    #[Route('/statistique', name: 'statistique')]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access the admin dashboard.')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository(\App\Entity\Langue::class);
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

        $repo = $entityManager->getRepository(\App\Entity\Classe::class);
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

        $repo = $entityManager->getRepository(\App\Entity\Construction::class);
        $constructions = $repo->findAll();
        $statConstructions = [];
        foreach ($constructions as $construction) {
            $colors = RandomColor::many(2, [
                'luminosity' => ['light', 'bright'],
                'hue' => 'random',
            ]);
            $statConstructions[] = [
                'value' => $construction->getTerritoires()->count(),
                'color' => $colors[0],
                'highlight' => $colors[1],
                'label' => $construction->getLabel(),
            ];
        }

        $repo = $entityManager->getRepository(\App\Entity\Competence::class);
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

            if ($previousFamily != $competence->getCompetenceFamily()->getLabel()) {
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

        $repo = $entityManager->getRepository(\App\Entity\Personnage::class);
        $personnages = $repo->findAll();

        $repo = $entityManager->getRepository(\App\Entity\User::class);
        $Users = $repo->findAll();

        $repo = $entityManager->getRepository(\App\Entity\Participant::class);
        $participants = $repo->findAll();

        $repo = $entityManager->getRepository(\App\Entity\Groupe::class);
        $groupes = $repo->findAll();
        $places = 0;
        foreach ($groupes as $groupe) {
            $places += $groupe->getClasseOpen();
        }

        $repo = $entityManager->getRepository(\App\Entity\Genre::class);
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

        return $this->render('admin/statistique/index.twig', [
            'langues' => json_encode($stats),
            'classes' => json_encode($statClasses),
            'genres' => json_encode($statGenres),
            'competences' => json_encode($statCompetences),
            'competencesFamily' => json_encode($statCompetencesFamily),
            'constructions' => json_encode($statConstructions),
            'personnageCount' => count($personnages),
            'UserCount' => count($Users),
            'participantCount' => count($participants),
            'places' => $places,
        ]);
    }
}
