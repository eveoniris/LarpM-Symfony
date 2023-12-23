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

use App\Entity\Competence;
use App\Repository\CompetenceRepository;
use App\Form\CompetenceForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

/**
 * LarpManager\Controllers\CompetenceController.
 *
 * @author kevin
 */
class CompetenceController extends AbstractController
{
    /**
     * Liste des compétences.
     */
    #[Route('/competence', name: 'competence')]
    public function indexAction(CompetenceRepository $competenceRepository, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_REGLE')) {
           $competences = $competenceRepository->findAllOrderedByLabel();
        } else {
            $competences = $competenceRepository->getRootCompetences($entityManager);
        }

        return $this->render(
            'competence/list.twig', 
            [
                'competences' => $competences
            ]
        );
    }

    /**
     * Liste des perso ayant cette compétence.
     */
    public function persoAction(Request $request, Application $app)
    {
        $competence = $request->get('competence');

        return $app['twig']->render('admin/competence/perso.twig', ['competence' => $competence]);
    }

    /**
     * Liste du matériel necessaire par compétence.
     */
    public function materielAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Competence::class);
        $competences = $repo->findAllOrderedByLabel();

        return $app['twig']->render('admin/competence/materiel.twig', ['competences' => $competences]);
    }

    /**
     * Ajout d'une compétence.
     */
    public function addAction(Request $request, Application $app)
    {
        $competence = new \App\Entity\Competence();

        // l'identifiant de la famille de competence peux avoir été passé en paramètre
        // pour initialiser le formulaire avec une valeur par défaut.
        // TODO : dans ce cas, il ne faut proposer que les niveaux pour lesquels une compétence
        // n'a pas été défini pour cette famille

        $competenceFamilyId = $request->get('competenceFamily');
        $levelIndex = $request->get('level');

        if ($competenceFamilyId) {
            $competenceFamily = $app['orm.em']->find('\\'.\App\Entity\CompetenceFamily::class, $competenceFamilyId);
            if ($competenceFamily) {
                $competence->setCompetenceFamily($competenceFamily);
            }
        }

        if ($levelIndex) {
            $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Level::class);
            $level = $repo->findOneByIndex($levelIndex + 1);
            if ($level) {
                $competence->setLevel($level);
            }
        }

        $form = $app['form.factory']->createBuilder(new CompetenceForm(), $competence)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $competence = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $app['session']->getFlashBag()->add('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $app->redirect($app['url_generator']->generate('competence.family'), 303);
                }

                $documentFilename = hash('md5', $competence->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $competence->setDocumentUrl($documentFilename);
            }

            $app['orm.em']->persist($competence);
            $app['orm.em']->flush();

            $app['session']->getFlashBag()->add('success', 'La compétence a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('competence.detail', ['competence' => $competence->getId()]));
            } elseif ($form->get('save_continue')->isClicked()) {
                return $app->redirect($app['url_generator']->generate('competence.add'), 303);
            }
        }

        return $app['twig']->render('admin/competence/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'une compétence.
     */
    public function detailAction(Request $request, Application $app)
    {
        $competence = $request->get('competence');

        return $app['twig']->render('admin/competence/detail.twig', ['competence' => $competence]);
    }

    /**
     * Met à jour une compétence.
     */
    public function updateAction(Request $request, Application $app)
    {
        $competence = $request->get('competence');
        $attributeRepos = $app['orm.em']->getRepository('\\'.\App\Entity\AttributeType::class);
        $form = $app['form.factory']->createBuilder(new CompetenceForm(), $competence)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $competence = $form->getData();

            $files = $request->files->get($form->getName());

            // si un document est fourni, l'enregistré
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $app['session']->getFlashBag()->add('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $app->redirect($app['url_generator']->generate('competence.family'), 303);
                }

                $documentFilename = hash('md5', $competence->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $competence->setDocumentUrl($documentFilename);
            }

            if ($form->get('update')->isClicked()) {
                $competence->setCompetenceAttributesAsString($request->get('competenceAttributesAsString'), $app['orm.em'], $attributeRepos);
                $app['orm.em']->persist($competence);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'La compétence a été mise à jour.');

                return $app->redirect($app['url_generator']->generate('competence.detail', ['competence' => $competence->getId()]));
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($competence);
                $app['orm.em']->flush();
                $app['session']->getFlashBag()->add('success', 'La compétence a été supprimée.');

                return $app->redirect($app['url_generator']->generate('competence'));
            }
        }

        return $app['twig']->render('admin/competence/update.twig', [
            'competence' => $competence,
            'form' => $form->createView(),
            'available_attributes' => $attributeRepos->findAllOrderedByLabel(),
        ]);
    }

    /**
     * Retire le document d'une competence.
     */
    public function removeDocumentAction(Request $request, Application $app, Competence $competence)
    {
        $competence->setDocumentUrl(null);

        $app['orm.em']->persist($competence);
        $app['orm.em']->flush();
        $app['session']->getFlashBag()->add('success', 'La compétence a été mise à jour.');

        return $app->redirect($app['url_generator']->generate('competence'));
    }

    /**
     * Téléchargement du document lié à une compétence.
     */
    public function getDocumentAction(Request $request, Application $app)
    {
        $document = $request->get('document');
        $competence = $request->get('competence');

        // on ne peux télécharger que les documents des compétences que l'on connait
        if (!$app['security.authorization_checker']->isGranted('ROLE_REGLE') && $app['User']->getPersonnage()) {
            if (!$app['User']->getPersonnage()->getCompetences()->contains($competence)) {
                $app['session']->getFlashBag()->add('error', "Vous n'avez pas les droits necessaires");
            }
        }

        $file = __DIR__.'/../../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$competence->getLabel().'.pdf"',
        ]);
    }
}
