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

use App\Entity\Quality;
use Doctrine\Common\Collections\ArrayCollection;
use LarpManager\Form\Quality\QualityDeleteForm;
use LarpManager\Form\Quality\QualityForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\QualityController.
 *
 * @author kevin
 */
class QualityController extends AbstractController
{
    /**
     * Liste les qualitys.
     */
    public function listAction(Application $app, Request $request)
    {
        $qualities = $app['orm.em']->getRepository('\\'.\App\Entity\Quality::class)->findAll();

        return $app['twig']->render('admin/quality/list.twig', [
            'qualities' => $qualities,
        ]);
    }

    /**
     * Ajoute une quality.
     */
    public function addAction(Application $app, Request $request)
    {
        $form = $app['form.factory']->createBuilder(new QualityForm(), new Quality())
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $quality = $form->getData();

            /*
             * Pour toutes les valeurs de la qualité
             */
            foreach ($quality->getQualityValeurs() as $qualityValeur) {
                $qualityValeur->setQuality($quality);
            }

            $app['orm.em']->persist($quality);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La quality a été enregistrée.');

            return $this->redirectToRoute('quality', [], 303);
        }

        return $app['twig']->render('admin/quality/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une quality.
     */
    public function updateAction(Application $app, Request $request, Quality $quality)
    {
        $originalQualityValeurs = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets qualityValeur de la quality
         */
        foreach ($quality->getQualityValeurs() as $qualityValeur) {
            $originalQualityValeurs->add($qualityValeur);
        }

        $form = $app['form.factory']->createBuilder(new QualityForm(), $quality)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $quality = $form->getData();

            /*
             * Pour toutes les valeurs de la qualité
             */
            foreach ($quality->getQualityValeurs() as $qualityValeur) {
                $qualityValeur->setQuality($quality);
            }

            /*
             *  supprime la relation entre participantHasRestauration et le participant
             */
            foreach ($originalQualityValeurs as $qualityValeur) {
                if (false == $quality->getQualityValeurs()->contains($qualityValeur)) {
                    $app['orm.em']->remove($qualityValeur);
                }
            }

            $app['orm.em']->persist($quality);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La quality a été enregistrée.');

            return $this->redirectToRoute('quality', [], 303);
        }

        return $app['twig']->render('admin/quality/update.twig', [
            'quality' => $quality,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une quality.
     */
    public function deleteAction(Application $app, Request $request, Quality $quality)
    {
        $form = $app['form.factory']->createBuilder(new QualityDeleteForm(), $quality)
            ->add('submit', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $quality = $form->getData();
            $app['orm.em']->remove($quality);
            $app['orm.em']->flush();

           $this->addFlash('success', 'La quality a été supprimée.');

            return $this->redirectToRoute('quality', [], 303);
        }

        return $app['twig']->render('admin/quality/delete.twig', [
            'quality' => $quality,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Fourni le détail d'une quality.
     */
    public function detailAction(Application $app, Request $request, Quality $quality)
    {
        return $app['twig']->render('admin/quality/detail.twig', [
            'quality' => $quality,
        ]);
    }
}
