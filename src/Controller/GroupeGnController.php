<?php


namespace App\Controller;

use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Form\GroupeGn\GroupeGnForm;
use App\Form\GroupeGn\GroupeGnOrdreForm;
use App\Form\GroupeGn\GroupeGnPlaceAvailableForm;
use App\Form\GroupeGn\GroupeGnResponsableForm;
use Doctrine\ORM\EntityManagerInterface;
use LarpManager\Repository\ParticipantRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LarpManager\Controllers\GroupeGnController.
 *
 * @author kevin
 */
class GroupeGnController extends AbstractController
{
    /**
     * Liste des sessions de jeu pour un groupe.
     */
    #[Route('/groupeGn/list/{groupe}/', name: 'groupeGn.list')]
    public function listAction(Request $request,  EntityManagerInterface $entityManager, Groupe $groupe)
    {
        return $this->render('groupeGn/list.twig', [
            'groupe' => $groupe,
        ]);
    }

    /**
     * Ajout d'un groupe à un jeu.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager, Groupe $groupe)
    {
        $groupeGn = new GroupeGn();
        $groupeGn->setGroupe($groupe);

        // Si ce groupe a déjà une participation à un jeu, reprendre le code/jeuStrategique/jeuMaritime/placeAvailable
        if ($groupe->getGroupeGns()->count() > 0) {
            $jeu = $groupe->getGroupeGns()->last();
            $groupeGn->setCode($jeu->getCode());
            $groupeGn->setPlaceAvailable($jeu->getPlaceAvailable());
            $groupeGn->setJeuStrategique($jeu->getJeuStrategique());
            $groupeGn->setJeuMaritime($jeu->getJeuMaritime());
        }

        $form = $this->createForm(GroupeGnForm::class, $groupeGn)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeGn = $form->getData();
            $entityManager->persist($groupeGn);
            $entityManager->flush();

           $this->addFlash('success', 'La participation au jeu a été enregistré.');

            return $this->redirectToRoute('groupe.detail', ['index' => $groupe->getId()]);
        }

        return $this->render('groupeGn/add.twig', [
            'groupe' => $groupe,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modification de la participation à un jeu du groupe.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Groupe $groupe, GroupeGn $groupeGn)
    {
        $form = $this->createForm(GroupeGnForm::class, $groupeGn)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeGn = $form->getData();
            $entityManager->persist($groupeGn);
            $entityManager->flush();

           $this->addFlash('success', 'La participation au jeu a été enregistré.');

            return $this->redirectToRoute('groupe.detail', ['index' => $groupe->getId()]);
        }

        return $this->render('groupeGn/update.twig', [
            'groupe' => $groupe,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Choisir le responsable.
     */
    public function responsableAction(Request $request,  EntityManagerInterface $entityManager, Groupe $groupe, GroupeGn $groupeGn)
    {
        $form = $this->createForm(GroupeGnResponsableForm::class, $groupeGn)
            ->add('responsable', 'entity', [
                'label' => 'Responsable',
                'required' => false,
                'class' => \App\Entity\Participant::class,
                'choice_label' => 'User.etatCivil',
                'query_builder' => static function (ParticipantRepository $er) use ($groupeGn) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->join('p.User', 'u');
                    $qb->join('p.groupeGn', 'gg');
                    $qb->join('u.etatCivil', 'ec');
                    $qb->orderBy('ec.nom', 'ASC');
                    $qb->where('gg.id = :groupeGnId');
                    $qb->setParameter('groupeGnId', $groupeGn->getId());

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Responsable',
                ],
            ])
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeGn = $form->getData();
            $entityManager->persist($groupeGn);
            $entityManager->flush();

            $app['notify']->newResponsable($groupeGn->getResponsable()->getUser(), $groupeGn);

           $this->addFlash('success', 'Le responsable du groupe a été enregistré.');

            return $this->redirectToRoute('groupeGn.list', ['groupe' => $groupeGn->getGroupe()->getId()]);
        }

        return $this->render('groupeGn/responsable.twig', [
            'groupe' => $groupe,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un participant à un groupe.
     */
    public function participantAddAction(Request $request,  EntityManagerInterface $entityManager, GroupeGn $groupeGn)
    {
        $form = $this->createForm()
            ->add('participant', 'entity', [
                'label' => 'Nouveau participant',
                'required' => true,
                'class' => \App\Entity\Participant::class,
                'choice_label' => 'User.etatCivil',
                'query_builder' => static function (ParticipantRepository $er) use ($groupeGn) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->join('p.User', 'u');
                    $qb->join('p.gn', 'gn');
                    $qb->join('u.etatCivil', 'ec');
                    $qb->where($qb->expr()->isNull('p.groupeGn'));
                    $qb->andWhere('gn.id = :gnId');
                    $qb->setParameter('gnId', $groupeGn->getGn()->getId());
                    $qb->orderBy('ec.nom', 'ASC');

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Participant',
                ],
            ])
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $data['participant']->setGroupeGn($groupeGn);
            $entityManager->persist($data['participant']);
            $entityManager->flush();

            $app['notify']->newMembre($data['participant']->getUser(), $groupeGn);

           $this->addFlash('success', 'Le joueur a été ajouté à cette session.');

            return $this->redirectToRoute('groupeGn.list', ['groupe' => $groupeGn->getGroupe()->getId()]);
        }

        return $this->render('groupeGn/participantAdd.twig', [
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un participant à un groupe (pour les chefs de groupe).
     */
    public function joueurAddAction(Request $request,  EntityManagerInterface $entityManager, GroupeGn $groupeGn)
    {
        $participant = $this->getUser()->getParticipant($groupeGn->getGn());

        $form = $this->createForm()
            ->add('participant', 'entity', [
                'label' => 'Choisissez le nouveau membre de votre groupe',
                'required' => false,
                'class' => \App\Entity\Participant::class,
                'choice_label' => 'User.Username',
                'query_builder' => static function (ParticipantRepository $er) use ($groupeGn) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->join('p.User', 'u');
                    $qb->join('p.gn', 'gn');
                    $qb->join('u.etatCivil', 'ec');
                    $qb->where($qb->expr()->isNull('p.groupeGn'));
                    $qb->andWhere('gn.id = :gnId');
                    $qb->setParameter('gnId', $groupeGn->getGn()->getId());
                    $qb->orderBy('u.Username', 'ASC');

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Participant',
                ],
            ])
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Ajouter le joueur choisi']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($data['participant']) {
                $data['participant']->setGroupeGn($groupeGn);
                $entityManager->persist($data['participant']);
                $entityManager->flush();

                $app['notify']->newMembre($data['participant']->getUser(), $groupeGn);

               $this->addFlash('success', 'Le joueur a été ajouté à votre groupe.');
            }

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        return $this->render('groupeGn/add.twig', [
            'groupeGn' => $groupeGn,
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retire un participant d'un groupe.
     */
    public function participantRemoveAction(Request $request,  EntityManagerInterface $entityManager, GroupeGn $groupeGn, Participant $participant)
    {
        $form = $this->createForm()
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Retirer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // si le participant est le chef de groupe
            if ($groupeGn->getResponsable() == $participant) {
                $groupeGn->setResponsableNull();
            }

            $participant->setGroupeGnNull();
            $entityManager->persist($participant);
            $entityManager->flush();

           $this->addFlash('success', 'Le joueur a été retiré de cette session.');

            return $this->redirectToRoute('groupeGn.list', ['groupe' => $groupeGn->getGroupe()->getId()]);
        }

        return $this->render('groupeGn/participantRemove.twig', [
            'groupeGn' => $groupeGn,
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Permet au chef de groupe de modifier le nombre de place disponible.
     */
    public function placeAvailableAction(Request $request,  EntityManagerInterface $entityManager, GroupeGn $groupeGn)
    {
        $participant = $this->getUser()->getParticipant($groupeGn->getGn());

        $form = $this->createForm(GroupeGnPlaceAvailableForm::class, $groupeGn)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeGn = $form->getData();
            $entityManager->persist($groupeGn);
            $entityManager->flush();

           $this->addFlash('success', 'Vos modifications ont été enregistré.');

            return $this->redirectToRoute('groupeGn.groupe', ['groupeGn' => $groupeGn->getId()]);
        }

        return $this->render('groupeGn/placeAvailable.twig', [
            'form' => $form->createView(),
            'groupe' => $groupeGn->getGroupe(),
            'participant' => $participant,
            'groupeGn' => $groupeGn,
        ]);
    }

    /**
     * Détail d'un groupe.
     */
    #[Route('/groupeGn/{groupeGn}', name: 'groupeGn.groupe')]
    public function groupeAction(Request $request,  EntityManagerInterface $entityManager, #[MapEntity] GroupeGn $groupeGn)
    {
        $participant = $this->getUser()->getParticipant($groupeGn->getGn());

        return $this->render('groupe/detail.twig', [
            'groupe' => $groupeGn->getGroupe(),
            'participant' => $participant,
            'groupeGn' => $groupeGn,
        ]);
    }

    /**
     * Modification du jeu de domaine du groupe.
     */
    public function jeudedomaineAction(Request $request,  EntityManagerInterface $entityManager, Groupe $groupe, GroupeGn $groupeGn)
    {
        $form = $this->createForm(GroupeGnOrdreForm::class, $groupeGn, ['groupeGnId' => $groupeGn->getId()])
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Enregistrer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeGn = $form->getData();
            $entityManager->persist($groupeGn);
            $entityManager->flush();

           $this->addFlash('success', 'Le jeu de domaine a été enregistré.');

            return $this->redirectToRoute('groupe.detail', ['index' => $groupe->getId()]);
        }

        return $this->render('groupeGn/jeudedomaine.twig', [
            'groupe' => $groupe,
            'groupeGn' => $groupeGn,
            'form' => $form->createView(),
        ]);
    }
}
