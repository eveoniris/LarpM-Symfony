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

use App\Entity\Document;
use App\Entity\Groupe;
use App\Entity\GroupeHasIngredient;
use App\Entity\GroupeHasRessource;
use App\Entity\Personnage;
use Doctrine\Common\Collections\ArrayCollection;
use JasonGrimes\Paginator;
use LarpManager\Form\BackgroundForm;
use LarpManager\Form\Groupe\GroupeCompositionForm;
use LarpManager\Form\Groupe\GroupeDescriptionForm;
use LarpManager\Form\Groupe\GroupeDocumentForm;
use LarpManager\Form\Groupe\GroupeEnvelopeForm;
use LarpManager\Form\Groupe\GroupeForm;
use LarpManager\Form\Groupe\GroupeIngredientForm;
use LarpManager\Form\Groupe\GroupeItemForm;
use LarpManager\Form\Groupe\GroupeRessourceForm;
use LarpManager\Form\Groupe\GroupeRichesseForm;
use LarpManager\Form\Groupe\GroupeScenaristeForm;
use LarpManager\Form\Groupe\GroupFindForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\GroupeController.
 *
 * @author kevin
 */
class GroupeController
{
    /**
     * Modifier la composition du groupe.
     */
    public function compositionAction(Request $request, Application $app, Groupe $groupe)
    {
        $originalGroupeClasses = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets GroupeClasse du groupe
         */
        foreach ($groupe->getGroupeClasses() as $groupeClasse) {
            $originalGroupeClasses->add($groupeClasse);
        }

        $form = $app['form.factory']->createBuilder(new GroupeCompositionForm(), $groupe)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupe = $form->getData();

            /*
             * Pour toutes les classes du groupe
             */
            foreach ($groupe->getGroupeClasses() as $groupeClasses) {
                $groupeClasses->setGroupe($groupe);
            }

            /*
             *  supprime la relation entre le groupeClasse et le groupe
             */
            foreach ($originalGroupeClasses as $groupeClasse) {
                if (false == $groupe->getGroupeClasses()->contains($groupeClasse)) {
                    $app['orm.em']->remove($groupeClasse);
                }
            }

            $app['orm.em']->persist($groupe);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La composition du groupe a été sauvegardé.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/composition.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modification de la description du groupe.
     */
    public function descriptionAction(Request $request, Application $app, Groupe $groupe)
    {
        $form = $app['form.factory']->createBuilder(new GroupeDescriptionForm(), $groupe)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupe = $form->getData();
            $app['orm.em']->persist($groupe);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La description du groupe a été sauvegardé.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/description.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Choix du scenariste.
     */
    public function scenaristeAction(Request $request, Application $app, Groupe $groupe)
    {
        $form = $app['form.factory']->createBuilder(new GroupeScenaristeForm(), $groupe)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupe = $form->getData();
            $app['orm.em']->persist($groupe);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le groupe a été sauvegardé.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/scenariste.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * fourni le tableau de quête pour tous les groupes.
     */
    public function quetesAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Groupe::class);
        $groupes = $repo->findAllOrderByNumero();
        $ressourceRares = new ArrayCollection($app['orm.em']->getRepository('\\'.\App\Entity\Ressource::class)->findRare());
        $ressourceCommunes = new ArrayCollection($app['orm.em']->getRepository('\\'.\App\Entity\Ressource::class)->findCommun());

        $quetes = new ArrayCollection();
        $stats = [];

        foreach ($groupes as $groupe) {
            $quete = $app['larp.manager']->generateQuete($groupe, $ressourceCommunes, $ressourceRares);
            $quetes[] = [
                'quete' => $quete,
                'groupe' => $groupe,
            ];
            foreach ($quete['needs'] as $ressources) {
                if (isset($stats[$ressources->getLabel()])) {
                    ++$stats[$ressources->getLabel()];
                } else {
                    $stats[$ressources->getLabel()] = 1;
                }
            }
        }

        if ($request->get('csv')) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename=eveoniris_quetes_'.date('Ymd').'.csv');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');

            // header
            fputcsv($output,
                [
                    'nom',
                    'pays',
                    'res 1',
                    'res 2',
                    'res 3',
                    'res 4',
                    'res 5',
                    'res 6',
                    'res 7',
                    'Départ',
                    'Arrivée',
                    'recompense 1',
                    'recompense 2',
                    'recompense 3',
                    'recompense 4',
                    'recompense 5',
                    'description'], ';');

            foreach ($quetes as $quete) {
                $line = [];
                $line[] = mb_convert_encoding('#'.$quete['groupe']->getNumero().' '.$quete['groupe']->getNom(), 'ISO-8859-1');
                $line[] = $quete['groupe']->getTerritoire() ? mb_convert_encoding((string) $quete['groupe']->getTerritoire()->getNom(), 'ISO-8859-1') : '';

                foreach ($quete['quete']['needs'] as $ressources) {
                    $line[] = mb_convert_encoding((string) $ressources->getLabel(), 'ISO-8859-1');
                }

                $line[] = '';
                $line[] = '';
                foreach ($quete['quete']['recompenses'] as $recompense) {
                    $line[] = mb_convert_encoding((string) $recompense, 'ISO-8859-1');
                }

                $line[] = '';
                fputcsv($output, $line, ';');
            }

            fclose($output);
            exit;
        }

        return $app['twig']->render('admin/groupe/quetes.twig', [
            'quetes' => $quetes,
            'stats' => $stats,
        ]);
    }

    /**
     * Générateur de quêtes commerciales.
     */
    public function queteAction(Request $request, Application $app)
    {
        $groupe = $request->get('groupe');
        $ressourceRares = new ArrayCollection($app['orm.em']->getRepository('\\'.\App\Entity\Ressource::class)->findRare());
        $ressourceCommunes = new ArrayCollection($app['orm.em']->getRepository('\\'.\App\Entity\Ressource::class)->findCommun());
        $quete = $app['larp.manager']->generateQuete($groupe, $ressourceCommunes, $ressourceRares);

        return $app['twig']->render('admin/groupe/quete.twig', [
            'groupe' => $groupe,
            'needs' => $quete['needs'],
            'valeur' => $quete['valeur'],
            'px' => $quete['px'],
            'recompenses' => $quete['recompenses'],
        ]);
    }

    /**
     * Modifie les ingredients du groupe.
     */
    public function adminIngredientAction(Request $request, Application $app, Groupe $groupe)
    {
        $originalGroupeHasIngredients = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets GroupeHasIngredient du groupe
         */
        foreach ($groupe->getGroupeHasIngredients() as $groupeHasIngredient) {
            $originalGroupeHasIngredients->add($groupeHasIngredient);
        }

        $form = $app['form.factory']->createBuilder(new GroupeIngredientForm(), $groupe)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupe = $form->getData();

            /*
             * Pour tous les ingredients
             */
            foreach ($groupe->getGroupeHasIngredients() as $groupeHasIngredient) {
                $groupeHasIngredient->setGroupe($groupe);
            }

            /*
             *  supprime la relation entre groupeHasIngredient et le groupe
             */
            foreach ($originalGroupeHasIngredients as $groupeHasIngredient) {
                if (false == $groupe->getGroupeHasIngredients()->contains($groupeHasIngredient)) {
                    $app['orm.em']->remove($groupeHasIngredient);
                }
            }

            $random = $form['random']->getData();

            /*
             *  Gestion des ressources communes alloués au hasard
             */
            if ($random && $random > 0) {
                $ingredients = $app['orm.em']->getRepository(\App\Entity\Ingredient::class)->findAll();
                shuffle($ingredients);
                $needs = new ArrayCollection(array_slice($ingredients, 0, $random));

                foreach ($needs as $ingredient) {
                    $ghi = new GroupeHasIngredient();
                    $ghi->setIngredient($ingredient);
                    $ghi->setQuantite(1);
                    $ghi->setGroupe($groupe);
                    $app['orm.em']->persist($ghi);
                }
            }

            $app['orm.em']->persist($groupe);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Votre groupe a été sauvegardé.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/ingredient.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie les ressources du groupe.
     */
    public function adminRessourceAction(Request $request, Application $app, Groupe $groupe)
    {
        $originalGroupeHasRessources = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets GroupeHasRessource du groupe
         */
        foreach ($groupe->getGroupeHasRessources() as $groupeHasRessource) {
            $originalGroupeHasRessources->add($groupeHasRessource);
        }

        $form = $app['form.factory']->createBuilder(new GroupeRessourceForm(), $groupe)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupe = $form->getData();

            /*
             * Pour toutes les ressources du groupe
             */
            foreach ($groupe->getGroupeHasRessources() as $groupeHasRessource) {
                $groupeHasRessource->setGroupe($groupe);
            }

            /*
             *  supprime la relation entre groupeHasRessource et le groupe
             */
            foreach ($originalGroupeHasRessources as $groupeHasRessource) {
                if (false == $groupe->getGroupeHasRessources()->contains($groupeHasRessource)) {
                    $app['orm.em']->remove($groupeHasRessource);
                }
            }

            $randomCommun = $form['randomCommun']->getData();

            /*
             *  Gestion des ressources communes alloués au hasard
             */
            if ($randomCommun && $randomCommun > 0) {
                $ressourceCommune = $app['orm.em']->getRepository(\App\Entity\Ressource::class)->findCommun();
                shuffle($ressourceCommune);
                $needs = new ArrayCollection(array_slice($ressourceCommune, 0, $randomCommun));

                foreach ($needs as $ressource) {
                    $ghr = new GroupeHasRessource();
                    $ghr->setRessource($ressource);
                    $ghr->setQuantite(1);
                    $ghr->setGroupe($groupe);
                    $app['orm.em']->persist($ghr);
                }
            }

            $randomRare = $form['randomRare']->getData();

            /*
             *  Gestion des ressources rares alloués au hasard
             */
            if ($randomRare && $randomRare > 0) {
                $ressourceRare = $app['orm.em']->getRepository(\App\Entity\Ressource::class)->findRare();
                shuffle($ressourceRare);
                $needs = new ArrayCollection(array_slice($ressourceRare, 0, $randomRare));

                foreach ($needs as $ressource) {
                    $ghr = new GroupeHasRessource();
                    $ghr->setRessource($ressource);
                    $ghr->setQuantite(1);
                    $ghr->setGroupe($groupe);
                    $app['orm.em']->persist($ghr);
                }
            }

            $app['orm.em']->persist($groupe);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Votre groupe a été sauvegardé.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/ressource.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * AModifie la richesse du groupe.
     */
    public function adminRichesseAction(Request $request, Application $app, Groupe $groupe)
    {
        $form = $app['form.factory']->createBuilder(new GroupeRichesseForm(), $groupe)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupe = $form->getData();
            $app['orm.em']->persist($groupe);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Votre groupe a été sauvegardé.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/richesse.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un document dans le matériel du groupe.
     */
    public function adminDocumentAction(Request $request, Application $app, Groupe $groupe)
    {
        $form = $app['form.factory']->createBuilder(new GroupeDocumentForm(), $groupe)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupe = $form->getData();
            $app['orm.em']->persist($groupe);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le document a été ajouté au groupe.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/documents.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gestion des objets du groupe.
     */
    public function adminItemAction(Request $request, Application $app, Groupe $groupe)
    {
        $form = $app['form.factory']->createBuilder(new GroupeItemForm(), $groupe)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupe = $form->getData();
            $app['orm.em']->persist($groupe);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'L\'objet a été ajouté au groupe.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/items.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gestion de l'enveloppe de groupe.
     */
    public function envelopeAction(Request $request, Application $app, Groupe $groupe)
    {
        $form = $app['form.factory']->createBuilder(new GroupeEnvelopeForm(), $groupe)
            ->add('submit', 'submit', ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupe = $form->getData();
            $app['orm.em']->persist($groupe);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le groupe a été sauvegardé.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/envelope.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gestion des membres du groupe.
     */
    public function UsersAction(Request $request, Application $app, Groupe $groupe)
    {
        return $app['twig']->render('admin/groupe/Users.twig', [
            'groupe' => $groupe,
        ]);
    }

    /**
     * vérouillage d'un groupe.
     */
    public function lockAction(Request $request, Application $app, Groupe $groupe)
    {
        $groupe->setLock(true);
        $app['orm.em']->persist($groupe);
        $app['orm.em']->flush();

        $app['session']->getFlashBag()->add('success', 'Le groupe est verrouillé. Cela bloque la création et la modification des personnages membres de ce groupe');

        return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
    }

    /**
     * devérouillage d'un groupe.
     */
    public function unlockAction(Request $request, Application $app, Groupe $groupe)
    {
        $groupe->setLock(false);
        $app['orm.em']->persist($groupe);
        $app['orm.em']->flush();

        $app['session']->getFlashBag()->add('success', 'Le groupe est dévérouillé');

        return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
    }

    /**
     * rendre disponible un groupe.
     */
    public function availableAction(Request $request, Application $app, Groupe $groupe)
    {
        $groupe->setFree(true);
        $app['orm.em']->persist($groupe);
        $app['orm.em']->flush();

        $app['session']->getFlashBag()->add('success', 'Le groupe est maintenant disponible. Il pourra être réservé par un joueur');

        return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
    }

    /**
     * rendre indisponible un groupe.
     */
    public function unvailableAction(Request $request, Application $app, Groupe $groupe)
    {
        $groupe->setFree(false);
        $app['orm.em']->persist($groupe);
        $app['orm.em']->flush();

        $app['session']->getFlashBag()->add('success', 'Le groupe est maintenant réservé. Il ne pourra plus être réservé par un joueur');

        return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
    }

    /**
     * Lier un pays à un groupe.
     */
    public function paysAction(Request $request, Application $app)
    {
        $groupe = $request->get('groupe');

        $form = $app['form.factory']->createBuilder()
            ->add('territoire', 'entity', [
                'required' => true,
                'class' => \App\Entity\Territoire::class,
                'property' => 'nomComplet',
                'label' => 'choisissez le territoire',
                'expanded' => true,
                'query_builder' => static function (\LarpManager\Repository\TerritoireRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->andWhere($qb->expr()->isNull('t.territoire'));
                    $qb->orderBy('t.nom', 'ASC');

                    return $qb;
                },
            ])
            ->add('add', 'submit', ['label' => 'Ajouter le territoire'])->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $territoire = $data['territoire'];

            $groupe->setTerritoire($territoire);

            $app['orm.em']->persist($groupe);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le groupe est lié au pays');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/pays.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajout d'un territoire sous le controle du groupe.
     */
    public function territoireAddAction(Request $request, Application $app)
    {
        $groupe = $request->get('groupe');

        $form = $app['form.factory']->createBuilder()
            ->add('territoire', 'entity', [
                'required' => true,
                'class' => \App\Entity\Territoire::class,
                'property' => 'nomComplet',
                'label' => 'choisissez le territoire',
                'expanded' => true,
                'query_builder' => static function (\LarpManager\Repository\TerritoireRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->orderBy('t.nom', 'ASC');

                    return $qb;
                },
            ])
            ->add('add', 'submit', ['label' => 'Ajouter le territoire'])->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $territoire = $data['territoire'];

            $territoire->setGroupe($groupe);

            $app['orm.em']->persist($territoire);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le territoire est contrôlé par le groupe');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/addTerritoire.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retirer un territoire du controle du groupe.
     */
    public function territoireRemoveAction(Request $request, Application $app)
    {
        $groupe = $request->get('groupe');
        $territoire = $request->get('territoire');

        $form = $app['form.factory']->createBuilder()
            ->add('remove', 'submit', ['label' => 'Retirer le territoire'])->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $territoire->setGroupeNull();

            $app['orm.em']->persist($territoire);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le territoire n\'est plus controlé par le groupe');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/removeTerritoire.twig', [
            'groupe' => $groupe,
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gestion de la restauration d'un groupe.
     */
    public function restaurationAction(Request $request, Application $app)
    {
        $groupe = $request->get('groupe');
        $availableTaverns = $app['larp.manager']->getAvailableTaverns();

        $formBuilder = $app['form.factory']->createBuilder();

        $participants = $groupe->getParticipants();

        $iterator = $participants->getIterator();
        $iterator->uasort(static function ($first, $second): int {
            if ($first === $second) {
                return 0;
            }

            return $first->getUser()->getEtatCivil()->getNom() < $second->getUser()->getEtatCivil()->getNom() ? -1 : 1;
        });
        $participants = new ArrayCollection(iterator_to_array($iterator));

        foreach ($participants as $participant) {
            $formBuilder->add($participant->getId(), 'choice', [
                'label' => $participant->getUser()->getEtatCivil()->getNom().' '.$participant->getUser()->getEtatCivil()->getPrenom().' '.$participant->getUser()->getEmail(),
                'choices' => $availableTaverns,
                'data' => $participant->getTavernId(),
                'multiple' => false,
                'expanded' => false,
            ]);
        }

        $form = $formBuilder->add('save', 'submit', ['label' => 'Enregistrer'])->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $result = $form->getData();
            foreach ($result as $joueur => $tavern) {
                $j = $app['orm.em']->getRepository('\\'.\App\Entity\Participant::class)->find($joueur);
                if ($j && $j->getTavernId() != $tavern) {
                    $j->setTavernId($tavern);
                    $app['orm.em']->persist($j);
                }
            }

            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Les options de restauration sont enregistrés.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/restauration.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Impression matériel pour les personnages du groupe.
     */
    public function printMaterielAction(Request $request, Application $app)
    {
        $groupe = $request->get('groupe');

        // recherche les personnages du prochains GN membre du groupe
        $session = $groupe->getNextSession();
        $participants = $session->getParticipants();

        return $app['twig']->render('admin/groupe/printMateriel.twig', [
            'groupe' => $groupe,
            'participants' => $participants,
        ]);
    }

    /**
     * Impression background pour les personnages du groupe.
     */
    public function printBackgroundAction(Request $request, Application $app)
    {
        $groupe = $request->get('groupe');

        return $app['twig']->render('admin/groupe/printBackground.twig', [
            'groupe' => $groupe,
        ]);
    }

    /**
     * Imprimmer toutes les enveloppes de tous les groupes.
     */
    public function printAllAction(Request $request, Application $app)
    {
        $gn = $app['larp.manager']->getGnActif();
        $groupeGns = $gn->getGroupeGns();

        $ressourceRares = new ArrayCollection($app['orm.em']->getRepository('\\'.\App\Entity\Ressource::class)->findRare());
        $ressourceCommunes = new ArrayCollection($app['orm.em']->getRepository('\\'.\App\Entity\Ressource::class)->findCommun());

        $groupes = new ArrayCollection();
        foreach ($groupeGns as $groupeGn) {
            $groupe = $groupeGn->getGroupe();
            $quete = $app['larp.manager']->generateQuete($groupe, $ressourceCommunes, $ressourceRares);
            $groupes[] = [
                'groupe' => $groupe,
                'quete' => $quete,
            ];
        }

        return $app['twig']->render('admin/groupe/printAll.twig', [
            'groupes' => $groupes,
        ]);
    }

    /**
     * Impression matériel pour le groupe.
     */
    public function printMaterielGroupeAction(Request $request, Application $app)
    {
        $groupe = $request->get('groupe');

        // recherche les personnages du prochains GN membre du groupe
        $session = $groupe->getNextSession();
        $participants = $session->getParticipants();

        $ressourceRares = new ArrayCollection($app['orm.em']->getRepository('\\'.\App\Entity\Ressource::class)->findRare());
        $ressourceCommunes = new ArrayCollection($app['orm.em']->getRepository('\\'.\App\Entity\Ressource::class)->findCommun());
        $quete = $app['larp.manager']->generateQuete($groupe, $ressourceCommunes, $ressourceRares);

        return $app['twig']->render('admin/groupe/printMaterielGroupe.twig', [
            'groupe' => $groupe,
            'participants' => $participants,
            'quete' => $quete,
            'session' => $session,
        ]);
    }

    /**
     * Impression fiche de perso pour le groupe.
     */
    public function printPersoAction(Request $request, Application $app)
    {
        $groupe = $request->get('groupe');

        // recherche les personnages du prochains GN membre du groupe
        $session = $groupe->getNextSession();
        $participants = $session->getParticipants();
        $quetes = new ArrayCollection();

        $ressourceRares = new ArrayCollection($app['orm.em']->getRepository('\\'.\App\Entity\Ressource::class)->findRare());
        $ressourceCommunes = new ArrayCollection($app['orm.em']->getRepository('\\'.\App\Entity\Ressource::class)->findCommun());

        foreach ($participants as $participant) {
            $personnage = $participant->getPersonnage();
            if ($personnage && $personnage->hasCompetence('Commerce')) {
                $niveau = $personnage->getCompetenceNiveau('Commerce');
                if ($niveau >= 2) {
                    $quetes[] = [
                        'quete' => $app['larp.manager']->generateQuete($groupe, $ressourceCommunes, $ressourceRares),
                        'personnage' => $personnage,
                    ];
                }
            }
        }

        return $app['twig']->render('admin/groupe/printPerso.twig', [
            'groupe' => $groupe,
            'participants' => $participants,
            'quetes' => $quetes,
        ]);
    }

    /**
     * Visualisation des liens entre groupes.
     */
    public function diplomatieAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Groupe::class);
        $groupes = $repo->findBy(['pj' => true], ['nom' => 'ASC']);

        return $app['twig']->render('admin/diplomatie.twig', [
            'groupes' => $groupes,
        ]);
    }

    /**
     * Impression des liens entre groupes.
     */
    public function diplomatiePrintAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\GroupeAllie::class);
        $alliances = $repo->findByAlliances();
        $demandeAlliances = $repo->findByDemandeAlliances();

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\GroupeEnemy::class);
        $guerres = $repo->findByWar();
        $demandePaix = $repo->findByRequestPeace();

        return $app['twig']->render('admin/diplomatiePrint.twig', [
            'alliances' => $alliances,
            'demandeAlliances' => $demandeAlliances,
            'guerres' => $guerres,
            'demandePaix' => $demandePaix,
        ]);
    }

    /**
     * Liste des groupes.
     */
    public function adminListAction(Request $request, Application $app)
    {
        $order_by = $request->get('order_by') ?: 'numero';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $type = null;
        $value = null;

        $form = $app['form.factory']->createBuilder(new GroupFindForm())->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['search'];
        }

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Groupe::class);

        $groupes = $repo->findList(
            $type,
            $value,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset);

        $numResults = $repo->findCount($type, $value);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('groupe.admin.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $app['twig']->render('admin/groupe/list.twig', [
            'form' => $form->createView(),
            'groupes' => $groupes,
            'paginator' => $paginator,
        ]);
    }

    /**
     * Ajouter un participant dans un groupe.
     */
    public function adminParticipantAddAction(Request $request, Application $app)
    {
        $groupe = $request->get('groupe');

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Participant::class);

        // trouve tous les participants n'étant pas dans un groupe
        $participants = $repo->findAllByGroupeNull();

        // creation du formulaire
        $form = $app['form.factory']->createBuilder()
            ->add('participant', 'entity', [
                'label' => 'Choisissez le nouveau membre du groupe',
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'property' => 'UserIdentity',
                'class' => \App\Entity\Participant::class,
                'choices' => $participants,
            ])
            ->add('submit', 'submit', ['label' => 'Ajouter'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $participant = $data['participant'];

            $groupe->addParticipant($participant);
            $participant->setGroupe($groupe);
            // le personnage du joueur doit aussi changer de groupe
            if ($participant->getPersonnage()) {
                $personnage = $participant->getPersonnage();
                $personnage->setGroupe($groupe);
                $app['orm.em']->persist($personnage);
            }

            $app['orm.em']->persist($groupe);
            $app['orm.em']->persist($participant);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le participant a été ajouté au groupe.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/addParticipant.twig', [
            'groupe' => $groupe,
            'participants' => $participants,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retirer un participant du groupe.
     */
    public function adminParticipantRemoveAction(Request $request, Application $app)
    {
        $participantId = $request->get('participant');
        $groupe = $request->get('groupe');

        $participant = $app['orm.em']->find('\\'.\App\Entity\Participant::class, $participantId);

        if ($request->isMethod('POST')) {
            $personnage = $participant->getPersonnage();
            if ($personnage) {
                if ($personnage->getGroupe() == $groupe) {
                    $personnage->removeGroupe($groupe);
                }

                $app['orm.em']->persist($personnage);
            }

            $participant->removeGroupe($groupe);
            $app['orm.em']->persist($participant);
            $app['orm.em']->persist($groupe);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le participant a été retiré du groupe.');

            return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $groupe->getId()]));
        }

        return $app['twig']->render('admin/groupe/removeParticipant.twig', [
            'groupe' => $groupe,
            'participant' => $participant,
        ]);
    }

    /**
     * Recherche d'un groupe.
     */
    public function searchAction(Request $request, Application $app)
    {
        $form = $app['form.factory']->createBuilder(new FindGroupeForm(), [])
            ->add('submit', 'submit', ['label' => 'Rechercher'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $type = $data['type'];
            $search = $data['search'];

            $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Groupe::class);

            $groupes = null;

            switch ($type) {
                case 'label':
                    $groupes = $repo->findByName($search);
                    break;
                case 'numero':
                    $groupes = $repo->findByNumero($search);
                    break;
            }

            if (null != $joueurs) {
                if (1 == count($joueurs)) {
                    $app['session']->getFlashBag()->add('success', 'Le joueur a été trouvé.');

                    return $app->redirect($app['url_generator']->generate('joueur.detail', ['index' => $joueurs[0]]));
                } else {
                    $app['session']->getFlashBag()->add('success', 'Il y a plusieurs résultats à votre recherche.');

                    return $app['twig']->render('joueur/search_result.twig', [
                        'joueurs' => $joueurs,
                    ]);
                }
            }

            $app['session']->getFlashBag()->add('error', 'Désolé, le joueur n\'a pas été trouvé.');
        }

        return $app['twig']->render('joueur/search.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modification du nombre de place disponibles dans un groupe.
     */
    public function placeAction(Request $request, Application $app)
    {
        $id = $request->get('index');
        $groupe = $app['orm.em']->find('\\'.\App\Entity\Groupe::class, $id);

        if ('POST' == $request->getMethod()) {
            $newPlaces = $request->get('place');

            /*
             * Met à jour uniquement si la valeur à changé
             */
            if ($groupe->getClasseOpen() != $newPlaces) {
                $groupe->setClasseOpen($newPlaces);
                $app['orm.em']->persist($groupe);
            }

            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le nombre de place disponible a été mis à jour');

            return $app->redirect($app['url_generator']->generate('groupe.admin.list'), 303);
        }

        return $app['twig']->render('admin/groupe/place.twig', [
            'groupe' => $groupe]);
    }

    /**
     * Ajout d'un background à un groupe.
     */
    public function addBackgroundAction(Request $request, Application $app)
    {
        $id = $request->get('index');
        $groupe = $app['orm.em']->find('\\'.\App\Entity\Groupe::class, $id);

        $background = new \App\Entity\Background();
        $background->setGroupe($groupe);

        $form = $app['form.factory']->createBuilder(new BackgroundForm(), $background)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $app['session']->getFlashBag()->add('success', 'Le background du groupe a été créé');

            return $app->redirect($app['url_generator']->generate('groupe.admin.detail', ['index' => $groupe->getId()]), 303);
        }

        return $app['twig']->render('admin/groupe/background/add.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour du background d'un groupe.
     */
    public function updateBackgroundAction(Request $request, Application $app)
    {
        $id = $request->get('index');
        $groupe = $app['orm.em']->find('\\'.\App\Entity\Groupe::class, $id);

        $form = $app['form.factory']->createBuilder(new BackgroundForm(), $groupe->getBackground())
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $background = $form->getData();

            $app['orm.em']->persist($background);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le background du groupe a été mis à jour');

            return $app->redirect($app['url_generator']->generate('groupe.admin.detail', ['index' => $groupe->getId()]), 303);
        }

        return $app['twig']->render('admin/groupe/background/update.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajout d'un groupe.
     */
    public function addAction(Request $request, Application $app)
    {
        $groupe = new \App\Entity\Groupe();

        $form = $app['form.factory']->createBuilder(new GroupeForm(), $groupe)
            ->add('save', 'submit', ['label' => 'Sauvegarder et fermer'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder et nouveau'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupe = $form->getData();

            /**
             * Création des topics associés à ce groupe
             * un topic doit être créé par GN auquel ce groupe est inscrit.
             *
             * @var \App\Entity\Topic $topic
             */
            $topic = new \App\Entity\Topic();
            $topic->setTitle($groupe->getNom());
            $topic->setDescription($groupe->getDescription());
            $topic->setUser($this->getUser());
            // défini les droits d'accés à ce forum
            // (les membres du groupe ont le droit d'accéder à ce forum)
            $topic->setRight('GROUPE_MEMBER');
            $topic->setTopic($app['larp.manager']->findTopic('TOPIC_GROUPE'));

            $groupe->setTopic($topic);

            $app['orm.em']->persist($topic);
            $app['orm.em']->persist($groupe);
            $app['orm.em']->flush();

            $topic->setObjectId($groupe->getId());
            $app['orm.em']->persist($topic);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'Le groupe été sauvegardé');

            /*
             * Si l'utilisateur a cliqué sur "save", renvoi vers la liste des groupes
             * Si l'utilisateur a cliqué sur "save_continue", renvoi vers un nouveau formulaire d'ajout
             */
            if ($form->get('save')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('groupe.admin.list'), 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('groupe.add'), 303);
            }
        }

        return $app['twig']->render('admin/groupe/add.twig', ['form' => $form->createView()]);
    }

    /**
     * Modification d'un groupe.
     */
    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $groupe = $app['orm.em']->find('\\'.\App\Entity\Groupe::class, $id);

        $originalGroupeClasses = new ArrayCollection();
        $originalGns = new ArrayCollection();
        $originalTerritoires = new ArrayCollection();

        /*
         *  Crée un tableau contenant les objets GroupeClasse du groupe
         */
        foreach ($groupe->getGroupeClasses() as $groupeClasse) {
            $originalGroupeClasses->add($groupeClasse);
        }

        /*
         * Crée un tableau contenant les territoires que ce groupe posséde
         */
        foreach ($groupe->getTerritoires() as $territoire) {
            $originalTerritoires->add($territoire);
        }

        $form = $app['form.factory']->createBuilder(new GroupeForm(), $groupe)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $groupe = $form->getData();

            /*
             * Pour toutes les classes du groupe
             */
            foreach ($groupe->getGroupeClasses() as $groupeClasses) {
                $groupeClasses->setGroupe($groupe);
            }

            /*
             *  supprime la relation entre le groupeClasse et le groupe
             */
            foreach ($originalGroupeClasses as $groupeClasse) {
                if (false == $groupe->getGroupeClasses()->contains($groupeClasse)) {
                    $app['orm.em']->remove($groupeClasse);
                }
            }

            /*
             * Pour tous les territoire du groupe
             */
            foreach ($groupe->getTerritoires() as $territoire) {
                $territoire->setGroupe($groupe);
            }

            foreach ($originalTerritoires as $territoire) {
                if (false == $groupe->getTerritoires()->contains($territoire)) {
                    $territoire->setGroupe(null);
                }
            }

            /*
             * Si l'utilisateur a cliquer sur "update", on met à jour le groupe
             * Si l'utilisateur a cliquer sur "delete", on supprime le groupe
             */
            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($groupe);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'Le groupe a été mis à jour.');

                return $app->redirect($app['url_generator']->generate('groupe.detail', ['index' => $id]));
            } elseif ($form->get('delete')->isClicked()) {
                // supprime le lien entre les personnages et le groupe
                foreach ($groupe->getPersonnages() as $personnage) {
                    $personnage->setGroupe(null);
                    $app['orm.em']->persist($personnage);
                }
                // supprime le lien entre les participants et le groupe
                foreach ($groupe->getParticipants() as $participant) {
                    $participant->setGroupe(null);
                    $app['orm.em']->persist($participant);
                }
                // supprime la relation entre le groupeClasse et le groupe
                foreach ($groupe->getGroupeClasses() as $groupeClasse) {
                    $app['orm.em']->remove($groupeClasse);
                }
                // supprime la relation entre les territoires et le groupe
                foreach ($groupe->getTerritoires() as $territoire) {
                    $territoire->setGroupe(null);
                    $app['orm.em']->persist($territoire);
                }
                // supprime la relation entre un background et le groupe
                foreach ($groupe->getBackgrounds() as $background) {
                    $app['orm.em']->remove($background);
                }
                $app['orm.em']->remove($groupe);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'Le groupe a été supprimé.');

                return $app->redirect($app['url_generator']->generate('groupe.admin.list'));
            }
        }

        return $app['twig']->render('admin/groupe/update.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche le détail d'un groupe.
     */
    public function detailAction(Request $request, Application $app)
    {
        $id = $request->get('index');

        $groupe = $app['orm.em']->find('\\'.\App\Entity\Groupe::class, $id);

        /*
         * Si le groupe existe, on affiche son détail
         * Sinon on envoi une erreur
         */
        if ($groupe) {
            return $app['twig']->render('admin/groupe/detail.twig', ['groupe' => $groupe]);
        } else {
            $app['session']->getFlashBag()->add('error', 'Le groupe n\'a pas été trouvé.');

            return $app->redirect($app['url_generator']->generate('groupe'));
        }
    }

    /**
     * Exportation de la liste des groupes au format CSV.
     */
    public function exportAction(Request $request, Application $app): void
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Groupe::class);
        $groupes = $repo->findAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_groupe_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv($output,
            [
                'nom',
                'description',
                'code',
                'creation_date'], ',');

        foreach ($groupes as $groupe) {
            fputcsv($output, $groupe->getExportValue(), ',');
        }

        fclose($output);
        exit;
    }
}
