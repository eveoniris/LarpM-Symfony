<?php


namespace App\Controller;

use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Sort;
use App\Form\DomaineDeleteForm;
use App\Form\DomaineForm;
use App\Form\Potion\PotionDeleteForm;
use App\Form\Potion\PotionForm;
use App\Form\PriereDeleteForm;
use App\Form\PriereForm;
use App\Form\SortDeleteForm;
use App\Form\SortForm;
use App\Form\SphereDeleteForm;
use App\Form\SphereForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_REGLE')]
class MagieController extends AbstractController
{
    // liste des colonnes à afficher par défaut sur les vues 'personnages' (l'ordre est pris en compte)
    private array $defaultPersonnageListColumnKeys = ['colId', 'colStatut', 'colNom', 'colClasse', 'colGroupe', 'colUser'];

    /**
     * Liste des sphere.
     */
    #[Route('/magie/sphere', name: 'magieSphere.list')]
    public function sphereListAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $spheres = $entityManager->getRepository('\\'.\App\Entity\Sphere::class)->findAll();

        return $this->render('admin/sphere/list.twig', [
            'spheres' => $spheres,
        ]);
    }

    /**
     * Detail d'une sphere.
     */
    public function sphereDetailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $sphere = $request->get('sphere');

        return $this->render('admin/sphere/detail.twig', [
            'sphere' => $sphere,
        ]);
    }

    /**
     * Ajoute une sphere.
     */
    public function sphereAddAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $sphere = new \App\Entity\Sphere();

        $form = $this->createForm(SphereForm::class(), $sphere)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sphere = $form->getData();

            $entityManager->persist($sphere);
            $entityManager->flush();

           $this->addFlash('success', 'La sphere a été ajouté');

            return $this->redirectToRoute('magie.sphere.detail', ['sphere' => $sphere->getId()], [], 303);
        }

        return $this->render('admin/sphere/add.twig', [
            'sphere' => $sphere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une sphere.
     */
    public function sphereUpdateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $sphere = $request->get('sphere');

        $form = $this->createForm(SphereForm::class(), $sphere)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sphere = $form->getData();

            $entityManager->persist($sphere);
            $entityManager->flush();

           $this->addFlash('success', 'La sphere a été sauvegardé');

            return $this->redirectToRoute('magie.sphere.detail', ['sphere' => $sphere->getId()], [], 303);
        }

        return $this->render('admin/sphere/update.twig', [
            'sphere' => $sphere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une sphère.
     */
    public function sphereDeleteAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $sphere = $request->get('sphere');

        $form = $this->createForm(SphereDeleteForm::class(), $sphere)
            ->add('save', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sphere = $form->getData();

            $entityManager->remove($sphere);
            $entityManager->flush();

           $this->addFlash('success', 'La sphere a été suprimé');

            return $this->redirectToRoute('magie.sphere.list', [], 303);
        }

        return $this->render('admin/sphere/delete.twig', [
            'sphere' => $sphere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des prieres.
     */
    #[Route('/magie/priere', name: 'magiePriere.list')]
    public function priereListAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $prieres = $entityManager->getRepository('\\'.\App\Entity\Priere::class)->findAll();

        return $this->render('admin/priere/list.twig', [
            'prieres' => $prieres,
        ]);
    }

    /**
     * Detail d'une priere.
     */
    public function priereDetailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $priere = $request->get('priere');

        return $this->render('admin/priere/detail.twig', [
            'priere' => $priere,
        ]);
    }

    /**
     * Ajoute une priere.
     */
    public function priereAddAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $priere = new \App\Entity\Priere();

        $form = $this->createForm(PriereForm::class(), $priere)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $priere = $form->getData();

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('magie.priere.list', [], 303);
                }

                $documentFilename = hash('md5', $priere->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $priere->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($priere);
            $entityManager->flush();

           $this->addFlash('success', 'La priere a été ajouté');

            return $this->redirectToRoute('magie.priere.detail', ['priere' => $priere->getId()], [], 303);
        }

        return $this->render('admin/priere/add.twig', [
            'priere' => $priere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une priere.
     */
    public function priereUpdateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $priere = $request->get('priere');

        $form = $this->createForm(PriereForm::class(), $priere)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $priere = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('magie.priere.list', [], 303);
                }

                $documentFilename = hash('md5', $priere->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $priere->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($priere);
            $entityManager->flush();

           $this->addFlash('success', 'La priere a été sauvegardé');

            return $this->redirectToRoute('magie.priere.detail', ['priere' => $priere->getId()], [], 303);
        }

        return $this->render('admin/priere/update.twig', [
            'priere' => $priere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une priere.
     */
    public function priereDeleteAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $priere = $request->get('priere');

        $form = $this->createForm(PriereDeleteForm::class(), $priere)
            ->add('save', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $priere = $form->getData();

            $entityManager->remove($priere);
            $entityManager->flush();

           $this->addFlash('success', 'La priere a été suprimé');

            return $this->redirectToRoute('magie.priere.list', [], 303);
        }

        return $this->render('admin/priere/delete.twig', [
            'priere' => $priere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une priere.
     */
    public function getPriereDocumentAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $document = $request->get('document');
        $priere = $request->get('priere');

        // on ne peux télécharger que les documents des compétences que l'on connait
        /*if  ( ! $app['security.authorization_checker']->isGranted('ROLE_REGLE') )
        {
        if ( $this->getUser()->getPersonnage() )
        {
        if ( ! $this->getUser()->getPersonnage()->getCompetences()->contains($competence) )
        {
       $this->addFlash('error', 'Vous n\'avez pas les droits necessaires');
        }
        }
        }*/

        $file = __DIR__.'/../../../private/doc/'.$document;

        /*$stream = function () use ($file) {
            readfile($file);
        };

        return $app->stream($stream, 200, array(
                'Content-Type' => 'text/pdf',
                'Content-length' => filesize($file),
                'Content-Disposition' => 'attachment; filename="'.$priere->getLabel().'.pdf"'
        ));*/

        return $app->sendFile($file);
    }

    /**
     * Liste des personnages ayant cette prière.
     */
    public function prierePersonnagesAction(Request $request,  EntityManagerInterface $entityManager, Priere $priere)
    {
        $routeName = 'magie.priere.personnages';
        $routeParams = ['priere' => $priere->getId()];
        $twigFilePath = 'admin/priere/personnages.twig';
        $columnKeys = $this->defaultPersonnageListColumnKeys;
        $personnages = $priere->getPersonnages();
        $additionalViewParams = [
            'priere' => $priere,
        ];

        // handle the request and return an array containing the parameters for the view
        $personnageSearchHandler = $app['personnage.manager']->getSearchHandler();

        $viewParams = $personnageSearchHandler->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages
        );

        return $this->render(
            $twigFilePath,
            $viewParams
        );
    }

    /**
     * Liste des potions.
     */
    #[Route('/magie/potion', name: 'magiePotion.list')]
    public function potionListAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $potions = $entityManager->getRepository('\\'.\App\Entity\Potion::class)->findAll();

        return $this->render('admin/potion/list.twig', [
            'potions' => $potions,
        ]);
    }

    /**
     * Detail d'une potion.
     */
    public function potionDetailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $potion = $request->get('potion');

        return $this->render('admin/potion/detail.twig', [
            'potion' => $potion,
        ]);
    }

    /**
     * Liste des personnages ayant cette potion.
     *
     * @param Potion
     */
    public function potionPersonnagesAction(Request $request,  EntityManagerInterface $entityManager, Potion $potion)
    {
        $routeName = 'magie.potion.personnages';
        $routeParams = ['potion' => $potion->getId()];
        $twigFilePath = 'admin/potion/personnages.twig';
        $columnKeys = $this->defaultPersonnageListColumnKeys;
        $personnages = $potion->getPersonnages();
        $additionalViewParams = [
            'potion' => $potion,
        ];

        // handle the request and return an array containing the parameters for the view
        $personnageSearchHandler = $app['personnage.manager']->getSearchHandler();

        $viewParams = $personnageSearchHandler->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages
        );

        return $this->render(
            $twigFilePath,
            $viewParams
        );
    }

    /**
     * Ajoute une potion.
     */
    public function potionAddAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $potion = new \App\Entity\Potion();

        $form = $this->createForm(PotionForm::class(), $potion)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $potion = $form->getData();

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('magie.potion.list', [], 303);
                }

                $documentFilename = hash('md5', $potion->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $potion->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($potion);
            $entityManager->flush();

           $this->addFlash('success', 'La potion a été ajouté');

            return $this->redirectToRoute('magie.potion.detail', ['potion' => $potion->getId()], [], 303);
        }

        return $this->render('admin/potion/add.twig', [
            'potion' => $potion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une potion.
     */
    public function potionUpdateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $potion = $request->get('potion');

        $form = $this->createForm(PotionForm::class(), $potion)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $potion = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('magie.potion.list', [], 303);
                }

                $documentFilename = hash('md5', $potion->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $potion->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($potion);
            $entityManager->flush();

           $this->addFlash('success', 'La potion a été sauvegardé');

            return $this->redirectToRoute('magie.potion.detail', ['potion' => $potion->getId()], [], 303);
        }

        return $this->render('admin/potion/update.twig', [
            'potion' => $potion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une potion.
     */
    public function potionDeleteAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $potion = $request->get('potion');

        $form = $this->createForm(PotionDeleteForm::class(), $potion)
            ->add('save', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $potion = $form->getData();

            $entityManager->remove($potion);
            $entityManager->flush();

           $this->addFlash('success', 'La potion a été suprimé');

            return $this->redirectToRoute('magie.potion.list', [], 303);
        }

        return $this->render('admin/potion/delete.twig', [
            'potion' => $potion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une potion.
     */
    public function getPotionDocumentAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $document = $request->get('document');
        $potion = $request->get('potion');

        $file = __DIR__.'/../../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$potion->getLabel().'.pdf"',
        ]);
    }

    /**
     * Liste des domaines de magie.
     */
    #[Route('/magie/domaine', name: 'magieDomaine.list')]
    public function domaineListAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $domaines = $entityManager->getRepository('\\'.\App\Entity\Domaine::class)->findAll();

        return $this->render('admin/domaine/list.twig', [
            'domaines' => $domaines,
        ]);
    }

    /**
     * Detail d'un domaine de magie.
     */
    public function domaineDetailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $domaine = $request->get('domaine');

        return $this->render('admin/domaine/detail.twig', [
            'domaine' => $domaine,
        ]);
    }

    /**
     * Ajoute un domaine de magie.
     */
    public function domaineAddAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $domaine = new \App\Entity\Domaine();

        $form = $this->createForm(DomaineForm::class(), $domaine)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $domaine = $form->getData();

            $entityManager->persist($domaine);
            $entityManager->flush();

           $this->addFlash('success', 'Le domaine de magie a été ajouté');

            return $this->redirectToRoute('magie.domaine.detail', ['domaine' => $domaine->getId()], [], 303);
        }

        return $this->render('admin/domaine/add.twig', [
            'domaine' => $domaine,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un domaine de magie.
     */
    public function domaineUpdateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $domaine = $request->get('domaine');

        $form = $this->createForm(DomaineForm::class(), $domaine)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $domaine = $form->getData();

            $entityManager->persist($domaine);
            $entityManager->flush();

           $this->addFlash('success', 'Le domaine de magie a été sauvegardé');

            return $this->redirectToRoute('magie.domaine.detail', ['domaine' => $domaine->getId()], [], 303);
        }

        return $this->render('admin/domaine/update.twig', [
            'domaine' => $domaine,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un domaine de magie.
     */
    public function domaineDeleteAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $domaine = $request->get('domaine');

        $form = $this->createForm(DomaineDeleteForm::class(), $domaine)
            ->add('save', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $domaine = $form->getData();

            $entityManager->remove($domaine);
            $entityManager->flush();

           $this->addFlash('success', 'Le domaine de magie a été suprimé');

            return $this->redirectToRoute('magie.domaine.list', [], 303);
        }

        return $this->render('admin/domaine/delete.twig', [
            'domaine' => $domaine,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des sorts.
     */
    #[Route('/magie/sort', name: 'magieSort.list')]
    public function sortListAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $sorts = $entityManager->getRepository('\\'.\App\Entity\Sort::class)->findAll();

        return $this->render('admin/sort/list.twig', [
            'sorts' => $sorts,
        ]);
    }

    /**
     * Detail d'un sort.
     */
    public function sortDetailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $sort = $request->get('sort');

        return $this->render('admin/sort/detail.twig', [
            'sort' => $sort,
        ]);
    }

    /**
     * Ajoute un sort.
     */
    public function sortAddAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $sort = new \App\Entity\Sort();

        $form = $this->createForm(SortForm::class(), $sort)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sort = $form->getData();

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('magie.sort.list', [], 303);
                }

                $documentFilename = hash('md5', $sort->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $sort->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($sort);
            $entityManager->flush();

           $this->addFlash('success', 'Le sort a été ajouté');

            return $this->redirectToRoute('magie.sort.detail', ['sort' => $sort->getId()], [], 303);
        }

        return $this->render('admin/sort/add.twig', [
            'sort' => $sort,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un sort.
     */
    public function sortUpdateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $sort = $request->get('sort');

        $form = $this->createForm(SortForm::class(), $sort)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sort = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('magie.sort.list', [], 303);
                }

                $documentFilename = hash('md5', $sort->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $sort->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($sort);
            $entityManager->flush();

           $this->addFlash('success', 'Le sort a été sauvegardé');

            return $this->redirectToRoute('magie.sort.detail', ['sort' => $sort->getId()], [], 303);
        }

        return $this->render('admin/sort/update.twig', [
            'sort' => $sort,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un sortilège.
     */
    public function sortDeleteAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $sort = $request->get('sort');

        $form = $this->createForm(SortDeleteForm::class(), $sort)
            ->add('save', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sort = $form->getData();

            $entityManager->remove($sort);
            $entityManager->flush();

           $this->addFlash('success', 'Le sort a été supprimé');

            return $this->redirectToRoute('magie.sort.list', [], 303);
        }

        return $this->render('admin/sort/delete.twig', [
            'sort' => $sort,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a un sort.
     */
    public function getSortDocumentAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $document = $request->get('document');
        $sort = $request->get('sort');

        // on ne peux télécharger que les documents des compétences que l'on connait
        /*if  ( ! $app['security.authorization_checker']->isGranted('ROLE_REGLE') )
        {
            if ( $this->getUser()->getPersonnage() )
            {
                if ( ! $this->getUser()->getPersonnage()->getCompetences()->contains($competence) )
                {
                   $this->addFlash('error', 'Vous n\'avez pas les droits necessaires');
                }
            }
        }*/

        $file = __DIR__.'/../../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$sort->getPrintLabel().'.pdf"',
        ]);
    }

    /**
     * Liste des personnages ayant ce sort.
     *
     * @param Sort
     */
    public function sortPersonnagesAction(Request $request,  EntityManagerInterface $entityManager, Sort $sort)
    {
        $routeName = 'magie.sort.personnages';
        $routeParams = ['sort' => $sort->getId()];
        $twigFilePath = 'admin/sort/personnages.twig';
        $columnKeys = $this->defaultPersonnageListColumnKeys;
        $personnages = $sort->getPersonnages();
        $additionalViewParams = [
            'sort' => $sort,
        ];

        // handle the request and return an array containing the parameters for the view
        $personnageSearchHandler = $app['personnage.manager']->getSearchHandler();

        $viewParams = $personnageSearchHandler->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages
        );

        return $this->render(
            $twigFilePath,
            $viewParams
        );
    }
}
