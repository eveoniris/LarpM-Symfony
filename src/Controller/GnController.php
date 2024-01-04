<?php

namespace App\Controller;

use App\Entity\Gn;
use App\Entity\Loi;
use App\Entity\Personnage;
use App\Repository\GnRepository;
use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Form\Gn\GnDeleteForm;
use App\Form\Gn\GnForm;
use App\Form\ParticipantFindForm;
use App\Form\PersonnageFindForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GnController extends AbstractController
{
    #[Route('/gn', name: 'gn.list')]
    public function listAction(Request $request, GnRepository $gnRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10;

        $paginator = $gnRepository->findPaginated($page, $limit);
        dump($paginator);

        return $this->render(
            'gn/list.twig',
            [
                'paginator' => $paginator,
                'limit' => $limit,
                'page' => $page,
            ]
        );
    }

    #[Route('/gn/detail', name: 'gn.detail')]
    public function detailAction(Gn $gn, QuestionRepository $questionRepository): Response
    {
        $participant = $this->getUser()->getParticipant($gn);

        if (null != $participant && $participant->getBesoinValidationCi()) {
            // L'utilisateur n'a pas validé les CG.
            /*return $this->redirectToRoute('User.gn.validationci', [
                'gn' => $gn->getId(),
            ], [], 303);*/
            return $this->redirectToRoute('User.gn.validationci', ['gn' => $gn->getId()], 303);
        }

        $questions = $questionRepository->findByParticipant($participant);

        return $this->render('gn/detail.twig', [
            'gn' => $gn,
            'participant' => $participant,
            'questions' => $questions,
        ]);
    }

    /**
     * Fiche de personnage d'un participant au GN.
     */
    #[Route('/gn/personnage', name: 'gn.personnage')]
    public function personnageAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $participant = $this->getUser()->getParticipant($gn);
        $personnage = $participant->getPersonnage();
        if (!$personnage) {
           $this->addFlash('error', "Vous n'avez pas encore de personnage.");

            return $this->redirectToRoute('gn.detail', [
                'gn' => $gn->getId(),
            ], [], 303);
        }

        $lois = $entityManager->getRepository(Loi::class)->findAll();
        $descendants = $entityManager->getRepository(Personnage::class)->findDescendants($personnage);

        return $this->render('public/personnage/detail.twig', [
            'personnage' => $personnage,
            'participant' => $participant,
            'lois' => $lois,
            'descendants' => $descendants,
        ]);
    }

    /**
     * Les personnages present pendant le jeu.
     */
    public function personnagesAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];
        $criteria[] = 'gn.id = '.$gn->getId();
        $form = $this->createForm(new PersonnageFindForm())->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            switch ($type) {
                case 'nom':
                    // $criteria[] = new LikeExpression("p.nom", "%$value%");
                    $criteria['nom'] = "LOWER(p.nom) LIKE '%".preg_replace('/[\'"<>=*;]/', '', strtolower((string) $value))."%'";
                    break;
                case 'id':
                    // $criteria[] = new EqualExpression("p.id", $value);
                    $criteria['id'] = 'p.id = '.preg_replace('/[^\d]/', '', (string) $value);
                    break;
            }
        }

        $repo = $entityManager->getRepository('\\'.\App\Entity\Personnage::class);
        $personnages = $repo->findList($criteria, [
            'by' => $order_by,
            'dir' => $order_dir,
        ], $limit, $offset);
        $numResults = $repo->findCount($criteria);
        $paginator = new Paginator($numResults, $limit, $page, $app['url_generator']->generate('gn.personnages', [
                'gn' => $gn->getId(),
            ]).'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir);

        return $this->render('gn/personnages.twig', [
            'gn' => $gn,
            'personnages' => $personnages,
            'paginator' => $paginator,
            'numResults' => $numResults,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Impression des backgrounds des chefs de groupe.
     */
    public function backgroundsChefAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $groupes = $gn->getGroupes();
        $iterator = $groupes->getIterator();
        $iterator->uasort(static function ($a, $b): int {
            return ($a->getNumero() < $b->getNumero()) ? -1 : 1;
        });
        $groupes = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('gn/backgroundsChef.twig', [
            'gn' => $gn,
            'groupes' => $groupes,
        ]);
    }

    /**
     * Impression des backgrounds des groupes.
     */
    public function backgroundsGroupeAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $groupes = $gn->getGroupes();
        $iterator = $groupes->getIterator();
        $iterator->uasort(static function ($a, $b): int {
            return ($a->getNumero() < $b->getNumero()) ? -1 : 1;
        });
        $groupes = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('gn/backgroundsGroupe.twig', [
            'gn' => $gn,
            'groupes' => $groupes,
        ]);
    }

    /**
     * Impression des backgrounds des chefs de groupe.
     */
    public function backgroundsMembresAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $groupes = $gn->getGroupes();
        $iterator = $groupes->getIterator();
        $iterator->uasort(static function ($a, $b): int {
            return ($a->getNumero() < $b->getNumero()) ? -1 : 1;
        });
        $groupes = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('gn/backgroundsMembres.twig', [
            'gn' => $gn,
            'groupes' => $groupes,
        ]);
    }

    /**
     * Gestion des billets d'un GN.
     */
    public function billetAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $participant = null;

        return $this->render('gn/billet.twig', [
            'gn' => $gn,
            'participant' => $participant,
        ]);
    }

    /**
     * affiche le formulaire d'ajout d'un gn
     * Lorsqu'un GN est créé, son forum associé doit lui aussi être créé.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(GnForm::class(), new Gn())
            ->add('save', 'submit', [
                'label' => 'Sauvegarder',
            ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $gn = $form->getData();
            /**
             * Création du topic associé à ce gn.
             *
             * @var \App\Entity\Topic $topic
             */
            $topic = new \App\Entity\Topic();
            $topic->setTitle($gn->getLabel());
            $topic->setDescription($gn->getDescription());
            $topic->setUser($this->getUser()); // défini les droits d'accés à ce forum // (les participants au GN ont le droits d'accéder à ce forum)
            $topic->setRight('GN_PARTICIPANT');
            $gn->setTopic($topic);
            $entityManager->persist($gn);
            $entityManager->flush();
            $entityManager->persist($topic);
            $topic->setObjectId($gn->getId());
            $entityManager->persist($gn);
            $entityManager->flush();
           $this->addFlash('success', 'Le gn a été ajouté.');

            return $this->redirectToRoute('gn.list', [], 303);
        }

        return $this->render('gn/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Liste des participants à un jeu n'ayant pas encore de billets.
     */
    public function participantsWithoutBilletAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $participants = $gn->getParticipantsWithoutBillet();

        return $this->render('gn/participantswithoutbillet.twig', [
            'gn' => $gn,
            'participants' => $participants,
        ]);
    }

    /**
     * Liste des participants à un jeu ayant un billet mais pas encore de groupe.
     */
    public function participantsWithoutGroupAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $participants = $gn->getParticipantsWithoutGroup();

        return $this->render('gn/participantswithoutgroup.twig', [
            'gn' => $gn,
            'participants' => $participants,
        ]);
    }

    /**
     * Liste des participants à un jeu ayant un billet mais pas encore de personnage.
     */
    public function participantsWithoutPersoAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $participants = $gn->getParticipantsWithoutPerso();

        return $this->render('gn/participantswithoutperso.twig', [
            'gn' => $gn,
            'participants' => $participants,
        ]);
    }

    /**
     * Liste des participants à un jeu ayant un billet mais pas encore de groupe au format CSV.
     */
    public function participantsWithoutGroupCSVAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn): void
    {
        $participants = $gn->getParticipantsWithoutGroup();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_participants_sans_groupe_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        $output = fopen('php://output', 'w');
        // header
        fputcsv($output, [
            'Nom',
            'Prénom',
            'Email',
        ], ';');
        foreach ($participants as $participant) {
            $line = [];
            $line[] = mb_convert_encoding((string) $participant->getUser()
                ->getEtatCivil()
                ->getNom(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $participant->getUser()
                ->getEtatCivil()
                ->getPrenom(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $participant->getUser()->getEmail(), 'ISO-8859-1');
            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Liste des participants à un jeu n'ayant pas encore de billets au format CSV.
     */
    public function participantsWithoutBilletCSVAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn): void
    {
        $participants = $gn->getParticipantsWithoutBillet();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_participants_sans_billet_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        $output = fopen('php://output', 'w');
        // header
        fputcsv($output, [
            'Nom',
            'Prénom',
            'Email',
        ], ';');
        foreach ($participants as $participant) {
            $line = [];
            $line[] = mb_convert_encoding((string) $participant->getUser()
                ->getEtatCivil()
                ->getNom(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $participant->getUser()
                ->getEtatCivil()
                ->getPrenom(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $participant->getUser()->getEmail(), 'ISO-8859-1');
            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Liste des participants à un jeu n'ayant pas encore de personnage au format CSV.
     */
    public function participantsWithoutPersoCSVAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn): void
    {
        $participants = $gn->getParticipantsWithoutPerso();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_participants_sans_perso_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv($output, [
            'Nom',
            'Prénom',
            'Email',
        ], ';');

        foreach ($participants as $participant) {
            $line = [];
            $line[] = mb_convert_encoding((string) $participant->getUser()
                ->getEtatCivil()
                ->getNom(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $participant->getUser()
                ->getEtatCivil()
                ->getPrenom(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $participant->getUser()->getEmail(), 'ISO-8859-1');
            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Liste des participants à un jeu.
     */
    public function participantsAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $order_by = $request->get('order_by') ?: 'ec.nom';
        $order_dir = 'DESC' == $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;

        // nombre de participant au total
        $qbTotal = $entityManager->createQueryBuilder();
        $qbTotal->select('COUNT(p.id)')
            ->from('\\'.\App\Entity\Participant::class, 'p')
            ->join('p.gn', 'gn')
            ->join('p.User', 'u')
            ->join('u.etatCivil', 'ec')
            ->where('gn.id = :gnId')
            ->setParameter('gnId', $gn->getId());

        // liste des participants à afficher
        $qb = $entityManager->createQueryBuilder();
        $qb->select('p')
            ->from('\\'.\App\Entity\Participant::class, 'p')
            ->join('p.gn', 'gn')
            ->join('p.User', 'u')
            ->join('u.etatCivil', 'ec')
            ->where('gn.id = :gnId')
            ->setParameter('gnId', $gn->getId())
            ->orderBy($order_by, $order_dir)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $form = $this->createForm(new ParticipantFindForm())->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            switch ($data['type']) {
                case 'nom':
                    $qb->andWhere('ec.nom LIKE :value');
                    $qbTotal->andWhere('ec.nom LIKE :value');
                    break;
                case 'email':
                    $qb->andWhere('u.email LIKE :value');
                    $qbTotal->andWhere('u.email LIKE :value');
                    break;
            }

            $qb->setParameter('value', '%'.$data['value'].'%');
            $qbTotal->setParameter('value', '%'.$data['value'].'%');
        }

        $participants = $qb->getQuery()->getResult();
        $count = $qbTotal->getQuery()->getSingleScalarResult();

        $paginator = new Paginator($count, $limit, $page, $app['url_generator']->generate('gn.participants', [
                'gn' => $gn->getId(),
            ]).'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir);

        return $this->render('gn/participants.twig', [
            'gn' => $gn,
            'participants' => $participants,
            'paginator' => $paginator,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Génére le fichier à envoyer à la FédéGN.
     */
    public function fedegnAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn): void
    {
        $participants = $gn->getParticipantsFedeGn();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_fedegn_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        $output = fopen('php://output', 'w');
        // header
        fputcsv($output, [
            'Nom',
            'Prénom',
            'Email',
            'Date de naissance',
            'fedegn',
        ], ';');
        foreach ($participants as $participant) {
            $line = [];
            $line[] = mb_convert_encoding((string) $participant->getUser()
                ->getEtatCivil()
                ->getNom(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $participant->getUser()
                ->getEtatCivil()
                ->getPrenom(), 'ISO-8859-1');
            $line[] = mb_convert_encoding((string) $participant->getUser()->getEmail(), 'ISO-8859-1');
            if ($participant->getUser()
                ->getEtatCivil()
                ->getDateNaissance()) {
                $line[] = mb_convert_encoding((string) $participant->getUser()
                    ->getEtatCivil()
                    ->getDateNaissance()
                    ->format('Y-m-d'), 'ISO-8859-1');
            } else {
                $line[] = '?';
            }

            if ($participant->getUser()
                ->getEtatCivil()
                ->getFedeGn()) {
                $line[] = mb_convert_encoding((string) $participant->getUser()
                    ->getEtatCivil()
                    ->getFedeGn(), 'ISO-8859-1');
            } else {
                $line[] = '?';
            }

            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    #[Route('/gn/delete', name: 'gn.delete')]
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $form = $this->createForm(GnDeleteForm::class(), $gn)
            ->add('delete', 'submit', [
                'label' => 'Supprimer',
            ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $gn = $form->getData();
            $entityManager->remove($gn);
            $entityManager->flush();
           $this->addFlash('success', 'Le gn a été supprimé.');

            return $this->redirectToRoute('gn.list');
        }

        return $this->render('gn/delete.twig', [
            'gn' => $gn,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/gn/update', name: 'gn.update')]
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $form = $this->createForm(GnForm::class(), $gn)
            ->add('update', 'submit', [
                'label' => 'Sauvegarder',
            ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $gn = $form->getData();
            $entityManager->persist($gn);
            $entityManager->flush();
           $this->addFlash('success', 'Le gn a été mis à jour.');

            return $this->redirectToRoute('gn.list');
        }

        return $this->render('gn/update.twig', [
            'gn' => $gn,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche la billetterie d'un GN.
     */
    public function billetterieAction( EntityManagerInterface $entityManager, Request $request, Gn $gn)
    {
        $groupeGns = $gn->getGroupeGnsPj();
        $iterator = $groupeGns->getIterator();
        $iterator->uasort(static function ($a, $b): int {
            return ($a->getGroupe()
                    ->getNumero() < $b->getGroupe()
                    ->getNumero()) ? -1 : 1;
        });
        $groupeGns = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('gn/billetterie.twig', [
            'gn' => $gn,
            'groupeGns' => $groupeGns,
        ]);
    }

    /**
     * Liste des personnages renommé prévu sur le jeu.
     */
    public function renomAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    { // trouver tous les personnages participants au prochain GN et ayant une renommé supérieur à 10
        $personnages = $gn->getPersonnagesRenom(10);

        return $this->render('gn/renom.twig', [
            'personnages' => $personnages,
            'gn' => $gn,
        ]);
    }

    /**
     * Liste des pnjs prévu sur le jeu.
     */
    public function pnjsAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $pnjs = $gn->getParticipantsPnj();

        return $this->render('gn/pnjs.twig', [
            'pnjs' => $pnjs,
            'gn' => $gn,
        ]);
    }

    /**
     * Liste des groupes prévu sur le jeu.
     */
    public function groupesAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $groupes = $gn->getGroupes();
        $iterator = $groupes->getIterator();
        $iterator->uasort(static function ($a, $b): int {
            return ($a->getNumero() < $b->getNumero()) ? -1 : 1;
        });
        $groupes = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('gn/groupes.twig', [
            'groupes' => $groupes,
            'gn' => $gn,
        ]);
    }

    /**
     * Liste des groupes réservés.
     */
    public function groupesReservesAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $groupes = $gn->getGroupesReserves();
        $iterator = $groupes->getIterator();
        $iterator->uasort(static function ($a, $b): int {
            return ($a->getNumero() < $b->getNumero()) ? -1 : 1;
        });
        $groupes = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('admin/gn/groupes.twig', [
            'groupes' => $groupes,
            'gn' => $gn,
        ]);
    }

    /**
     * Liste des groupes recherchant des joueurs.
     */
    public function groupesPlacesAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $groupesPlaces = new ArrayCollection();
        $groupes = $gn->getGroupes();
        foreach ($groupes as $groupe) {
            $session = $groupe->getSession($gn);
            if ($session->getPlaceAvailable() > 0) {
                $groupesPlaces[] = $groupe;
            }
        }

        $iterator = $groupesPlaces->getIterator();
        $iterator->uasort(static function ($a, $b): int {
            return ($a->getNumero() < $b->getNumero()) ? -1 : 1;
        });
        $groupesPlaces = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('gn/groupesPlaces.twig', [
            'groupes' => $groupesPlaces,
            'gn' => $gn,
        ]);
    }

    /**
     * Impression fiche de perso pour le gn.
     */
    public function printPersoAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $participants = $gn->getParticipantsWithBillet();
        $quetes = new ArrayCollection();

        return $this->render('admin/gn/printPerso.twig', [
            'gn' => $gn,
            'participants' => $participants,
            'quetes' => $quetes,
        ]);
    }

    /**
     * Impression fiche de perso pour le gn.
     */
    public function printInterAction(Request $request,  EntityManagerInterface $entityManager, Gn $gn)
    {
        $participants = $gn->getParticipantsInterGN();
        $quetes = new ArrayCollection();

        return $this->render('admin/gn/printInter.twig', [
            'participants' => $participants,
            'quetes' => $quetes,
        ]);
    }
}
