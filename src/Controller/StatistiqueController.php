<?php

/**
 * LarpManager - A Live Action Role Playing Manager
 * Copyright (C) 2016 Kevin Polez.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Controller;

use LarpManager\Services\RandomColor\RandomColor;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\StatistiqueController.
 *
 * @author kevin
 */
class StatistiqueController
{
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository(\App\Entity\Langue::class);
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

        $repo = $app['orm.em']->getRepository(\App\Entity\Classe::class);
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

        $repo = $app['orm.em']->getRepository(\App\Entity\Construction::class);
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

        $repo = $app['orm.em']->getRepository(\App\Entity\Competence::class);
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

        $repo = $app['orm.em']->getRepository(\App\Entity\Personnage::class);
        $personnages = $repo->findAll();

        $repo = $app['orm.em']->getRepository(\App\Entity\User::class);
        $Users = $repo->findAll();

        $repo = $app['orm.em']->getRepository(\App\Entity\Participant::class);
        $participants = $repo->findAll();

        $repo = $app['orm.em']->getRepository(\App\Entity\Groupe::class);
        $groupes = $repo->findAll();
        $places = 0;
        foreach ($groupes as $groupe) {
            $places += $groupe->getClasseOpen();
        }

        $repo = $app['orm.em']->getRepository(\App\Entity\Genre::class);
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

        return $app['twig']->render('admin/statistique/index.twig', [
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
