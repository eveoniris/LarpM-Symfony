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

use LarpManager\Form\AttributeTypeForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\AttributeTypeController.
 *
 * @author kevin
 */
class AttributeTypeController extends AbstractController
{
    /**
     * Liste des types d'attribut.
     */
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\AttributeType::class);
        $attributes = $repo->findAllOrderedByLabel();

        return $app['twig']->render('admin/attributeType/index.twig', ['attributes' => $attributes]);
    }

    /**
     * Ajoute d'un attribut.
     */
    public function addAction(Request $request, Application $app)
    {
        $attributeType = new \App\Entity\AttributeType();

        $form = $app['form.factory']->createBuilder(new AttributeTypeForm(), $attributeType)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $attributeType = $form->getData();

            $app['orm.em']->persist($attributeType);
            $app['orm.em']->flush();

           $this->addFlash('success', 'Le type d\'attribut a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('attribute.type', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('attribute.type.add', [], 303);
            }
        }

        return $app['twig']->render('admin/attributeType/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un attribut.
     */
    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $attributeType = $app['orm.em']->find('\\'.\App\Entity\AttributeType::class, $id);

        $form = $app['form.factory']->createBuilder(new AttributeTypeForm(), $attributeType)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $attributeType = $form->getData();

            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($attributeType);
                $app['orm.em']->flush();
               $this->addFlash('success', 'La type d\'attribut a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($attributeType);
                $app['orm.em']->flush();
               $this->addFlash('success', 'Le type d\'attribut a été supprimé.');
            }

            return $this->redirectToRoute('attribute.type');
        }

        return $app['twig']->render('admin/attributeType/update.twig', [
            'attributeType' => $attributeType,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'un attribut.
     */
    public function detailAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $attributeType = $app['orm.em']->find('\\'.\App\Entity\AttributeType::class, $id);

        if ($attributeType) {
            return $app['twig']->render('admin/attributeType/detail.twig', ['attributeType' => $attributeType]);
        } else {
           $this->addFlash('error', 'La attribute type n\'a pas été trouvé.');

            return $this->redirectToRoute('attribute.type');
        }
    }
}
