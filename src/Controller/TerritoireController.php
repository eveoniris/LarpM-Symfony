<?php

namespace App\Controller;

use App\Entity\Loi;
use App\Entity\Territoire;
use App\Entity\Construction;
use App\Entity\Groupe;
use App\Form\Territoire\FiefForm;
use App\Form\Territoire\TerritoireBlasonForm;
use App\Form\Territoire\TerritoireCiblesForm;
use App\Form\Territoire\TerritoireCultureForm;
use App\Form\Territoire\TerritoireDeleteForm;
use App\Form\Territoire\TerritoireForm;
use App\Form\Territoire\TerritoireIngredientsForm;
use App\Form\Territoire\TerritoireConstructionForm;
use App\Form\Territoire\TerritoireLoiForm;
use App\Form\Territoire\TerritoireStatutForm;
use App\Form\Territoire\TerritoireStrategieForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CARTOGRAPHE')]
class TerritoireController extends AbstractController
{
    /**
     * Modifier les listes de cibles pour les quêtes commerciales.
     */
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    #[Route('/admin/territoire/{territoire}/updateCibles', name: 'territoire.admin.updateCibles')]
    public function updateCiblesAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire): RedirectResponse|Response
    {
        $form = $this->createForm(TerritoireCiblesForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/cibles.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des territoires.
     */
    #[Route('/territoire', name: 'territoire.list')]
    public function listAction(Request $request, EntityManagerInterface $entityManager)
    {
        $territoirenRepository = $entityManager->getRepository(Territoire::class);

        $orderBy = $this->getRequestOrder(
            alias: 't',
            allowedFields: $territoirenRepository->getFieldNames()
        );

        $qb = $entityManager->createQueryBuilder('t')
            ->select('t')
            ->from(Territoire::class, 't')
            ->where('t.territoire IS NULL')
            ->orderBy(key($orderBy), current($orderBy))
        ;

        $paginator = $territoirenRepository->findPaginatedQuery(
            $qb->getQuery(), $this->getRequestLimit(10), $this->getRequestPage()
        );

        return $this->render('territoire/list.twig', ['paginator' => $paginator]);
    }

    /**
     * Liste des fiefs.
     */
    #[Route('/territoire/fief', name: 'territoire.fief')]
    public function fiefAction(Request $request, EntityManagerInterface $entityManager)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' === $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $formData = $request->query->get('personnageFind');
        $pays = isset($formData['pays']) ? $entityManager->find(Territoire::class, $formData['pays']) : null;
        $province = isset($formData['provinces']) ? $entityManager->find(Territoire::class, $formData['provinces']) : null;
        $groupe = isset($formData['groupe']) ? $entityManager->find(Groupe::class, $formData['groupe']) : null;
        $optionalParameters = '';

        //$listeGroupes = $entityManager->getRepository('\\'.Groupe::class)->findList(null, null, ['by' => 'nom', 'dir' => 'ASC'], 1000, 0);
        $listeGroupes = $entityManager->getRepository('\\'.Groupe::class)->findBy([], ['nom' => 'ASC']);
        $listePays = $entityManager->getRepository('\\'.Territoire::class)->findRoot();
        $listeProvinces = $entityManager->getRepository('\\'.Territoire::class)->findProvinces();

        $form = $this->createForm(FiefForm::class,
            null,
            [
                'data' => [
                    'pays' => $pays,
                    'province' => $province,
                    'groupe' => $groupe,
                ],
                'listeGroupes' => $listeGroupes,
                'listePays' => $listePays,
                'listeProvinces' => $listeProvinces,
                'method' => 'get',
                'csrf_protection' => false,
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            $pays = $data['pays'] ? $data['pays'] : null;
            $province = $data['province'] ? $data['province'] : null;
            $groupe = $data['groupe'] ? $data['groupe'] : null;

            if ($type && $value) {
                switch ($type) {
                    case 'idFief':
                        $criteria['t.id'] = $value;
                        break;
                    case 'nomFief':
                        $criteria['t.nom'] = $value;
                        break;
                }
            }
        }

        if ($groupe) {
            $criteria['tgr.id'] = $groupe->getId();
            $optionalParameters .= '&fief[groupe]='.$groupe->getId();
        }

        if ($pays) {
            $criteria['tp.id'] = $pays->getId();
            $optionalParameters .= '&fief[pays]='.$pays->getId();
        }

        if ($province) {
            $criteria['tpr.id'] = $province->getId();
            $optionalParameters .= '&fief[province]='.$province->getId();
        }

        /* @var TerritoireRepository $repo */
        $repo = $entityManager->getRepository('\\'.Territoire::class);
        $fiefs = $repo->findFiefsList(
            $limit,
            $offset,
            $criteria,
            ['by' => $order_by, 'dir' => $order_dir]
        );

        //$numResults = count($fiefs);

        //$paginator = new Paginator($numResults, $limit, $page,
        //    $app['url_generator']->generate('territoire.fief').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir.$optionalParameters
        //);

        $paginator = $repo->findPaginatedQuery(
            $fiefs, 
            $this->getRequestLimit(),
            $this->getRequestPage()
        );

        return $this->render('territoire/fief.twig', [
            'fiefs' => $fiefs,
            'form' => $form->createView(),
            'paginator' => $paginator,
            'optionalParameters' => $optionalParameters,
        ]);
    }

    /**
     * Impression des territoires.
     */
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    #[Route('/admin/territoire/print', name: 'territoire.admin.print')]
    public function printAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $territoires = $entityManager->getRepository('\\'.Territoire::class)->findFiefs();

        return $this->render('territoire/print.twig', ['territoires' => $territoires]);
    }

    /**
     * Liste des fiefs pour les quêtes.
     */
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    #[Route('/admin/territoire/quete', name: 'territoire.admin.quete')]
    public function queteAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $territoires = $entityManager->getRepository('\\'.Territoire::class)->findFiefs();

        return $this->render('territoire/quete.twig', ['territoires' => $territoires]);
    }

    /**
     * Liste des pays avec le nombre de noble.
     */
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    #[Route('/admin/territoire/noble', name: 'territoire.admin.noble')]
    public function nobleAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $territoires = $entityManager->getRepository('\\'.Territoire::class)->findRoot();

        return $this->render('territoire/noble.twig', ['territoires' => $territoires]);
    }

    /**
     * Detail d'un territoire pour les joueurs.
     */
    #[Route('/territoire/{territoire}', name: 'territoire.detail')]
    public function detailJoueurAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire): Response
    {
        return $this->render('territoire/detail.twig', ['territoire' => $territoire]);
    }

    /**
     * Ajoute une loi à un territoire.
     */
    #[Route('/territoire/{territoire}/updateLoi', name: 'territoire.updateLoi')]
    public function updateLoiAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire): RedirectResponse|Response
    {
        $form = $this->createForm(TerritoireLoiForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/loi.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute une construction dans un territoire.
     */
    #[Route('/territoire/{territoire}/constructionAdd', name: 'territoire.constructionAdd')]
    public function constructionAddAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire): RedirectResponse|Response
    {
        $form = $this->createForm(TerritoireConstructionForm::class, $territoire)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            //$territoire->addConstruction($data['constructions']);
            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'La construction a été ajoutée.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/addConstruction.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retire une construction d'un territoire.
     */
    #[Route('/territoire/{territoire}/constructionRemove/{construction}', name: 'territoire.constructionRemove')]
    public function constructionRemoveAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire, #[MapEntity] Construction $construction): RedirectResponse|Response
    {
        //$construction = $request->get('construction');

        //dump($construction);

        $form = $this->createFormBuilder($territoire)
            ->add('save', SubmitType::class, ['label' => 'Retirer la construction'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $territoire->removeConstruction($construction);
            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'La construction a été retirée.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/removeConstruction.twig', [
            'territoire' => $territoire,
            'construction' => $construction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un territoire.
     */
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    #[Route('/territoire/admin/add', name: 'territoire.admin.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $territoire = new Territoire();

        $form = $this->createForm(TerritoireForm::class, $territoire)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            /**
             * Création des topics associés à ce groupe
             * un topic doit être créé par GN auquel ce groupe est inscrit.
             *
             * @var \App\Entity\Topic $topic
             */
            $topic = new \App\Entity\Topic();
            $topic->setTitle($territoire->getNom());
            $topic->setDescription($territoire->getDescription());
            $topic->setUser($this->getUser());
            // défini les droits d'accés à ce forum
            // (les membres du groupe ont le droit d'accéder à ce forum)
            $topic->setRight('TERRITOIRE_MEMBER');
            $topic->setTopic($app['larp.manager']->findTopic('TOPIC_TERRITOIRE'));

            $territoire->setTopic($topic);

            $entityManager->persist($topic);
            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'Le territoire a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('territoire.list', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('territoire.add', [], 303);
            }
        }

        return $this->render('territoire/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour de la liste des ingrédients fourni par un territoire.
     */
    #[Route('/territoire/{territoire}/updateIngredients', name: 'territoire.updateIngredients')]
    public function updateIngredientsAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire): RedirectResponse|Response
    {
        $form = $this->createForm(TerritoireIngredientsForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'Le territoire a été mis à jour.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/updateIngredients.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie un territoire.
     */
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    #[Route('/admin/territoire/{territoire}/update', name: 'territoire.admin.update')]
    public function updateAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire): RedirectResponse|Response
    {
        $form = $this->createForm(TerritoireForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();
            $this->addFlash('success', 'Le territoire a été mis à jour.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/update.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour la culture d'un territoire.
     */
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    #[Route('/admin/territoire/{territoire}/updateCulture', name: 'territoire.admin.updateCulture')]
    public function updateCultureAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire): RedirectResponse|Response
    {
        $form = $this->createForm(TerritoireCultureForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/culture.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour le statut d'un territoire.
     */
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    #[Route('/admin/territoire/{territoire}/updateStatut', name: 'territoire.admin.updateStatut')]
    public function updateStatutAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire)
    {
        $form = $this->createForm(TerritoireStatutForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/updateStatut.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Récupération de l'image du blason d'un territoire.
     */
    #[Route('/territoire/{territoire}/blason', name: 'territoire.blason', methods: ['GET'])]
    public function blasonAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire): Response
	{
		$blason = $territoire->getBlason();
        $filename = __DIR__.'/../../assets/img/blasons/'.$blason;
        
        $response = new Response(file_get_contents($filename));
        $response->headers->set('Content-Type', 'image/png');

        return $response;
	}

    /**
     * Met à jour le blason d'un territoire.
     */
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    #[Route('/territoire/{territoire}/updateBlason', name: 'territoire.updateBlason')]
    public function updateBlasonAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire)
    {
        $form = $this->createForm(TerritoireBlasonForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $request->files->get($form->getName());

            $path = __DIR__.'/../../assets/img/blasons/';
            $filename = $files['blason']->getClientOriginalName();
            $extension = $files['blason']->guessExtension();

            if (!$extension || !in_array($extension, ['png', 'jpg', 'jpeg', 'bmp'])) {
                $this->addFlash('error', 'Désolé, votre image ne semble pas valide (vérifiez le format de votre image)');

                return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
            }

            $blasonFilename = hash('md5', $this->getUser()->getUsername().$filename.time()).'.'.$extension;

            $imagine = new \Imagine\Gd\Imagine();
            $image = $imagine->open($files['blason']->getPathname());
            $image->resize($image->getSize()->widen(160));
            $image->save($path.$blasonFilename);

            $territoire->setBlason($blasonFilename);
            $entityManager->persist($territoire);
            $entityManager->flush();

            $this->addFlash('success', 'Le blason a été enregistré');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/blason.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie le jeu strategique d'un territoire.
     */
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access to this.')]
    #[Route('/admin/territoire/{territoire}/update/strategie', name: 'territoire.admin.updateStrategie')]
    public function updateStrategieAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire)
    {
        $form = $this->createForm(TerritoireStrategieForm::class, $territoire)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();
            $this->addFlash('success', 'Le territoire a été mis à jour.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/updateStrategie.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supression d'un territoire.
     */
    #[Route('/territoire/{territoire}/delete', name: 'territoire.delete')]
    public function deleteAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire)
    {
        $form = $this->createForm(TerritoireDeleteForm::class, $territoire)
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            foreach ($territoire->getPersonnages() as $personnage) {
                $personnage->setTerritoire(null);
                $entityManager->persist($personnage);
            }

            foreach ($territoire->getGroupes() as $groupe) {
                $groupe->removeTerritoire($territoire);
                $entityManager->persist($groupe);
            }

            if ($territoire->getGroupe()) {
                $groupe = $territoire->getGroupe();
                $groupe->setTerritoire(null);
                $entityManager->persist($groupe);
            }

            $entityManager->remove($territoire);
            $entityManager->flush();
            $this->addFlash('success', 'Le territoire a été supprimé.');

            return $this->redirectToRoute('territoire.list', [], 303);
        }

        return $this->render('territoire/delete.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajout d'un topic pour un territoire.
     */
    #[Route('/territoire/{territoire}/addTopic', name: 'territoire.addTopic')]
    public function addTopicAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire)
    {
        $territoire = $request->get('territoire');

        $topic = new \App\Entity\Topic();
        $topic->setTitle($territoire->getNom());
        $topic->setDescription($territoire->getDescription());
        $topic->setUser($this->getUser());
        $topic->setRight('TERRITOIRE_MEMBER');
        $topic->setObjectId($territoire->getId());
        $topic->addTerritoire($territoire);
        $topic->setTopic($app['larp.manager']->findTopic('TOPIC_TERRITOIRE'));

        $territoire->setTopic($topic);

        $entityManager->persist($topic);
        $entityManager->persist($territoire);
        $entityManager->flush();

        $this->addFlash('success', 'Le topic a été ajouté.');

        return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
    }

    /**
     * Supression d'un topic pour un territoire.
     */
    #[Route('/territoire/{territoire}/deleteTopic', name: 'territoire.deleteTopic')]
    public function deleteTopicAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire) : RedirectResponse
    {
        $territoire = $request->get('territoire');

        $topic = $territoire->getTopic();

        if ($topic) {
            $territoire->setTopic(null);

            $entityManager->persist($territoire);
            $entityManager->remove($topic);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Le topic a été supprimé.');

        return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
    }

    /**
     * Ajoute un événement à un territoire.
     */
    #[Route('/territoire/{territoire}/eventAdd', name: 'territoire.eventAdd')]
    public function eventAddAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire)
    {
        $event = $request->get('event');

        $event = new \App\Entity\Chronologie();

        $form = $this->createForm(EventForm::class, $event)
            ->add('add', SubmitType::class, ['label' => 'Ajouter']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();

            $entityManager->persist($event);
            $entityManager->flush();
            $this->addFlash('success', 'L\'evenement a été ajouté.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/addEvent.twig', [
            'territoire' => $territoire,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un événement.
     */
    #[Route('/territoire/{territoire}/eventUpdate', name: 'territoire.eventUpdate')]
    public function eventUpdateAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire)
    {
        $event = $request->get('event');

        $form = $this->createForm(ChronologieForm::class, $event)
            ->add('update', SubmitType::class, ['label' => 'Mettre à jour']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();

            $entityManager->persist($event);
            $entityManager->flush();
            $this->addFlash('success', 'L\'evenement a été modifié.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/updateEvent.twig', [
            'territoire' => $territoire,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un événement.
     */
    #[Route('/territoire/{territoire}/eventDelete', name: 'territoire.eventDelete')]
    public function eventDeleteAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Territoire $territoire)
    {
        $territoire = $request->get('territoire');
        $event = $request->get('event');

        $form = $this->createForm(ChronologieDeleteForm::class, $event)
            ->add('delete', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();

            $entityManager->remove($event);
            $entityManager->flush();
            $this->addFlash('success', 'L\'evenement a été supprimé.');

            return $this->redirectToRoute('territoire.detail', ['territoire' => $territoire->getId()], 303);
        }

        return $this->render('territoire/deleteEvent.twig', [
            'territoire' => $territoire,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }
}
