<?php

namespace App\Controller;

use LarpManager\Form\JoueurForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\JoueurController.
 *
 * @author kevin
 */
class JoueurController
{
    /**
     * Affiche la vue index.twig.
     */
    public function indexAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\App\Entity\Joueur');
        $joueurs = $repo->findAll();

        return $app['twig']->render('joueur/index.twig', ['joueurs' => $joueurs]);
    }

    /**
     * Affiche le formulaire d'ajout d'un joueur.
     */
    public function addAction(Request $request, Application $app)
    {
        $joueur = new \App\Entity\Joueur();

        $form = $app['form.factory']->createBuilder(new JoueurForm(), $joueur)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $joueur = $form->getData();
            $this->getUser()->setJoueur($joueur);

            $app['orm.em']->persist($this->getUser());
            $app['orm.em']->persist($joueur);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Vos informations ont été enregistrés.');

            return $app->redirect($app['url_generator']->generate('homepage'), 303);
        }

        return $app['twig']->render('joueur/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'un joueur.
     */
    public function detailAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $joueur = $app['orm.em']->find('\App\Entity\Joueur', $id);

        if ($joueur) {
            return $app['twig']->render('joueur/detail.twig', ['joueur' => $joueur]);
        } else {
            $app['session']->getFlashBag()->add('error', 'Le joueur n\'a pas été trouvé.');

            return $app->redirect($app['url_generator']->generate('joueur'));
        }
    }

    /**
     * Met à jour les informations d'un joueur.
     */
    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $joueur = $app['orm.em']->find('\App\Entity\Joueur', $id);

        $form = $app['form.factory']->createBuilder(new JoueurForm(), $joueur)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $joueur = $form->getData();

            $app['orm.em']->persist($joueur);
            $app['orm.em']->flush();
            $app['session']->getFlashBag()->add('success', 'Le joueur a été mis à jour.');

            return $app->redirect($app['url_generator']->generate('joueur.detail', ['index' => $id]));
        }

        return $app['twig']->render('joueur/update.twig', [
            'joueur' => $joueur,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met a jours les points d'expérience des joueurs.
     */
    public function xpAction(Application $app, Request $request)
    {
        $repo = $app['orm.em']->getRepository('\App\Entity\Joueur');
        $joueurs = $repo->findAll();

        if ('POST' == $request->getMethod()) {
            $newXps = $request->get('xp');
            $explanation = $request->get('explanation');

            foreach ($joueurs as $joueur) {
                $personnage = $joueur->getPersonnage();
                if ($personnage->getXp() != $newXps[$joueur->getId()]) {
                    $oldXp = $personnage->getXp();
                    $gain = $newXps[$joueur->getId()] - $oldXp;

                    $personnage->setXp($newXps[$joueur->getId()]);
                    $app['orm.em']->persist($personnage);

                    // historique
                    $historique = new \App\Entity\ExperienceGain();
                    $historique->setExplanation($explanation);
                    $historique->setOperationDate(new \DateTime('NOW'));
                    $historique->setPersonnage($personnage);
                    $historique->setXpGain($gain);
                    $app['orm.em']->persist($historique);
                }
            }

            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Les points d\'expérience ont été sauvegardés');
        }

        return $app['twig']->render('joueur/xp.twig', [
            'joueurs' => $joueurs,
        ]);
    }
}
