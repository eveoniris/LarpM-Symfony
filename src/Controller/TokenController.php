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

use App\Entity\Token;
use LarpManager\Form\TokenDeleteForm;
use LarpManager\Form\TokenForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\TokenController.
 *
 * @author kevin
 */
class TokenController extends AbstractController
{
    /**
     * Liste des tokens.
     */
    public function listAction(Request $request, Application $app)
    {
        $tokens = $app['orm.em']->getRepository('\\'.\App\Entity\Token::class)->findAllOrderedByLabel();

        return $app['twig']->render('admin/token/list.twig', ['tokens' => $tokens]);
    }

    /**
     * Impression des tokens.
     */
    public function printAction(Request $request, Application $app)
    {
        $tokens = $app['orm.em']->getRepository('\\'.\App\Entity\Token::class)->findAllOrderedByLabel();

        return $app['twig']->render('admin/token/print.twig', ['tokens' => $tokens]);
    }

    /**
     * Téléchargement des tokens.
     */
    public function downloadAction(Request $request, Application $app): void
    {
        $tokens = $app['orm.em']->getRepository('\\'.\App\Entity\Token::class)->findAllOrderedByLabel();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_tokens_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv($output,
            [
                'id',
                'label',
                'tag',
                'description'], ';');

        foreach ($tokens as $token) {
            fputcsv($output, $token->getExportValue(), ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Ajouter un token.
     */
    public function addAction(Request $request, Application $app)
    {
        $token = new Token();

        $form = $app['form.factory']->createBuilder(new TokenForm(), $token)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $token = $form->getData();

            $app['orm.em']->persist($token);
            $app['orm.em']->flush($token);

           $this->addFlash('success', 'Le jeton a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('token.list', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('token.add', [], 303);
            }
        }

        return $app['twig']->render('admin/token/add.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'un token.
     */
    public function detailAction(Request $request, Application $app, Token $token)
    {
        return $app['twig']->render('admin/token/detail.twig', ['token' => $token]);
    }

    /**
     * Mise à jour d'un token.
     */
    public function updateAction(Request $request, Application $app, Token $token)
    {
        $form = $app['form.factory']->createBuilder(new TokenForm(), $token)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $token = $form->getData();

            $app['orm.em']->persist($token);
            $app['orm.em']->flush($token);

           $this->addFlash('success', 'Le jeton a été modifié.');

            return $this->redirectToRoute('token.list', [], 303);
        }

        return $app['twig']->render('admin/token/update.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un token.
     */
    public function deleteAction(Request $request, Application $app, Token $token)
    {
        $form = $app['form.factory']->createBuilder(new TokenDeleteForm(), $token)
            ->add('save', 'submit', ['label' => 'Supprimer', 'attr' => ['class' => 'btn-danger']])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $app['orm.em']->remove($token);
            $app['orm.em']->flush($token);

           $this->addFlash('success', 'Le jeton a été supprimé.');

            return $this->redirectToRoute('token.list', [], 303);
        }

        return $app['twig']->render('admin/token/delete.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }
}
