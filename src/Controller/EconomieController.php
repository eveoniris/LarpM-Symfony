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

use Doctrine\Common\Collections\ArrayCollection;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\EconomieController.
 *
 * @author kevin
 */
class EconomieController
{
    /**
     * Présentation des constructions.
     */
    public function indexAction(Request $request, Application $app)
    {
        /* calcul de la masse monétaire :
         * correspond à somme des revenus des groupes en jeu
         * les revenus d'un groupe provient des territoire qu'il contrôle
         *
         * calcul des ressources :
         * Les ressources en jeu correspond à la totalité des ressources
         * fourni par les territoires contrôlé par les groupes
         *
         **/

        $territoires = new ArrayCollection();
        $ressources = new ArrayCollection();
        $ingredients = new ArrayCollection();
        $constructions = new ArrayCollection();

        // recherche le prochain GN
        $gnRepo = $app['orm.em']->getRepository('\\'.\App\Entity\Gn::class);
        $gn = $gnRepo->findNext();

        $groupeRepo = $app['orm.em']->getRepository('\\'.\App\Entity\Groupe::class);
        $groupes = $groupeRepo->findAll();

        $masseMonetaire = 0;

        foreach ($groupes as $groupe) {
            //  les groupes doivent participer au prochain GN
            if ($groupe->getGroupeGnById($gn->getId())) {
                foreach ($groupe->getTerritoires() as $territoire) {
                    $territoires[] = $territoire;
                }

                if ($groupe->getRichesse()) {
                    $masseMonetaire += $groupe->getRichesse();
                }

                foreach ($groupe->getGroupeHasRessources() as $groupeHasRessource) {
                    if ($ressources->containsKey($groupeHasRessource->getRessource()->getId())) {
                        $value = $ressources->get($groupeHasRessource->getRessource()->getId());
                        $value['nombre'] += $groupeHasRessource->getQuantite();
                    } else {
                        $ressources->set($groupeHasRessource->getRessource()->getId(), [
                            'label' => $groupeHasRessource->getRessource()->getLabel(),
                            'nombre' => $groupeHasRessource->getQuantite(),
                            'territoires' => [],
                        ]);
                    }
                }

                foreach ($groupe->getGroupeHasIngredients() as $groupeHasIngredient) {
                    if ($ingredients->containsKey($groupeHasIngredient->getIngredient()->getId())) {
                        $value = $ingredients->get($groupeHasIngredient->getIngredient()->getId());
                        $value['nombre'] += $groupeHasIngredient->getQuantite();
                    } else {
                        $ingredients->set($groupeHasIngredient->getIngredient()->getId(), [
                            'label' => $groupeHasIngredient->getIngredient()->getLabel(),
                            'nombre' => $groupeHasIngredient->getQuantite(),
                            'territoires' => [],
                        ]);
                    }
                }
            }
        }

        foreach ($territoires as $territoire) {
            $masseMonetaire += $territoire->getTresor();

            // récupération des ressources
            foreach ($territoire->getExportations() as $ressource) {
                if ($ressources->containsKey($ressource->getId())) {
                    $value = $ressources->get($ressource->getId());
                    ++$value['nombre'];
                    $value['territoires'][] = $territoire;

                    $ressources->set($ressource->getId(), $value);
                } else {
                    $ressources->set($ressource->getId(), [
                        'label' => $ressource->getLabel(),
                        'nombre' => 1,
                        'territoires' => [$territoire],
                    ]);
                }
            }

            // classement des résultats par ordre décroissant
            $iterator = $ressources->getIterator();
            $iterator->uasort(static function (array $first, array $second): int {
                return (int) $first['nombre'] < (int) $second['nombre'] ? 1 : -1;
            });
            $ressources = new ArrayCollection(iterator_to_array($iterator));

            // récupération des ingrédients
            foreach ($territoire->getIngredients() as $ingredient) {
                if ($ingredients->containsKey($ingredient->getId())) {
                    $value = $ingredients->get($ingredient->getId());
                    ++$value['nombre'];
                    $value['territoires'][] = $territoire;

                    $ingredients->set($ingredient->getId(), $value);
                } else {
                    $ingredients->set($ingredient->getId(), [
                        'label' => $ingredient->getLabel(),
                        'nombre' => 1,
                        'territoires' => [$territoire],
                    ]);
                }
            }

            // classement des résultats par ordre décroissant
            $iterator = $ingredients->getIterator();
            $iterator->uasort(static function (array $first, array $second): int {
                return (int) $first['nombre'] < (int) $second['nombre'] ? 1 : -1;
            });
            $ingredients = new ArrayCollection(iterator_to_array($iterator));

            // récupération des constructions
            foreach ($territoire->getConstructions() as $construction) {
                if ($constructions->containsKey($construction->getId())) {
                    $value = $constructions->get($construction->getId());
                    ++$value['nombre'];
                    $value['territoires'][] = $territoire;

                    $constructions->set($construction->getId(), $value);
                } else {
                    $constructions->set($construction->getId(), [
                        'label' => $construction->getLabel(),
                        'nombre' => 1,
                        'territoires' => [$territoire],
                    ]);
                }
            }

            // classement des résultats par ordre décroissant
            $iterator = $constructions->getIterator();
            $iterator->uasort(static function (array $first, array $second): int {
                return (int) $first['nombre'] < (int) $second['nombre'] ? 1 : -1;
            });
            $constructions = new ArrayCollection(iterator_to_array($iterator));
        }

        return $app['twig']->render('admin/economie/index.twig', [
            'gn' => $gn,
            'masseMonetaire' => $masseMonetaire,
            'ressources' => $ressources,
            'ingredients' => $ingredients,
            'constructions' => $constructions,
        ]);
    }

    /**
     * Sortie du fichier pour le jeu économique.
     */
    public function csvAction(Request $request, Application $app): void
    {
        $territoires = $app['orm.em']->getRepository('\\'.\App\Entity\Territoire::class)->findFiefs();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_economie_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        fputcsv($output,
            [
                mb_convert_encoding('fief', 'ISO-8859-1'),
                mb_convert_encoding('groupe', 'ISO-8859-1'),
                mb_convert_encoding('Statut', 'ISO-8859-1'),
                mb_convert_encoding('Constructions', 'ISO-8859-1'),
                mb_convert_encoding('Constructions ajoutées', 'ISO-8859-1'),
                mb_convert_encoding('Ressources', 'ISO-8859-1'),
                mb_convert_encoding('Ingrédients', 'ISO-8859-1'),
                mb_convert_encoding('Or', 'ISO-8859-1'),
                mb_convert_encoding('Distribution', 'ISO-8859-1'),
            ], ';');

        foreach ($territoires as $territoire) {
            $line = [];
            $line[] = mb_convert_encoding((string) $territoire->getNom(), 'ISO-8859-1');
            $groupe = $territoire->getGroupe();
            $line[] = $groupe ? mb_convert_encoding('#'.$groupe->getNumero().' '.$groupe->getNom(), 'ISO-8859-1') : mb_convert_encoding('Aucun', 'ISO-8859-1');

            $line[] = mb_convert_encoding((string) $territoire->getStatut(), 'ISO-8859-1');
            $line[] = mb_convert_encoding(implode(' - ', $territoire->getConstructions()->toArray()), 'ISO-8859-1');
            $line[] = '';
            $line[] = mb_convert_encoding(implode(' - ', $territoire->getExportations()->toArray()), 'ISO-8859-1');
            $line[] = mb_convert_encoding(implode(' - ', $territoire->getIngredients()->toArray()), 'ISO-8859-1');
            $line[] = mb_convert_encoding($territoire->getRichesse().' ('.$territoire->getTresor().')', 'ISO-8859-1');
            $line[] = '';

            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }
}
