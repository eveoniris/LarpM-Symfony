<?php


namespace App\Controller;

use App\Entity\SecondaryGroup;
use JasonGrimes\Paginator;
use App\Form\GroupeSecondaire\GroupeSecondaireForm;
use App\Form\GroupeSecondaire\GroupeSecondaireMaterielForm;
use App\Form\GroupeSecondaire\GroupeSecondaireNewMembreForm;
use App\Form\GroupeSecondaire\SecondaryGroupFindForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class GroupeSecondaireController extends AbstractController
{
    /**
     * Liste des groupes secondaires (pour les orgas).
     */
    #[Route('/groupeSecondaire', name: 'groupeSecondaire.list')]
    public function adminListAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $form = $this->createForm(
            new SecondaryGroupFindForm(),
            null,
            [
                'method' => 'get',
                'csrf_protection' => false,
            ]
        )
            ->add('find', 'submit', ['label' => 'Rechercher']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            switch ($type) {
                case 'nom':
                    // $criteria[] = new LikeExpression("p.nom", "%$value%");
                    $criteria['nom'] = "g.label like '%".preg_replace('/[\'"<>=*;]/', '', (string) $value)."%'";
                    break;
                case 'id':
                    // $criteria[] = new EqualExpression("p.id", $value);
                    $criteria['id'] = 'g.id = '.preg_replace('/[^\d]/', '', (string) $value);
                    break;
            }
        }

        /* @var SecondaryGroupRepository $repo */
        $repo = $entityManager->getRepository('\\'.\App\Entity\SecondaryGroup::class);
        $groupeSecondaires = $repo->findList(
            $criteria,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset
        );

        $numResults = $repo->findCount($criteria);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('groupeSecondaire.admin.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $this->render('admin/groupeSecondaire/list.twig', [
            'groupeSecondaires' => $groupeSecondaires,
            'paginator' => $paginator,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un groupe secondaire.
     */
    public function adminAddAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $groupeSecondaire = new \App\Entity\SecondaryGroup();

        $form = $this->createForm(GroupeSecondaireForm::class(), $groupeSecondaire)
            ->add('save', 'submit', ['label' => 'Sauvegarder'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeSecondaire = $form->getData();

            /**
             * Création des topics associés à ce groupe
             * un topic doit être créé par GN auquel ce groupe est inscrit.
             *
             * @var \App\Entity\Topic $topic
             */
            $topic = new \App\Entity\Topic();
            $topic->setTitle($groupeSecondaire->getLabel());
            $topic->setDescription($groupeSecondaire->getDescription());
            $topic->setUser($this->getUser());
            $topic->setTopic($app['larp.manager']->findTopic('TOPIC_GROUPE_SECONDAIRE'));
            $entityManager->persist($topic);

            $groupeSecondaire->setTopic($topic);
            $entityManager->persist($groupeSecondaire);
            $entityManager->flush();

            // défini les droits d'accés à ce forum
            // (les membres du groupe ont le droit d'accéder à ce forum)
            $topic->setObjectId($groupeSecondaire->getId());
            $topic->setRight('GROUPE_SECONDAIRE_MEMBER');

            /**
             * Ajoute le responsable du groupe dans le groupe si il n'y est pas déjà.
             */
            $personnage = $groupeSecondaire->getResponsable();
            if ($personnage && !$groupeSecondaire->isMembre($personnage)) {
                $membre = new \App\Entity\Membre();
                $membre->setPersonnage($personnage);
                $membre->setSecondaryGroup($groupeSecondaire);
                $membre->setSecret(false);
                $entityManager->persist($membre);
                $entityManager->flush();
                $groupeSecondaire->addMembre($membre);
            }

            $entityManager->persist($topic);
            $entityManager->persist($groupeSecondaire);
            $entityManager->flush();

           $this->addFlash('success', 'Le groupe secondaire a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('groupeSecondaire.admin.list', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('groupeSecondaire.admin.add', [], 303);
            }
        }

        return $this->render('admin/groupeSecondaire/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Mise à jour du matériel necessaire à un groupe secondaire.
     */
    public function materielUpdateAction(Request $request,  EntityManagerInterface $entityManager, SecondaryGroup $groupeSecondaire)
    {
        $form = $this->createForm(GroupeSecondaireMaterielForm::class(), $groupeSecondaire)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeSecondaire = $form->getData();
            $entityManager->persist($groupeSecondaire);
            $entityManager->flush();
           $this->addFlash('success', 'Le groupe secondaire a été mis à jour.');

            return $this->redirectToRoute('groupeSecondaire.admin.detail', ['groupe' => $groupeSecondaire->getId()], [], 303);
        }

        return $this->render('admin/groupeSecondaire/materiel.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Impression de l'enveloppe du groupe secondaire.
     */
    public function materielPrintAction(Request $request,  EntityManagerInterface $entityManager, SecondaryGroup $groupeSecondaire)
    {
        return $this->render('admin/groupeSecondaire/print.twig', [
            'groupeSecondaire' => $groupeSecondaire,
        ]);
    }

    /**
     * Impression de toutes les enveloppes groupe secondaire.
     */
    public function materielPrintAllAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $groupeSecondaires = $entityManager->getRepository('\\'.\App\Entity\SecondaryGroup::class)->findAll();

        return $this->render('admin/groupeSecondaire/printAll.twig', [
            'groupeSecondaires' => $groupeSecondaires,
        ]);
    }

    /**
     * Met à jour un de groupe secondaire.
     */
    public function adminUpdateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $groupeSecondaire = $request->get('groupe');

        $form = $this->createForm(GroupeSecondaireForm::class(), $groupeSecondaire)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeSecondaire = $form->getData();

            if ($form->get('update')->isClicked()) {
                /**
                 * Ajoute le responsable du groupe dans le groupe si il n'y est pas déjà.
                 */
                $personnage = $groupeSecondaire->getResponsable();
                if (!$groupeSecondaire->isMembre($personnage)) {
                    $membre = new \App\Entity\Membre();
                    $membre->setPersonnage($personnage);
                    $membre->setSecondaryGroup($groupeSecondaire);
                    $membre->setSecret(false);

                    $entityManager->persist($membre);
                    $entityManager->flush();

                    $groupeSecondaire->addMembre($membre);
                }
                /*
                 * Retire la candidature du responsable si elle existe
                 */
                foreach ($groupeSecondaire->getPostulants() as $postulant) {
                    if ($postulant->getPersonnage() == $personnage) {
                        $entityManager->remove($postulant);
                    }
                }
                $entityManager->persist($groupeSecondaire);
                $entityManager->flush();
               $this->addFlash('success', 'Le groupe secondaire a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($groupeSecondaire);
                $entityManager->flush();
               $this->addFlash('success', 'Le groupe secondaire a été supprimé.');
            }

            return $this->redirectToRoute('groupeSecondaire.admin.list');
        }

        return $this->render('admin/groupeSecondaire/update.twig', [
            'groupeSecondaire' => $groupeSecondaire,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Construit le contexte pour la page détail de groupe secondaire (pour les orgas).
     *
     * @return array of
     */
    public function buildContextDetailTwig( EntityManagerInterface $entityManager, SecondaryGroup $groupeSecondaire, array $extraParameters = null): array
    {
        $gnActif = $app['larp.manager']->getGnActif();
        $result = [
            'groupeSecondaire' => $groupeSecondaire,
            'gn' => $gnActif,
        ];

        if (null == $extraParameters) {
            return $result;
        }

        return array_merge($result, $extraParameters);
    }

    /**
     * Détail d'un groupe secondaire (pour les orgas).
     */
    public function adminDetailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $groupeSecondaire = $request->get('groupe');

        return $this->render('admin/groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire)
        );
    }

    /**
     * Ajoute un nouveau membre au groupe secondaire.
     */
    public function adminNewMembreAction(Request $request,  EntityManagerInterface $entityManager, SecondaryGroup $groupeSecondaire)
    {
        $form = $this->createForm(new GroupeSecondaireNewMembreForm())->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form['personnage']->getData();
            // $personnage = $data['personnage'];

            $membre = new \App\Entity\Membre();

            if ($groupeSecondaire->isMembre($personnage)) {
               $this->addFlash('warning', 'le personnage est déjà membre du groupe secondaire.');

                return $this->redirectToRoute('groupeSecondaire.admin.detail', ['groupe' => $groupeSecondaire->getId()], [], 303);
            }

            $membre->setPersonnage($personnage);
            $membre->setSecondaryGroup($groupeSecondaire);
            $membre->setSecret(false);

            $entityManager->persist($membre);
            $entityManager->flush();

           $this->addFlash('success', 'le personnage a été ajouté au groupe secondaire.');

            return $this->redirectToRoute('groupeSecondaire.admin.detail', ['groupe' => $groupeSecondaire->getId()], [], 303);
        }

        return $this->render('admin/groupeSecondaire/newMembre.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire, ['form' => $form->createView()])
        );
    }

    /**
     * Retire un postulant du groupe.
     */
    public function adminRemovePostulantAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $groupeSecondaire = $request->get('groupe');
        $postulant = $request->get('postulant');

        $entityManager->remove($postulant);
        $entityManager->flush();

       $this->addFlash('success', 'la candidature a été supprimée.');

        return $this->render('admin/groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire)
        );
    }

    /**
     * Accepte un postulant dans le groupe.
     */
    public function adminAcceptPostulantAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $groupeSecondaire = $request->get('groupe');
        $postulant = $request->get('postulant');

        $personnage = $postulant->getPersonnage();

        $membre = new \App\Entity\Membre();
        $membre->setPersonnage($personnage);
        $membre->setSecondaryGroup($groupeSecondaire);
        $membre->setSecret(false);

        if ($groupeSecondaire->isMembre($personnage)) {
           $this->addFlash('warning', 'le personnage est déjà membre du groupe secondaire.');
        } else {
            $entityManager->persist($membre);
            $entityManager->remove($postulant);
            $entityManager->flush();

            $app['User.mailer']->sendGroupeSecondaireAcceptMessage($personnage->getUser(), $groupeSecondaire);

           $this->addFlash('success', 'la candidature a été accepté.');
        }

        return $this->render('admin/groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire)
        );
    }

    /**
     * Retire un membre du groupe.
     */
    public function adminRemoveMembreAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $groupeSecondaire = $request->get('groupe');
        $membre = $request->get('membre');

        $entityManager->remove($membre);
        $entityManager->flush();

       $this->addFlash('success', 'le membre a été retiré.');

        return $this->render('admin/groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire)
        );
    }

    /**
     * Retirer le droit de lire les secrets à un utilisateur.
     *
     * @param Applicetion $app
     */
    public function adminSecretOffAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $groupeSecondaire = $request->get('groupe');
        $membre = $request->get('membre');

        $membre->setSecret(false);
        $entityManager->persist($membre);
        $entityManager->flush();

        return $this->render('admin/groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire)
        );
    }

    /**
     * Active le droit de lire les secrets à un utilisateur.
     *
     * @param Applicetion $app
     */
    public function adminSecretOnAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $groupeSecondaire = $request->get('groupe');
        $membre = $request->get('membre');

        $membre->setSecret(true);
        $entityManager->persist($membre);
        $entityManager->flush();

        return $this->render('admin/groupeSecondaire/detail.twig',
            $this->buildContextDetailTwig($app, $groupeSecondaire)
        );
    }
}
