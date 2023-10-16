<?php

namespace App\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\GuildeController.
 */
class GuildeController
{
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\App\Entity\Guilde');
        $guildes = $repo->findAll();

        return $app['twig']->render('guilde/index.twig', ['guildes' => $guildes]);
    }

    public function addAction(Request $request, Application $app)
    {
        if ('POST' === $request->getMethod()) {
            $label = $request->get('label');
            $description = $request->get('description');

            $guilde = new \App\Entity\Guilde();

            $guilde->setLabel($label);
            $guilde->setDescription($description);

            $app['orm.em']->persist($guilde);
            $app['orm.em']->flush();

            return $app->redirect($app['url_generator']->generate('guilde_list'));
        }

        return $app['twig']->render('guilde/add.twig');
    }

    public function modifyAction(Request $request, Application $app)
    {
        $id = $request->get('index');
        $guilde = $app['orm.em']->find('\App\Entity\Guilde', $id);
        if (!$guilde) {
            return $app->redirect($app['url_generator']->generate('guilde_list'));
        }

        if ('POST' === $request->getMethod()) {
            $label = $request->get('label');
            $description = $request->get('description');

            $guilde->setLabel($label);
            $guilde->setDescription($description);

            $app['orm.em']->flush();

            return $app->redirect($app['url_generator']->generate('guilde_list'));
        }

        $repo = $app['orm.em']->getRepository('\App\Entity\Guilde');

        return $app['twig']->render('guilde/modify.twig', ['guilde' => $guilde]);
    }

    public function removeAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $guilde = $app['orm.em']->find('\App\Entity\Guilde', $id);

        if ($guilde) {
            if ('POST' === $request->getMethod()) {
                $app['orm.em']->remove($guilde);
                $app['orm.em']->flush();

                return $app->redirect($app['url_generator']->generate('guilde_list'));
            }

            return $app['twig']->render('chronologie/remove.twig', ['guilde' => $guilde]);
        } else {
            return $app->redirect($app['url_generator']->generate('guilde_list'));
        }
    }

    public function detailAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $guilde = $app['orm.em']->find('\App\Entity\Guilde', $id);

        if ($guilde) {
            return $app['twig']->render('guilde/detail.twig', ['guilde', $guilde]);
        } else {
            return $app->redirect($app['url_generator']->generate('guilde_list'));
        }
    }
}
