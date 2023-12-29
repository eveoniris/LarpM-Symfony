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

use App\Entity\Question;
use LarpManager\Form\Question\QuestionDeleteForm;
use LarpManager\Form\Question\QuestionForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController
{
    /**
     * Liste des question.
     */
    #[Route('/question', name: 'question')]
    public function indexAction(Request $request, Application $app)
    {
        $questions = $app['orm.em']->getRepository(\App\Entity\Question::class)->findAll();

        return $app['twig']->render('admin\question\index.twig', [
            'questions' => $questions,
        ]);
    }

    /**
     * Ajout d'une question.
     */
    public function addAction(Request $request, Application $app)
    {
        $form = $app['form.factory']->createBuilder(new QuestionForm(), new Question())->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $question = $form->getData();
            $question->setUser($this->getUser());
            $question->setDate(new \DateTime('NOW'));

            $app['orm.em']->persist($question);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La question a été ajoutée.');

            return $app->redirect($app['url_generator']->generate('question'), 303);
        }

        return $app['twig']->render('admin\question\add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une question.
     */
    public function detailAction(Request $request, Application $app, Question $question)
    {
        return $app['twig']->render('admin\question\detail.twig', [
            'question' => $question,
        ]);
    }

    /**
     * Mise à jour d'une question.
     */
    public function updateAction(Request $request, Application $app, Question $question)
    {
        $form = $app['form.factory']->createBuilder(new QuestionForm(), $question)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $question = $form->getData();

            $app['orm.em']->persist($question);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La question a été mise à jour.');

            return $app->redirect($app['url_generator']->generate('question'), 303);
        }

        return $app['twig']->render('admin\question\update.twig', [
            'form' => $form->createView(),
            'question' => $question,
        ]);
    }

    /**
     * Suppression d'une question.
     */
    public function deleteAction(Request $request, Application $app, Question $question)
    {
        $form = $app['form.factory']->createBuilder(new QuestionDeleteForm(), $question)
            ->add('submit', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $question = $form->getData();

            foreach ($question->getReponses() as $reponse) {
                $app['orm.em']->remove($reponse);
            }

            $app['orm.em']->remove($question);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La question a été supprimée.');

            return $app->redirect($app['url_generator']->generate('question'), 303);
        }

        return $app['twig']->render('admin\question\delete.twig', [
            'form' => $form->createView(),
            'question' => $question,
        ]);
    }
}
