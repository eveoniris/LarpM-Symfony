<?php


namespace App\Controller;

use App\Entity\Loi;
use App\Entity\Territoire;
use JasonGrimes\Paginator;
use App\Form\Territoire\FiefForm;
use App\Form\Territoire\TerritoireBlasonForm;
use App\Form\Territoire\TerritoireCiblesForm;
use App\Form\Territoire\TerritoireCultureForm;
use App\Form\Territoire\TerritoireDeleteForm;
use App\Form\Territoire\TerritoireForm;
use App\Form\Territoire\TerritoireIngredientsForm;
use App\Form\Territoire\TerritoireLoiForm;
use App\Form\Territoire\TerritoireStatutForm;
use App\Form\Territoire\TerritoireStrategieForm;
use LarpManager\Repository\ConstructionRepository;
use LarpManager\Repository\TerritoireRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_CARTOGRAPHE')]
class TerritoireController extends AbstractController
{
    /**
     * Modifier les listes de cibles pour les quêtes commerciales.
     */
    public function updateCiblesAction(Request $request,  EntityManagerInterface $entityManager, Territoire $territoire)
    {
        $form = $this->createForm(TerritoireCiblesForm::class, $territoire)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();

           $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $this->render('admin/territoire/cibles.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des territoires.
     */
    #[Route('/territoire', name: 'territoire.list')]
    public function listAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoires = $entityManager->getRepository('\\'.\App\Entity\Territoire::class)->findRoot();

        return $this->render('admin/territoire/list.twig', ['territoires' => $territoires]);
    }

    /**
     * Liste des fiefs.
     */
    #[Route('/territoire/fief', name: 'territoire.fief')]
    public function fiefAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 500);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $formData = $request->query->get('personnageFind');
        $pays = isset($formData['pays']) ? $entityManager->find(\App\Entity\Territoire::class, $formData['pays']) : null;
        $province = isset($formData['provinces']) ? $entityManager->find(\App\Entity\Territoire::class, $formData['provinces']) : null;
        $groupe = isset($formData['groupe']) ? $entityManager->find(\App\Entity\Groupe::class, $formData['groupe']) : null;
        $optionalParameters = '';

        $listeGroupes = $entityManager->getRepository('\\'.\App\Entity\Groupe::class)->findList(null, null, ['by' => 'nom', 'dir' => 'ASC'], 1000, 0);
        $listePays = $entityManager->getRepository('\\'.\App\Entity\Territoire::class)->findRoot();
        $listeProvinces = $entityManager->getRepository('\\'.\App\Entity\Territoire::class)->findProvinces();

        $form = $this->createForm(
            new FiefForm(),
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
        )->getForm();

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
        $repo = $entityManager->getRepository('\\'.\App\Entity\Territoire::class);
        $fiefs = $repo->findFiefsList(
            $criteria,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset
        );

        $numResults = count($fiefs);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('territoire.fief').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir.$optionalParameters
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
    public function printAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoires = $entityManager->getRepository('\\'.\App\Entity\Territoire::class)->findFiefs();

        return $this->render('admin/territoire/print.twig', ['territoires' => $territoires]);
    }

    /**
     * Liste des fiefs pour les quêtes.
     */
    public function queteAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoires = $entityManager->getRepository('\\'.\App\Entity\Territoire::class)->findFiefs();

        return $this->render('admin/territoire/quete.twig', ['territoires' => $territoires]);
    }

    /**
     * Liste des pays avec le nombre de noble.
     */
    public function nobleAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoires = $entityManager->getRepository('\\'.\App\Entity\Territoire::class)->findRoot();

        return $this->render('admin/territoire/noble.twig', ['territoires' => $territoires]);
    }

    /**
     * Detail d'un territoire pour les joueurs.
     */
    public function detailJoueurAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoire = $request->get('territoire');

        return $this->render('public/territoire/detail.twig', ['territoire' => $territoire]);
    }

    /**
     * Detail d'un territoire.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoire = $request->get('territoire');

        return $this->render('admin/territoire/detail.twig', ['territoire' => $territoire]);
    }

    /**
     * Ajoute une loi à un territoire.
     */
    public function updateLoiAction(Request $request,  EntityManagerInterface $entityManager, Territoire $territoire)
    {
        $form = $this->createForm(TerritoireLoiForm::class, $territoire)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();

           $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $this->render('admin/territoire/loi.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute une construction dans un territoire.
     */
    public function constructionAddAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoire = $request->get('territoire');

        $form = $this->createForm()
            ->add('construction', 'entity', [
                'label' => 'Choisissez la construction',
                'required' => true,
                'class' => \App\Entity\Construction::class,
                'query_builder' => static function (ConstructionRepository $repo) {
                    return $repo->createQueryBuilder('c')->orderBy('c.label', 'ASC');
                },
                'property' => 'fullLabel',
                'expanded' => true,
            ])
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $territoire->addConstruction($data['construction']);
            $entityManager->persist($territoire);
            $entityManager->flush();

           $this->addFlash('success', 'La construction a été ajoutée.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $this->render('admin/territoire/addConstruction.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retire une construction d'un territoire.
     */
    public function constructionRemoveAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoire = $request->get('territoire');
        $construction = $request->get('construction');

        $form = $this->createForm()
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Retirer la construction']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $territoire->removeConstruction($construction);
            $entityManager->persist($territoire);
            $entityManager->flush();

           $this->addFlash('success', 'La construction a été retiré.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $this->render('admin/territoire/removeConstruction.twig', [
            'territoire' => $territoire,
            'construction' => $construction,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un territoire.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoire = new \App\Entity\Territoire();

        $form = $this->createForm(TerritoireForm::class, $territoire)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder & continuer']);

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
                return $this->redirectToRoute('territoire.admin.list', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('territoire.admin.add', [], 303);
            }
        }

        return $this->render('admin/territoire/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour de la liste des ingrédients fourni par un territoire.
     */
    public function updateIngredientsAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoire = $request->get('territoire');

        $form = $this->createForm(TerritoireIngredientsForm::class, $territoire)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();

           $this->addFlash('success', 'Le territoire a été mis à jour.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $this->render('admin/territoire/updateIngredients.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie un territoire.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoire = $request->get('territoire');

        $form = $this->createForm(TerritoireForm::class, $territoire)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();
           $this->addFlash('success', 'Le territoire a été mis à jour.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $this->render('admin/territoire/update.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour la culture d'un territoire.
     */
    public function updateCultureAction(Request $request,  EntityManagerInterface $entityManager, Territoire $territoire)
    {
        $form = $this->createForm(TerritoireCultureForm::class, $territoire)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($territoire);
            $entityManager->flush();

           $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $this->render('admin/territoire/culture.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour le statut d'un territoire.
     */
    public function updateStatutAction(Request $request,  EntityManagerInterface $entityManager, Territoire $territoire)
    {
        $form = $this->createForm(TerritoireStatutForm::class, $territoire)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($territoire);
            $entityManager->flush();

           $this->addFlash('success', 'Le territoire a été mis à jour');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $this->render('admin/territoire/updateStatut.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour le blason d'un territoire.
     */
    public function updateBlasonAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoire = $request->get('territoire');

        $form = $this->createForm(TerritoireBlasonForm::class, $territoire)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $request->files->get($form->getName());

            $path = __DIR__.'/../../../private/img/blasons/';
            $filename = $files['blason']->getClientOriginalName();
            $extension = $files['blason']->guessExtension();

            if (!$extension || !in_array($extension, ['png', 'jpg', 'jpeg', 'bmp'])) {
               $this->addFlash('error', 'Désolé, votre image ne semble pas valide (vérifiez le format de votre image)');

                return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
            }

            $blasonFilename = hash('md5', $this->getUser()->getUsername().$filename.time()).'.'.$extension;

            $image = $app['imagine']->open($files['blason']->getPathname());
            $image->resize($image->getSize()->widen(160));
            $image->save($path.$blasonFilename);

            $territoire->setBlason($blasonFilename);
            $entityManager->persist($territoire);
            $entityManager->flush();

           $this->addFlash('success', 'Le blason a été enregistré');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $this->render('admin/territoire/blason.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie le jeu strategique d'un territoire.
     */
    public function updateStrategieAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoire = $request->get('territoire');

        $form = $this->createForm(TerritoireStrategieForm::class, $territoire)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $territoire = $form->getData();

            $entityManager->persist($territoire);
            $entityManager->flush();
           $this->addFlash('success', 'Le territoire a été mis à jour.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $this->render('admin/territoire/updateStrategie.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supression d'un territoire.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoire = $request->get('territoire');

        $form = $this->createForm(TerritoireDeleteForm::class, $territoire)
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

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

            return $this->redirectToRoute('territoire.admin.list', [], 303);
        }

        return $this->render('admin/territoire/delete.twig', [
            'territoire' => $territoire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajout d'un topic pour un territoire.
     */
    public function addTopicAction(Request $request,  EntityManagerInterface $entityManager)
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

        return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
    }

    /**
     * Supression d'un topic pour un territoire.
     */
    public function deleteTopicAction(Request $request,  EntityManagerInterface $entityManager)
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

        return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
    }

    /**
     * Ajoute un événement à un territoire.
     */
    public function eventAddAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoire = $request->get('territoire');
        $event = $request->get('event');

        $event = new \App\Entity\Chronologie();

        $form = $this->createForm(EventForm::class, $event)
            ->add('add', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Ajouter']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();

            $entityManager->persist($event);
            $entityManager->flush();
           $this->addFlash('success', 'L\'evenement a été ajouté.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $this->render('admin/territoire/addEvent.twig', [
            'territoire' => $territoire,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un événement.
     */
    public function eventUpdateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoire = $request->get('territoire');
        $event = $request->get('event');

        $form = $this->createForm(ChronologieForm::class, $event)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Mettre à jour']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();

            $entityManager->persist($event);
            $entityManager->flush();
           $this->addFlash('success', 'L\'evenement a été modifié.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $this->render('admin/territoire/updateEvent.twig', [
            'territoire' => $territoire,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime un événement.
     */
    public function eventDeleteAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $territoire = $request->get('territoire');
        $event = $request->get('event');

        $form = $this->createForm(ChronologieDeleteForm::class, $event)
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();

            $entityManager->remove($event);
            $entityManager->flush();
           $this->addFlash('success', 'L\'evenement a été supprimé.');

            return $this->redirectToRoute('territoire.admin.detail', ['territoire' => $territoire->getId()], [], 303);
        }

        return $this->render('admin/territoire/deleteEvent.twig', [
            'territoire' => $territoire,
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }
}
