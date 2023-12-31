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

use App\Entity\Classe;
use App\Repository\ClasseRepository;
use App\Form\Classe\ClasseForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

/**
 * LarpManager\Controllers\ClasseController.
 *
 * @author kevin
 */
class ClasseController extends AbstractController
{
    /**
     * Présentation des classes.
     */
    #[Route('/classe', name: 'classe')]
    public function indexAction(Request $request, ClasseRepository $classeRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10;

        $classes = $classeRepository->findPaginated($page, $limit);

        return $this->render(
            'classe\list.twig',
            [
                'classes' => $classes,
                'limit' => $limit,
                'page' => $page,
            ]
        );
    }

    /**
     * Ajout d'une classe.
     */
    #[Route('/classe/add', name: 'classe.add')]
    public function addAction(Request $request, ClasseRepository $classeRepository): Response
    {
        $classe = new \App\Entity\Classe();

        $form = $app['form.factory']->createBuilder(new ClasseForm(), $classe)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $classe = $form->getData();

            $entityManager->persist($classe);
            $entityManager->flush();

            $this->addFlash('success', 'La classe a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('classe', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('classe.add', [], 303);
            }
        }

        return $app['twig']->render('classe/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour d'une classe.
     */
    #[Route('/classe/{id}/update', name: 'classe.update')]
    public function updateAction(EntityManagerInterface $entityManager, Request $request, int $id)
    {
        $classe = $entityManager->getRepository(Classe::class)->find($id);
        
        $form = $this->createForm(ClasseForm::class, $classe)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
        ;

        $form->handleRequest($request);

        if ($form->isValid()) {
            $classe = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($classe);
                $entityManager->flush();
                $this->addFlash('success', 'La classe a été mise à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($classe);
                $entityManager->flush();
                $this->addFlash('success', 'La classe a été supprimée.');
            }

            //return $this->redirectToRoute('classe'));
            return $this->redirectToRoute('TODO', [], 303);
        }

        return $this->render('classe/update.twig', [
            'classe' => $classe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une classe.
     */
    #[Route('/classe/{id}', name: 'classe.detail')]
    public function detailAction(EntityManagerInterface $entityManager, int $id)
    {
        $classe = $entityManager->getRepository(Classe::class)->find($id);
        
        return $this->render('admin/classe/detail.twig', ['classe' => $classe]);
    }

    /**
     * Persos d'une classe.
     */
    #[Route('/classe/{id}/perso', name: 'classe.perso')]
    public function persoAction(EntityManagerInterface $entityManager, int $id)
    {
        $classe = $entityManager->getRepository(Classe::class)->find($id);

        return $this->render('admin/classe/perso.twig', ['classe' => $classe]);
    }
}
