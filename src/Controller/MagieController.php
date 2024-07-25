<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Domaine;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Sort;
use App\Entity\Sphere;
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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
class MagieController extends AbstractController
{
    // liste des colonnes à afficher par défaut sur les vues 'personnages' (l'ordre est pris en compte)
    private array $defaultPersonnageListColumnKeys = [
        'colId',
        'colStatut',
        'colNom',
        'colClasse',
        'colGroupe',
        'colUser',
    ];

    /**
     * Liste des sphere.
     */
    #[Route('/magie/sphere', name: 'magieSphere.list')]
    public function sphereListAction(EntityManagerInterface $entityManager): Response
    {
        $spheres = $entityManager->getRepository(Sphere::class)->findAll();

        return $this->render('sphere/list.twig', [
            'spheres' => $spheres,
        ]);
    }

    /**
     * Detail d'une sphere.
     */
    #[Route('/magie/sphere/{sphere}', name: 'magie.sphere.detail')]
    public function sphereDetailAction(Sphere $sphere): Response
    {
        return $this->render('sphere/detail.twig', [
            'sphere' => $sphere,
        ]);
    }

    /**
     * Ajoute une sphere.
     */
    #[Route('/magie/sphere', name: 'magie.sphere.add')]
    public function sphereAddAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $sphere = new Sphere();

        $form = $this->createForm(SphereForm::class, $sphere)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sphere = $form->getData();

            $entityManager->persist($sphere);
            $entityManager->flush();

            $this->addFlash('success', 'La sphere a été ajouté');

            return $this->redirectToRoute('magie.sphere.detail', ['sphere' => $sphere->getId()], 303);
        }

        return $this->render('sphere/add.twig', [
            'sphere' => $sphere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une sphere.
     */
    #[Route('/magie/sphere/{sphere}/update', name: 'magie.sphere.update', requirements: ['sphere' => Requirement::DIGITS])]
    public function sphereUpdateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Sphere $sphere
    ): RedirectResponse|Response {
        $form = $this->createForm(SphereForm::class, $sphere)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sphere = $form->getData();

            $entityManager->persist($sphere);
            $entityManager->flush();

            $this->addFlash('success', 'La sphere a été sauvegardé');

            return $this->redirectToRoute('magie.sphere.detail', ['sphere' => $sphere->getId()], 303);
        }

        return $this->render('sphere/update.twig', [
            'sphere' => $sphere,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/magie/sphere/{sphere}/delete', name: 'magie.sphere.delete', requirements: ['sphere' => Requirement::DIGITS])]
    public function sphereDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Sphere $sphere
    ): RedirectResponse|Response {
        $form = $this->createForm(SphereDeleteForm::class, $sphere)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sphere = $form->getData();

            $entityManager->remove($sphere);
            $entityManager->flush();

            $this->addFlash('success', 'La sphere a été suprimé');

            return $this->redirectToRoute('magie.sphere.list', [], 303);
        }

        return $this->render('sphere/delete.twig', [
            'sphere' => $sphere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des prieres.
     */
    #[Route('/magie/priere', name: 'magiePriere.list')]
    public function priereListAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $prieres = $entityManager->getRepository('\\'.Priere::class)->findAll();

        return $this->render('priere/list.twig', [
            'prieres' => $prieres,
        ]);
    }

    #[Route('/magie/priere/{priere}', name: 'magie.priere.detail', requirements: ['priere' => Requirement::DIGITS])]
    public function priereDetailAction(#[MapEntity] Priere $priere): Response
    {
        return $this->render('priere/detail.twig', ['priere' => $priere]);
    }

    /**
     * Ajoute une priere.
     */
    #[Route('/magie/priere/add', name: 'magie.priere.add')]
    public function priereAddAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $priere = new Priere();

        $form = $this->createForm(PriereForm::class, $priere)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $priere = $form->getData();

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $this->addFlash(
                        'error',
                        'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)'
                    );

                    return $this->redirectToRoute('magie.priere.list', [], 303);
                }

                $documentFilename = hash('md5', $priere->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $priere->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($priere);
            $entityManager->flush();

            $this->addFlash('success', 'La priere a été ajouté');

            return $this->redirectToRoute('magie.priere.detail', ['priere' => $priere->getId()], 303);
        }

        return $this->render('priere/add.twig', [
            'priere' => $priere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une priere.
     */
    #[Route('/magie/priere/{priere}/update', name: 'magie.priere.update')]
    public function priereUpdateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Priere $priere
    ): RedirectResponse|Response {

        $form = $this->createForm(PriereForm::class, $priere)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $priere = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $this->addFlash(
                        'error',
                        'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)'
                    );

                    return $this->redirectToRoute('magie.priere.list', [], 303);
                }

                $documentFilename = hash('md5', $priere->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $priere->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($priere);
            $entityManager->flush();

            $this->addFlash('success', 'La priere a été sauvegardé');

            return $this->redirectToRoute('magie.priere.detail', ['priere' => $priere->getId()], 303);
        }

        return $this->render('priere/update.twig', [
            'priere' => $priere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une priere.
     */
    #[Route('/magie/priere/{priere}/delete', name: 'magie.priere.delete')]
    public function priereDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Priere $priere
    ): RedirectResponse|Response {

        $form = $this->createForm(PriereDeleteForm::class, $priere)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $priere = $form->getData();

            $entityManager->remove($priere);
            $entityManager->flush();

            $this->addFlash('success', 'La priere a été suprimé');

            return $this->redirectToRoute('magie.priere.list', [], 303);
        }

        return $this->render('priere/delete.twig', [
            'priere' => $priere,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une priere.
     */
    #[Route('/magie/priere/document', name: 'magie.priere.document')]
    public function getPriereDocumentAction(Request $request, EntityManagerInterface $entityManager)
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

        $file = __DIR__.'/../../private/doc/'.$document;

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
    #[Route('/magie/priere/personnages', name: 'magie.priere.personnages')]
    public function prierePersonnagesAction(
        Request $request,
        Priere $priere
    ): Response {
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
    public function potionListAction(EntityManagerInterface $entityManager): Response
    {
        $potions = $entityManager->getRepository(Potion::class)->findAll();

        return $this->render('potion/list.twig', [
            'potions' => $potions,
        ]);
    }

    /**
     * Detail d'une potion.
     */
    #[Route('/magie/potion/{potion}', name: 'magie.potion.detail', requirements: ['potion' => Requirement::DIGITS])]
    public function potionDetailAction(#[MapEntity] Potion $potion): Response
    {
        return $this->render('potion/detail.twig', [
            'potion' => $potion,
        ]);
    }

    /**
     * Liste des personnages ayant cette potion.
     *
     * @param Potion
     */
    #[Route('/magie/potion/{potion}/personnages', name: 'magie.potion.personnages', requirements: ['potion' => Requirement::DIGITS])]
    public function potionPersonnagesAction(
        Request $request,
        #[MapEntity] Potion $potion
    ): Response {
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
    #[Route('/magie/potion/add', name: 'magie.potion.add')]
    public function potionAddAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $potion = new Potion();

        $form = $this->createForm(PotionForm::class, $potion)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $potion = $form->getData();

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $this->addFlash(
                        'error',
                        'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)'
                    );

                    return $this->redirectToRoute('magie.potion.list', [], 303);
                }

                $documentFilename = hash('md5', $potion->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $potion->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($potion);
            $entityManager->flush();

            $this->addFlash('success', 'La potion a été ajouté');

            return $this->redirectToRoute('magie.potion.detail', ['potion' => $potion->getId()], 303);
        }

        return $this->render('potion/add.twig', [
            'potion' => $potion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une potion.
     */
    #[Route('/magie/potion/{potion}/update', name: 'magie.potion.update', requirements: ['potion' => Requirement::DIGITS])]
    public function potionUpdateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Potion $potion
    ): RedirectResponse|Response {

        $form = $this->createForm(PotionForm::class, $potion)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $potion = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $this->addFlash(
                        'error',
                        'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)'
                    );

                    return $this->redirectToRoute('magie.potion.list', [], 303);
                }

                $documentFilename = hash('md5', $potion->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $potion->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($potion);
            $entityManager->flush();

            $this->addFlash('success', 'La potion a été sauvegardé');

            return $this->redirectToRoute('magie.potion.detail', ['potion' => $potion->getId()], 303);
        }

        return $this->render('potion/update.twig', [
            'potion' => $potion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une potion.
     */
    #[Route('/magie/potion/{potion}/delete', name: 'magie.potion.delete', requirements: ['potion' => Requirement::DIGITS])]
    public function potionDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Potion $potion
    ): RedirectResponse|Response {
        $form = $this->createForm(PotionDeleteForm::class, $potion)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $potion = $form->getData();

            $entityManager->remove($potion);
            $entityManager->flush();

            $this->addFlash('success', 'La potion a été suprimé');

            return $this->redirectToRoute('magie.potion.list', [], 303);
        }

        return $this->render('potion/delete.twig', [
            'potion' => $potion,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une potion.
     */
    #[Route('/magie/potion/{potion}/document/{document}', name: 'magie.potion.document', requirements: ['potion' => Requirement::DIGITS, 'document' => Requirement::DIGITS])]
    public function getPotionDocumentAction(Request $request, #[MapEntity] Potion $potion, #[MapEntity] Document $document): Response
    {
        $file = __DIR__.'/../../private/doc/'.$document;

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
    public function domaineListAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $domaines = $entityManager->getRepository('\\'.Domaine::class)->findAll();

        return $this->render('domaine/list.twig', [
            'domaines' => $domaines,
        ]);
    }

    /**
     * Detail d'un domaine de magie.
     */
    #[Route('/magie/domaine/{domaine}/detail', name: 'magie.domaine.detail', requirements: ['domaine' => Requirement::DIGITS])]
    public function domaineDetailAction(#[MapEntity] Domaine $domaine): Response
    {
        return $this->render('domaine/detail.twig', [
            'domaine' => $domaine,
        ]);
    }

    /**
     * Ajoute un domaine de magie.
     */
    #[Route('/magie/domaine/add', name: 'magie.domaine.add')]
    public function domaineAddAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $domaine = new Domaine();

        $form = $this->createForm(DomaineForm::class, $domaine)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $domaine = $form->getData();

            $entityManager->persist($domaine);
            $entityManager->flush();

            $this->addFlash('success', 'Le domaine de magie a été ajouté');

            return $this->redirectToRoute('magie.domaine.detail', ['domaine' => $domaine->getId()], 303);
        }

        return $this->render('domaine/add.twig', [
            'domaine' => $domaine,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un domaine de magie.
     */
    #[Route('/magie/domaine/{domaine}/update', name: 'magie.domaine.update', requirements: ['domaine' => Requirement::DIGITS])]
    public function domaineUpdateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Domaine $domaine
    ): RedirectResponse|Response {

        $form = $this->createForm(DomaineForm::class, $domaine)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $domaine = $form->getData();

            $entityManager->persist($domaine);
            $entityManager->flush();

            $this->addFlash('success', 'Le domaine de magie a été sauvegardé');

            return $this->redirectToRoute('magie.domaine.detail', ['domaine' => $domaine->getId()], 303);
        }

        return $this->render('domaine/update.twig', [
            'domaine' => $domaine,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un domaine de magie.
     */
    #[Route('/magie/domaine/{domaine}', name: 'magie.domaine.delete', requirements: ['domaine' => Requirement::DIGITS])]
    public function domaineDeleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Domaine $domaine
    ): RedirectResponse|Response {

        $form = $this->createForm(DomaineDeleteForm::class, $domaine)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $domaine = $form->getData();

            $entityManager->remove($domaine);
            $entityManager->flush();

            $this->addFlash('success', 'Le domaine de magie a été suprimé');

            return $this->redirectToRoute('magie.domaine.list', [], 303);
        }

        return $this->render('domaine/delete.twig', [
            'domaine' => $domaine,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des sorts.
     */
    #[Route('/magie/sort', name: 'magieSort.list')]
    public function sortListAction(EntityManagerInterface $entityManager): Response
    {
        $sorts = $entityManager->getRepository(Sort::class)->findAll();

        return $this->render('sort/list.twig', [
            'sorts' => $sorts,
        ]);
    }

    /**
     * Detail d'un sort.
     */
    #[Route('/magie/sort/{sort}/detail', name: 'magie.sort.detail', requirements: ['sort' => Requirement::DIGITS])]
    public function sortDetailAction(#[MapEntity] Sort $sort): Response
    {
        return $this->render('sort/detail.twig', [
            'sort' => $sort,
        ]);
    }

    /**
     * Ajoute un sort.
     */
    #[Route('/magie/sort/add', name: 'magie.sort.add', requirements: ['sort' => Requirement::DIGITS])]
    public function sortAddAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $sort = new Sort();

        $form = $this->createForm(SortForm::class, $sort)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sort = $form->getData();

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $this->addFlash(
                        'error',
                        'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)'
                    );

                    return $this->redirectToRoute('magie.sort.list', [], 303);
                }

                $documentFilename = hash('md5', $sort->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $sort->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($sort);
            $entityManager->flush();

            $this->addFlash('success', 'Le sort a été ajouté');

            return $this->redirectToRoute('magie.sort.detail', ['sort' => $sort->getId()], 303);
        }

        return $this->render('sort/add.twig', [
            'sort' => $sort,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un sort.
     */
    #[Route('/magie/sort/{sort}/update', name: 'magie.sort.update', requirements: ['sort' => Requirement::DIGITS])]
    public function sortUpdateAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Sort $sort): RedirectResponse|Response
    {
        $form = $this->createForm(SortForm::class, $sort)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sort = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                    $this->addFlash(
                        'error',
                        'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)'
                    );

                    return $this->redirectToRoute('magie.sort.list', [], 303);
                }

                $documentFilename = hash('md5', $sort->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $sort->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($sort);
            $entityManager->flush();

            $this->addFlash('success', 'Le sort a été sauvegardé');

            return $this->redirectToRoute('magie.sort.detail', ['sort' => $sort->getId()], 303);
        }

        return $this->render('sort/update.twig', [
            'sort' => $sort,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un sortilège.
     */
    #[Route('/magie/sort/{sort}/delete', name: 'magie.sort.delete', requirements: ['sort' => Requirement::DIGITS])]
    public function sortDeleteAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Sort $sort): RedirectResponse|Response
    {
        $form = $this->createForm(SortDeleteForm::class, $sort)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sort = $form->getData();

            $entityManager->remove($sort);
            $entityManager->flush();

            $this->addFlash('success', 'Le sort a été supprimé');

            return $this->redirectToRoute('magie.sort.list', [], 303);
        }

        return $this->render('sort/delete.twig', [
            'sort' => $sort,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a un sort.
     */
    #[Route('/magie/sort/{sort}/document/{document}', name: 'magie.sort.document', requirements: ['sort' => Requirement::DIGITS])]
    public function getSortDocumentAction(#[MapEntity] Sort $sort, #[MapEntity] Document $document)
    {
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

        $file = __DIR__.'/../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$sort->getPrintLabel().'.pdf"',
        ]);
    }

    #[Route('/magie/sort/{sort}/personnages', name: 'magie.sort.personnages', requirements: ['sort' => Requirement::DIGITS])]
    public function sortPersonnagesAction(Request $request, #[MapEntity] Sort $sort): Response
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
