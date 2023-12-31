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

use App\Entity\Item;
use App\Entity\Objet;
use LarpManager\Form\Item\ItemDeleteForm;
use LarpManager\Form\Item\ItemForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\ObjetController.
 *
 * @author kevin
 */
class ObjetController extends AbstractController
{
    /**
     * Présentation des objets de jeu.
     */
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Item::class);
        $items = $repo->findAll();

        return $app['twig']->render('admin/objet/index.twig', [
            'items' => $items,
        ]);
    }

    /**
     * Impression d'une etiquette.
     */
    public function printAction(Request $request, Application $app, Item $item)
    {
        return $app['twig']->render('admin/objet/print.twig', [
            'item' => $item,
        ]);
    }

    /**
     * Impression de toutes les etiquettes.
     */
    public function printAllAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Item::class);
        $items = $repo->findAll();

        return $app['twig']->render('admin/objet/printAll.twig', [
            'items' => $items,
        ]);
    }

    /**
     * Impression de tous les objets avec photo.
     */
    public function printPhotoAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Item::class);
        $items = $repo->findAll();

        return $app['twig']->render('admin/objet/printPhoto.twig', [
            'items' => $items,
        ]);
    }

    /**
     * Sortie CSV.
     */
    public function printCsvAction(Request $request, Application $app): void
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Item::class);
        $items = $repo->findAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_objets_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv($output,
            [
                'numéro',
                'identification',
                'label',
                'description',
                'special',
                'groupe',
                'personnage',
                'rangement',
                'proprietaire',
            ], ';');

        foreach ($items as $item) {
            $line = [];
            $line[] = mb_convert_encoding((string) $item->getNumero(), 'ISO-8859-1');
            $line[] = mb_convert_encoding($item->getQuality()->getNumero().$item->getIdentification(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $item->getlabel(), 'ISO-8859-1');
            $line[] = mb_convert_encoding(html_entity_decode(strip_tags((string) $item->getDescription())), 'ISO-8859-1');
            $line[] = mb_convert_encoding(html_entity_decode(strip_tags((string) $item->getSpecial())), 'ISO-8859-1');

            $groupes = '';
            foreach ($item->getGroupes() as $groupe) {
                $groupes = $groupe->getNom().', ';
            }

            $line[] = mb_convert_encoding($groupes, 'ISO-8859-1');

            $personnages = '';
            foreach ($item->getPersonnages() as $personnage) {
                $personnages = $personnage->getNom().', ';
            }

            $line[] = mb_convert_encoding($personnages, 'ISO-8859-1');

            $objet = $item->getObjet();
            if ($objet) {
                $line[] = $objet->getRangement() ? mb_convert_encoding((string) $objet->getRangement()->getAdresse(), 'ISO-8859-1') : '';

                $line[] = $objet->getProprietaire() ? mb_convert_encoding((string) $objet->getProprietaire()->getNom(), 'ISO-8859-1') : '';
            } else {
                $line[] = '';
                $line[] = '';
            }

            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Création d'un nouvel objet de jeu.
     */
    public function newAction(Request $request, Application $app, Objet $objet)
    {
        $item = new Item();
        $item->setObjet($objet);

        $form = $app['form.factory']->createBuilder(new ItemForm(), $item)->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $item = $form->getData();

            // si le numéro est vide, générer un numéro en suivant l'ordre
            $numero = $item->getNumero();
            if (!$numero) {
                $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Item::class);
                $numero = $repo->findNextNumero();
                if (!$numero) {
                    $numero = 0;
                }

                $item->setNumero($numero);
            }

            // en fonction de l'identification choisie, choisir un numéro d'identification
            $identification = $item->getIdentification();
            switch ($identification) {
                case 1:
                    $identification = sprintf('%02d', mt_rand(1, 10));
                    $item->setIdentification($identification);
                    break;
                case 11:
                    $identification = mt_rand(11, 20);
                    $item->setIdentification($identification);
                    break;
                case 81:
                    $identification = mt_rand(81, 99);
                    $item->setIdentification($identification);
                    break;
            }

            $app['orm.em']->persist($item);
            $app['orm.em']->flush();

           $this->addFlash('success', 'L\'objet de jeu a été créé');

            return $this->redirectToRoute('items', [], 303);
        }

        return $app['twig']->render('admin/objet/new.twig', [
            'objet' => $objet,
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'un objet de jeu.
     */
    public function detailAction(Request $request, Application $app, Item $item)
    {
        return $app['twig']->render('admin/objet/detail.twig', [
            'item' => $item,
        ]);
    }

    /**
     * Mise à jour d'un objet de jeu.
     */
    public function updateAction(Request $request, Application $app, Item $item)
    {
        $form = $app['form.factory']->createBuilder(new ItemForm(), $item)->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $app['orm.em']->persist($item);
            $app['orm.em']->flush();

            // en fonction de l'identification choisie, choisir un numéro d'identification
            $identification = $item->getIdentification();
            switch ($identification) {
                case 1:
                    $identification = sprintf('%02d', mt_rand(1, 10));
                    $item->setIdentification($identification);
                    break;
                case 11:
                    $identification = mt_rand(11, 20);
                    $item->setIdentification($identification);
                    break;
                case 81:
                    $identification = mt_rand(81, 99);
                    $item->setIdentification($identification);
                    break;
            }

           $this->addFlash('success', 'L\'objet de jeu a été sauvegardé');

            return $this->redirectToRoute('items', [], 303);
        }

        return $app['twig']->render('admin/objet/update.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un objet de jeu.
     */
    public function deleteAction(Request $request, Application $app, Item $item)
    {
        $form = $app['form.factory']->createBuilder(new ItemDeleteForm(), $item)->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $app['orm.em']->remove($item);
            $app['orm.em']->flush();

           $this->addFlash('success', 'L\'objet de jeu a été supprimé');

            return $this->redirectToRoute('items', [], 303);
        }

        return $app['twig']->render('admin/objet/delete.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Lier un objet de jeu à un groupe/personnage/lieu.
     */
    public function linkAction(Request $request, Application $app, Item $item)
    {
        $form = $app['form.factory']->createBuilder(new ItemLinkForm(), $item)->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $app['orm.em']->persist($item);
            $app['orm.em']->flush();

           $this->addFlash('success', 'L\'objet de jeu a été créé');

            return $this->redirectToRoute('objet', [], 303);
        }

        return $app['twig']->render('admin/objet/link.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }
}
