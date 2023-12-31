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

use App\Entity\EtatCivil;
use App\Entity\Restriction;
use Doctrine\ORM\EntityManagerInterface;
use LarpManager\Form\EtatCivilForm;
use LarpManager\Form\UserRestrictionForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LarpManager\Controllers\HomepageController.
 *
 * @author kevin
 */
class HomepageController extends AbstractController
{
    /**
     * Choix de la page d'acceuil en fonction de l'état de l'utilisateur.
     */
    public function indexAction(Request $request, EntityManagerInterface $entityManager)
    {
        if (!$this->getUser()) {
            return $this->notConnectedIndexAction($request);
        }

        if (!$this->getUser()->getEtatCivil()) {
            return $this->redirectToRoute('newUser.step1', [], 303);
        }

        $repoAnnonce = $entityManager->getRepository(\App\Entity\Annonce::class);
        $annonces = $repoAnnonce->findBy(['archive' => false, 'gn' => null], ['update_date' => 'DESC']);

        return $this->render('homepage/index.twig', [
            'annonces' => $annonces,
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Première étape pour un nouvel utilisateur.
     */
    public function newUserStep1Action(Request $request, EntityManagerInterface $entityManager)
    {
        if ($this->getUser()->getEtatCivil()) {
            $repoAnnonce = $entityManager->getRepository(\App\Entity\Annonce::class);
            $annonces = $repoAnnonce->findBy(['archive' => false, 'gn' => null], ['update_date' => 'DESC']);

            return $this->render('homepage/index.twig', [
                'annonces' => $annonces,
                'User' => $this->getUser(),
            ]);
        }

        return $this->render('public/newUser/step1.twig', []);
    }

    /**
     * Seconde étape pour un nouvel utilisateur : enregistrer les informations administratives.
     */
    public function newUserStep2Action(Request $request)
    {
        $etatCivil = $this->getUser()?->getEtatCivil();
        if (!$etatCivil) {
            $etatCivil = new EtatCivil();
        }

        $form = $app['form.factory']->createBuilder(new EtatCivilForm(), $etatCivil)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $etatCivil = $form->getData();
            $this->getUser()->setEtatCivil($etatCivil);

            $app['orm.em']->persist($this->getUser());
            $app['orm.em']->flush();

            return $this->redirectToRoute('newUser.step3', [], 303);
        }

        return $app['twig']->render('public/newUser/step2.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Troisième étape pour un nouvel utilisateur : les restrictions alimentaires.
     */
    public function newUserStep3Action(Request $request, Application $app)
    {
        $form = $app['form.factory']->createBuilder(new UserRestrictionForm(), $this->getUser())
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $User = $form->getData();
            $newRestriction = $form->get('new_restriction')->getData();
            if ($newRestriction) {
                $restriction = new Restriction();
                $restriction->setUserRelatedByAuteurId($this->getUser());
                $restriction->setLabel($newRestriction);

                $app['orm.em']->persist($restriction);
                $User->addRestriction($restriction);
            }

            $app['orm.em']->persist($User);
            $app['orm.em']->flush();

            return $this->redirectToRoute('newUser.step4', [], 303);
        }

        return $app['twig']->render('public/newUser/step3.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Quatrième étape pour un nouvel utilisateur : choisir un GN.
     */
    public function newUserStep4Action(Request $request, Application $app)
    {
        return $app['twig']->render('public/newUser/step4.twig');
    }

    /**
     * Fourni le blason pour affichage.
     */
    public function getBlasonAction(Request $request, Application $app)
    {
        $blason = $request->get('blason');
        $filename = __DIR__.'/../../../private/img/blasons/'.$blason;

        return $app->sendFile($filename);
    }

    /**
     * Page d'acceuil pour les utilisateurs non connecté.
     */
    public function notConnectedIndexAction(Request $request, Application $app)
    {
        return $app['twig']->render('homepage/not_connected.twig');
    }

    /**
     * Affiche une carte du monde.
     */
    #[Route('/world', name: 'world')]
    public function worldAction(Request $request, Application $app)
    {
        return $app['twig']->render('public/world.twig');
    }

    /**
     * Fourni la liste des pays, leur geographie et leur description.
     */
    public function countriesAction(Request $request, Application $app)
    {
        $repoTerritoire = $app['orm.em']->getRepository(\App\Entity\Territoire::class);
        $territoires = $repoTerritoire->findRoot();

        $countries = [];
        foreach ($territoires as $territoire) {
            $countries[] = [
                'id' => $territoire->getId(),
                'geom' => $territoire->getGeojson(),
                'name' => $territoire->getNom(),
                'color' => $territoire->getColor(),
                'description' => strip_tags((string) $territoire->getDescription()),
                'groupes' => array_values($territoire->getGroupesPj()),
                'desordre' => $territoire->getStatutIndex(),
                'langue' => $territoire->getLanguePrincipale(),
            ];
        }

        return $app->json($countries);
    }

    /**
     * Fourni la liste des régions.
     */
    public function regionsAction(Request $request, Application $app)
    {
        $repoTerritoire = $app['orm.em']->getRepository(\App\Entity\Territoire::class);
        $territoires = $repoTerritoire->findRegions();

        $regions = [];
        foreach ($territoires as $territoire) {
            $regions[] = [
                'id' => $territoire->getId(),
                'geom' => $territoire->getGeojson(),
                'name' => $territoire->getNom(),
                'color' => $territoire->getColor(),
                'description' => strip_tags((string) $territoire->getDescription()),
                'groupes' => array_values($territoire->getGroupesPj()),
                'desordre' => $territoire->getStatutIndex(),
                'langue' => $territoire->getLanguePrincipale(),
            ];
        }

        return $app->json($regions);
    }

    /**
     * Fourni la liste des fiefs.
     */
    public function fiefsAction(Request $request, Application $app)
    {
        $repoTerritoire = $app['orm.em']->getRepository(\App\Entity\Territoire::class);
        $territoires = $repoTerritoire->findFiefs();

        $fiefs = [];
        foreach ($territoires as $territoire) {
            $fiefs[] = [
                'id' => $territoire->getId(),
                'geom' => $territoire->getGeojson(),
                'name' => $territoire->getNom(),
                'color' => $territoire->getColor(),
                'description' => strip_tags((string) $territoire->getDescription()),
                'groupes' => array_values($territoire->getGroupesPj()),
                'desordre' => $territoire->getStatutIndex(),
                'langue' => $territoire->getLanguePrincipale(),
            ];
        }

        return $app->json($fiefs);
    }

    /**
     * Fourni la liste des langues.
     */
    public function languesAction(Request $request, Application $app)
    {
        $langueList = $app['orm.em']->getRepository('\\'.\App\Entity\Langue::class)->findAll();

        $langues = [];
        foreach ($langueList as $langue) {
            if (0 == $langue->getDiffusion()) {
                continue;
            } // ne pas transmettre aux joueurs la liste des langues anciennes

            // construction du geojson de la langue (utilisation en tant que langue principale)
            $geojson = '{ "type": "FeatureCollection", "features": [';

            $first = true;
            $geoJsonPrincipalCount = 0;
            foreach ($langue->getTerritoires() as $territoire) {
                $geom = $territoire->getGeojson();

                if ($geom) {
                    if (!$first) {
                        $geojson .= ',';
                    }

                    $geojson .= $geom;
                    $first = false;
                    ++$geoJsonPrincipalCount;
                }
            }

            $geojson .= ']}';
            $geoJsonPrincipal = $geojson;

            // construction du geoJson de la langue (utilisation en tant que langue secondaire)
            $geojson = '{ "type": "FeatureCollection", "features": [';
            $first = true;
            $geoJsonSecondaireCount = 0;
            foreach ($langue->getTerritoireSecondaires() as $territoire) {
                $geom = $territoire->getGeojson();

                if ($geom) {
                    if (!$first) {
                        $geojson .= ',';
                    }

                    $geojson .= $geom;
                    $first = false;
                    ++$geoJsonSecondaireCount;
                }
            }

            $geojson .= ']}';
            $geoJsonSecondaire = $geojson;

            $langues[] = [
                'id' => $langue->getId(),
                'label' => $langue->getLabel(),
                'description' => $langue->getDescription(),
                'diffusion' => $langue->getDiffusion(),
                'geomPrincipal' => $geoJsonPrincipal,
                'geomSecondaire' => $geoJsonSecondaire,
                'geomPrincipalCount' => $geoJsonPrincipalCount,
                'geomSecondaireCount' => $geoJsonSecondaireCount,
            ];
        }

        return $app->json($langues);
    }

    /**
     * Fourni la liste des groupes.
     */
    public function groupesAction(Request $request, Application $app)
    {
        // recherche le prochain GN
        $gnRepo = $app['orm.em']->getRepository('\\'.\App\Entity\Gn::class);
        $gn = $gnRepo->findNext();

        $groupeGnList = $gn->getGroupeGns();

        $groupes = [];
        foreach ($groupeGnList as $groupeGn) {
            $groupe = $groupeGn->getGroupe();
            if (true === $groupe->getPj()) {
                $geom = null;
                if ($groupe->getTerritoires()->count() > 0) {
                    $territoire = $groupe->getTerritoires()->first();
                    $geom = $territoire->getGeojson();
                }

                // mettre en surbrillance le groupe de l'utilisateur
                $highlight = false;
                if ($this->getUser()) {
                    foreach ($this->getUser()->getParticipants() as $participant) {
                        if ($participant->getGn() == $gn && $participant->getGroupeGn()) {
                            if ($participant->getGroupeGn()->getGroupe() == $groupe) {
                                $highlight = true;
                                break;
                            }
                        }
                    }
                }

                $groupes[] = [
                    'id' => $groupe->getId(),
                    'nom' => $groupe->getNom(),
                    'geom' => $geom,
                    'highlight' => $highlight,
                ];
            }
        }

        return $app->json($groupes);
    }

    /**
     * Met à jour la geographie d'un pays.
     */
    public function updateCountryGeomAction(Request $request, Application $app)
    {
        $territoire = $request->get('territoire');
        $geom = $request->get('geom');
        $color = $request->get('color');

        $territoire->setGeojson($geom);
        $territoire->setColor($color);

        $app['orm.em']->persist($territoire);
        $app['orm.em']->flush();

        $country = [
            'id' => $territoire->getId(),
            'geom' => $territoire->getGeojson(),
            'name' => $territoire->getNom(),
            'description' => strip_tags((string) $territoire->getDescription()),
            'groupes' => array_values($territoire->getGroupesPj()),
        ];

        return $app->json($country);
    }

    /**
     * Affiche une page récapitulatif des liens pour discuter.
     */
    public function discuterAction(Request $request, Application $app)
    {
        return $app['twig']->render('public/discuter.twig');
    }

    /**
     * Affiche une page récapitulatif des événements.
     */
    public function evenementAction(Request $request, Application $app)
    {
        return $app['twig']->render('public/evenement.twig');
    }

    /**
     * Affiche les mentions légales.
     */
    #[Route('/legal', name: 'legal')]
    public function legalAction(Request $request, Application $app)
    {
        return $app['twig']->render('homepage/legal.twig');
    }

    /**
     * Affiche les informations de dev.
     */
    #[Route('/dev', name: 'dev')]
    public function devAction(Request $request, Application $app)
    {
        return $app['twig']->render('homepage/dev.twig');
    }

    /**
     * Statistiques du projet.
     */
    public function metricsAction(Request $request, Application $app)
    {
        return $app['twig']->render('homepage/metrics/report.html');
    }
}
