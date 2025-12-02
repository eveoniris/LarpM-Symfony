<?php

namespace App\Controller;

use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\Loi;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Enum\Role;
use App\Form\Gn\GnDeleteForm;
use App\Form\Gn\GnForm;
use App\Manager\GroupeManager;
use App\Repository\ClasseRepository;
use App\Repository\GnRepository;
use App\Repository\LangueRepository;
use App\Repository\ParticipantRepository;
use App\Repository\PersonnageRepository;
use App\Repository\PersonnageSecondaireRepository;
use App\Repository\QuestionRepository;
use App\Repository\RessourceRepository;
use App\Security\MultiRolesExpression;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[Route('/gn', name: 'gn.')]
class GnController extends AbstractController
{
    /**
     * affiche le formulaire d'ajout d'un gn
     * Lorsqu'un GN est créé, son forum associé doit lui aussi être créé.
     */
    #[Route('/add', name: 'add')]
    #[IsGranted('ROLE_ORGA')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $form = $this->createForm(GnForm::class, new Gn())
            ->add('save', SubmitType::class, [
                'label' => 'Sauvegarder',
            ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $gn = $form->getData();
            $entityManager->persist($gn);
            $entityManager->flush();
            $this->addFlash('success', 'Le gn a été ajouté.');

            return $this->redirectToRoute('gn.list', [], 303);
        }

        return $this->render('gn/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    // TODO

    /**
     * Impression des backgrounds des chefs de groupe.
     */
    #[Route('/{gn}/backgrounds/chefs', name: 'groupes.backgrounds.chefs')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function backgroundsChefAction(#[MapEntity] Gn $gn): Response
    {
        $groupes = $gn->getGroupes();
        $iterator = $groupes->getIterator();
        $iterator->uasort(static fn (Groupe $a, Groupe $b): int => $a->getNumero() <=> $b->getNumero());
        $groupes = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('gn/backgroundsChef.twig', [
            'gn' => $gn,
            'groupes' => $groupes,
        ]);
    }

    /**
     * Impression des backgrounds des groupes.
     */
    #[Route('/{gn}/backgrounds/groupes', name: 'groupes.backgrounds.groupes')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function backgroundsGroupeAction(#[MapEntity] Gn $gn): Response
    {
        $groupes = $gn->getGroupes();
        $iterator = $groupes->getIterator();
        $iterator->uasort(static fn (Groupe $a, Groupe $b): int => $a->getNumero() <=> $b->getNumero());
        $groupes = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('gn/backgroundsGroupe.twig', [
            'gn' => $gn,
            'groupes' => $groupes,
        ]);
    }

    /**
     * Impression des backgrounds des chefs de groupe.
     */
    #[Route('/{gn}/backgrounds/membres', name: 'groupes.backgrounds.membres')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function backgroundsMembresAction(#[MapEntity] Gn $gn): Response
    {
        $groupes = $gn->getGroupes();
        $iterator = $groupes->getIterator();
        $iterator->uasort(static fn (Groupe $a, Groupe $b): int => $a->getNumero() <=> $b->getNumero());
        $groupes = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('gn/backgroundsMembres.twig', [
            'gn' => $gn,
            'groupes' => $groupes,
        ]);
    }

    /**
     * Gestion des billets d'un GN.
     */
    #[Route('/{gn}/personnages', name: 'billet')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function billetAction(Request $request, EntityManagerInterface $entityManager, Gn $gn): Response
    {
        $participant = null;

        return $this->render('gn/billet.twig', [
            'gn' => $gn,
            'participant' => $participant,
        ]);
    }

    /**
     * Affiche la billetterie d'un GN.
     */
    #[Route('/{gn}/billetterie', name: 'billetterie')]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access tho this page.')]
    public function billetterieAction(
        #[MapEntity] Gn $gn,
    ): Response {
        $groupeGns = $gn->getGroupeGnsPj();
        $iterator = $groupeGns->getIterator();
        $iterator->uasort(static fn (GroupeGn $a, GroupeGn $b): int => $a->getGroupe()?->getNumero() <=> $b->getGroupe()?->getNumero());

        $groupeGns = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('gn/billetterie.twig', [
            'gn' => $gn,
            'groupeGns' => $groupeGns,
        ]);
    }

    #[Route('/{gn}/delete', name: 'delete')]
    #[IsGranted('ROLE_ORGA')]
    public function deleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Gn $gn,
    ): RedirectResponse|Response {
        $form = $this->createForm(GnDeleteForm::class, $gn)
            ->add('delete', SubmitType::class, [
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

    #[Route('/{gn}', name: 'detail')]
    #[IsGranted('ROLE_USER')]
    public function detailAction(
        Request $request,
        #[MapEntity] Gn $gn,
        QuestionRepository $questionRepository,
    ): Response {
        $participant = $this->getUser()?->getParticipant($gn);

        if (null !== $participant && $participant->getBesoinValidationCi()) {
            // L'utilisateur n'a pas validé les CG.
            /*return $this->redirectToRoute('User.gn.validationci', [
                'gn' => $gn->getId(),
            ], 303);*/
            return $this->redirectToRoute('user.gn.validationci', ['gn' => $gn->getId()], 303);
        }

        $questions = $questionRepository->findByParticipant($participant);

        return $this->render('gn/detail.twig', [
            'gn' => $gn,
            'participant' => $participant,
            'questions' => $questions,
        ]);
    }

    /**
     * Génére le fichier à envoyer à la FédéGN.
     */
    #[Route('/{gn}/fedegn', name: 'fedegn')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function fedegnAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Gn $gn): void
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
            $line[] = mb_convert_encoding(
                (string) $participant->getUser()
                    ->getEtatCivil()
                    ->getNom(),
                'ISO-8859-1',
            );
            $line[] = mb_convert_encoding(
                (string) $participant->getUser()
                    ->getEtatCivil()
                    ->getPrenom(),
                'ISO-8859-1',
            );
            $line[] = mb_convert_encoding((string) $participant->getUser()->getEmail(), 'ISO-8859-1');
            if ($participant->getUser()
                ->getEtatCivil()
                ->getDateNaissance()) {
                $line[] = mb_convert_encoding(
                    (string) $participant->getUser()
                        ->getEtatCivil()
                        ->getDateNaissance()
                        ->format('Y-m-d'),
                    'ISO-8859-1',
                );
            } else {
                $line[] = '?';
            }

            if ($participant->getUser()
                ->getEtatCivil()
                ->getFedeGn()) {
                $line[] = mb_convert_encoding(
                    (string) $participant->getUser()
                        ->getEtatCivil()
                        ->getFedeGn(),
                    'ISO-8859-1',
                );
            } else {
                $line[] = '?';
            }

            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Liste des groupes prévus sur le jeu.
     */
    #[Route('/{gn}/groupes', name: 'groupes')]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access tho this page.')]
    public function groupesAction(#[MapEntity] Gn $gn): Response
    {
        $groupes = $gn->getGroupes();
        $iterator = $groupes->getIterator();
        $iterator->uasort(static fn (Groupe $a, Groupe $b): int => $a->getNumero() <=> $b->getNumero());

        $groupes = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('gn/groupes.twig', [
            'groupes' => $groupes,
            'gn' => $gn,
        ]);
    }

    /**
     * Liste des groupes recherchant des joueurs.
     */
    #[Route('/{gn}/groupes/avecPlace', name: 'groupesPlaces')]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access tho this page.')]
    public function groupesPlacesAction(#[MapEntity] Gn $gn): Response
    {
        $groupesPlaces = new ArrayCollection();
        /** @var Groupe[] $groupes */
        $groupes = $gn->getGroupes();
        foreach ($groupes as $groupe) {
            $session = $groupe->getSession($gn);

            if ($session?->getPlaceAvailable() > 0 && $groupe->getPj()) {
                $groupesPlaces[] = $groupe;
            }
        }

        $iterator = $groupesPlaces->getIterator();
        $iterator->uasort(static fn (Groupe $a, Groupe $b): int => $a->getNumero() <=> $b->getNumero());
        $groupesPlaces = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('gn/groupes.twig', [
            'groupes' => $groupesPlaces,
            'gn' => $gn,
        ]);
    }

    /**
     * Liste des groupes réservés.
     */
    #[Route('/{gn}/groupes/reserves', name: 'groupesReserves')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function groupesReservesAction(#[MapEntity] Gn $gn): Response
    {
        $groupes = $gn->getGroupesReserves();
        $iterator = $groupes->getIterator();
        $iterator->uasort(static fn (Groupe $a, Groupe $b): int => $a->getNumero() <=> $b->getNumero());
        $groupes = new ArrayCollection(iterator_to_array($iterator));

        return $this->render('gn/groupesReserves.twig', [
            'groupes' => $groupes,
            'gn' => $gn,
        ]);
    }

    #[Route('', name: 'list')]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access tho this page.')]
    public function listAction(
        Request $request,
        GnRepository $gnRepository,
    ): Response {
        $page = $request->query->getInt('page', 1);
        $limit = 10;

        $paginator = $gnRepository->findPaginated($page, $limit);

        return $this->render(
            'gn/list.twig',
            [
                'paginator' => $paginator,
                'limit' => $limit,
                'page' => $page,
            ],
        );
    }

    #[Route('/user', name: 'user.list')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA), message: 'You are not allowed to access to this.')]
    public function listUserAction(Request $request, GnRepository $gnRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10;

        $paginator = $gnRepository->findPaginated($page, $limit);

        return $this->render(
            'gn/list.twig',
            [
                'paginator' => $paginator,
                'limit' => $limit,
                'page' => $page,
            ],
        );
    }

    #[Route('/{gn}/lock', name: 'gn.lock')]
    #[IsGranted('ROLE_ORGA')]
    public function lockAction(
        Request $request,
        GnRepository $gnRepository,
        ClasseRepository $classeRepository,
        PersonnageRepository $personnageRepository,
        PersonnageSecondaireRepository $personnageSecondaireRepository,
        ParticipantRepository $participantRepository,
        LangueRepository $langueRepository,
        #[MapEntity] Gn $gn,
    ): RedirectResponse|Response {
        $form = $this->createFormBuilder()
            ->add(
                'save',
                SubmitType::class,
                ['label' => "Verrouiller l'édition des groupes du GN", 'attr' => ['class' => 'btn btn-secondary']],
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Lock de base
            $gnRepository->lockAllGroup($gn);

            $this->personnageService->lockGnSetDefaultCharacter($gn);
            $this->personnageService->lockGnSetDefaultLangue($gn);
            $this->personnageService->lockGnSetDefaultSecondCharacter($gn);
            $this->personnageService->lockGnGiveNoblityGnRenomme($gn);
            $this->personnageService->lockGnGiveLiteratureGnBonus($gn);
            $this->personnageService->lockGnGiveSanctuaireGnEffect();

            $this->addFlash('success', 'Le gn a été verrouillé.');

            return $this->redirectToRoute('gn.list', [], 303);
        }

        return $this->render('gn/form.twig', [
            'form' => $form->createView(),
            'gn' => $gn,
        ]);
    }

    /**
     * Liste des participants à un jeu.
     */
    #[IsGranted('ROLE_ORGA')]
    #[Route('/{gn}/participants', name: 'participants')]
    public function participantsAction(
        Request $request,
        PagerService $pagerService,
        ParticipantRepository $participantRepository,
        #[MapEntity] Gn $gn,
    ): Response {
        $pagerService->setRequest($request)->setRepository($participantRepository);

        $alias = $participantRepository->getAlias();
        $queryBuilder = $participantRepository->createQueryBuilder($alias);
        $queryBuilder = $participantRepository->gn($queryBuilder, $gn);

        return $this->render('gn/participants.twig', [
            'pagerService' => $pagerService,
            'paginator' => $participantRepository->searchPaginated($pagerService, $queryBuilder),
            'gn' => $gn,
        ]);
    }

    /**
     * Liste des participants à un jeu (CSV).
     */
    #[Route('/{gn}/participantsCSV', name: 'participants.csv')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function participantsCSVAction(#[MapEntity] Gn $gn, ParticipantRepository $participantRepository): ?StreamedResponse
    {
        return $this->sendCsv(
            'eveoniris_participants_'.$gn->getLabel().'_'.date('Ymd'),
            query: $participantRepository->getParticipantsGn($gn),
            header: [
                'Participant',
                'Email',
                'Billet',
                'Restauration',
                'Groupe',
            ]
        );
    }

    /**
     * Liste des participants à un jeu n'ayant pas encore de billets.
     */
    #[Route('/{gn}/participants/withoutbillet', name: 'participants.withoutbillet')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function participantsWithoutBilletAction(#[MapEntity] Gn $gn): Response
    {
        $participants = $gn->getParticipantsWithoutBillet();

        return $this->render('gn/participantswithoutbillet.twig', [
            'gn' => $gn,
            'participants' => $participants,
        ]);
    }

    /**
     * Liste des participants à un jeu n'ayant pas encore de billets au format CSV.
     */
    #[Route('/{gn}/participants/withoutbillet/csv', name: 'participants.withoutbillet.csv')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function participantsWithoutBilletCSVAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Gn $gn,
    ): void {
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
            $line[] = $participant->getUser() && $participant->getUser()->getEtatCivil() ? mb_convert_encoding(
                (string) $participant->getUser()->getEtatCivil()->getNom(),
                'ISO-8859-1',
            ) : '';
            $line[] = $participant->getUser() && $participant->getUser()->getEtatCivil() ? mb_convert_encoding(
                (string) $participant->getUser()->getEtatCivil()->getPrenom(),
                'ISO-8859-1',
            ) : '';
            $line[] = $participant->getUser() ? mb_convert_encoding(
                (string) $participant->getUser()->getEmail(),
                'ISO-8859-1',
            ) : '';

            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Liste des participants à un jeu ayant un billet mais pas encore de groupe.
     */
    #[Route('/{gn}/participants/withoutgroup', name: 'participants.withoutgroup')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function participantsWithoutGroupAction(#[MapEntity] Gn $gn): Response
    {
        $participants = $gn->getParticipantsWithoutGroup();

        return $this->render('gn/participantswithoutgroup.twig', [
            'gn' => $gn,
            'participants' => $participants,
        ]);
    }

    /**
     * Liste des participants à un jeu ayant un billet mais pas encore de groupe au format CSV.
     */
    #[Route('/{gn}/participants/withoutgroup/csv', name: 'participants.withoutgroup.csv')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function participantsWithoutGroupCSVAction(#[MapEntity] Gn $gn): void
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
            $line[] = $participant->getUser() && $participant->getUser()->getEtatCivil() ? mb_convert_encoding(
                (string) $participant->getUser()->getEtatCivil()->getNom(),
                'ISO-8859-1',
            ) : '';
            $line[] = $participant->getUser() && $participant->getUser()->getEtatCivil() ? mb_convert_encoding(
                (string) $participant->getUser()->getEtatCivil()->getPrenom(),
                'ISO-8859-1',
            ) : '';
            $line[] = $participant->getUser() ? mb_convert_encoding(
                (string) $participant->getUser()->getEmail(),
                'ISO-8859-1',
            ) : '';

            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Liste des participants à un jeu ayant un billet mais pas encore de personnage.
     */
    #[Route('/{gn}/participants/withoutperso', name: 'participants.withoutperso')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function participantsWithoutPersoAction(#[MapEntity] Gn $gn): Response
    {
        $participants = $gn->getParticipantsWithoutPerso();

        return $this->render('gn/participantswithoutperso.twig', [
            'gn' => $gn,
            'participants' => $participants,
        ]);
    }

    /**
     * Liste des participants à un jeu n'ayant pas encore de personnage au format CSV.
     */
    #[Route('/{gn}/participants/withoutperso/csv', name: 'participants.withoutperso.csv')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function participantsWithoutPersoCSVAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Gn $gn,
    ): void {
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

            $line[] = $participant->getUser() && $participant->getUser()->getEtatCivil() ? mb_convert_encoding(
                (string) $participant->getUser()->getEtatCivil()->getNom(),
                'ISO-8859-1',
            ) : '';
            $line[] = $participant->getUser() && $participant->getUser()->getEtatCivil() ? mb_convert_encoding(
                (string) $participant->getUser()->getEtatCivil()->getPrenom(),
                'ISO-8859-1',
            ) : '';
            $line[] = $participant->getUser() ? mb_convert_encoding(
                (string) $participant->getUser()->getEmail(),
                'ISO-8859-1',
            ) : '';

            fputcsv($output, $line, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Fiche de personnage d'un participant au GN.
     */
    #[Route('/{gn}/personnage', name: 'personnage')]
    #[IsGranted(new MultiRolesExpression(Role::USER), message: 'You are not allowed to access to this.')]
    public function personnageAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Gn $gn,
        Environment $twig,
    ): RedirectResponse|Response {
        $participant = $this->getUser()?->getParticipant($gn);
        $personnage = $participant?->getPersonnage();

        if (!$personnage) {
            $this->addFlash('error', "Vous n'avez pas encore de personnage.");

            return $this->redirectToRoute('gn.detail', [
                'gn' => $gn->getId(),
            ], 303);
        }

        $lois = $entityManager->getRepository(Loi::class)->findAll();
        $descendants = $entityManager->getRepository(Personnage::class)->findDescendants($personnage);
        $titre = $entityManager->getRepository(Personnage::class)->findTitre($personnage->getRenomme());

        $tab = $request->get('tab', 'general');
        if (!$twig->getLoader()->exists('personnage/fragment/tab_'.$tab.'.twig')) {
            $tab = 'general';
        }

        return $this->render('personnage/detail.twig', [
            'personnage' => $personnage,
            'participant' => $participant,
            'lois' => $lois,
            'descendants' => $descendants,
            'titre' => $titre,
            'tab' => $tab,
        ]);
    }

    /**
     * Les personnages present pendant le jeu.
     */
    #[Route('/{gn}/personnages', name: 'personnages')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA), message: 'You are not allowed to access to this.')]
    public function personnagesAction(
        Request $request,
        #[MapEntity] Gn $gn,
        PersonnageService $personnageService,
        GnRepository $gnRepository,
    ): Response {
        return $this->render(
            'personnage/list.twig',
            $personnageService->getSearchViewParameters(
                $request,
                'gn.personnages',
                ['gn' => $gn->getId()],
                [
                    'colId',
                    'colStatut',
                    'colNom',
                    'colClasse',
                    'colGroupe',
                    'colUser',
                    'colRenommee',
                    'colHeroisme',
                    'colXp',
                    'colHasAnomalie',
                ],
                [
                    'displayAdminToolbar' => false,
                    'breadcrumb' => [
                        [
                            'name' => 'Liste des grandeurs natures',
                            'route' => $this->generateUrl('gn.list'),
                        ],
                        [
                            'name' => $gn->getLabel(),
                            'route' => $this->generateUrl('gn.detail', ['gn' => $gn->getId()]),
                        ],
                        [
                            'name' => 'Personnages participants à ce GN',
                        ],
                    ],
                ],
                null,
                $gnRepository->getPersonnages($gn),
            ),
        );
    }

    /**
     * Liste des pnjs prévu sur le jeu.
     */
    #[Route('/{gn}/pnjs', name: 'pnjs')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function pnjsAction(#[MapEntity] Gn $gn): Response
    {
        $pnjs = $gn->getParticipantsPnj();

        return $this->render('gn/pnjs.twig', [
            'pnjs' => $pnjs,
            'gn' => $gn,
        ]);
    }

    #[Route('/{gn}/groupes/enveloppes', name: 'groupes.enveloppes')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA), message: 'You are not allowed to access to this.')]
    public function printAllAction(#[MapEntity] Gn $gn, RessourceRepository $ressourceRepository): Response
    {
        $groupeGns = $gn->getGroupeGns();

        $ressourceRares = new ArrayCollection($ressourceRepository->findRare());
        $ressourceCommunes = new ArrayCollection($ressourceRepository->findCommun());

        $groupes = new ArrayCollection();
        foreach ($groupeGns as $groupeGn) {
            $groupe = $groupeGn->getGroupe();
            $quete = GroupeManager::generateQuete($groupe, $ressourceCommunes, $ressourceRares);
            $groupes[] = [
                'groupe' => $groupe,
                'quete' => $quete,
            ];
        }

        return $this->render('groupe/printAll.twig', [
            'groupes' => $groupes,
        ]);
    }

    /**
     * Impression fiche de perso pour le gn.
     */
    #[Route('/{gn}/printInter', name: 'print.inter')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function printInterAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Gn $gn,
    ): Response {
        $participants = $gn->getParticipantsInterGN();
        $quetes = new ArrayCollection();

        return $this->render('gn/printInter.twig', [
            'participants' => $participants,
            'quetes' => $quetes,
        ]);
    }

    /**
     * Impression fiche de perso pour le gn.
     */
    #[Route('/{gn}/printPerso', name: 'print.perso')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function printPersoAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Gn $gn,
    ): Response {
        $participants = $gn->getParticipantsWithBillet();
        $quetes = new ArrayCollection();

        return $this->render('gn/printPerso.twig', [
            'gn' => $gn,
            'participants' => $participants,
            'quetes' => $quetes,
        ]);
    }

    /**
     * Liste des personnages renommé prévu sur le jeu.
     */
    #[Route('/{gn}/renom', name: 'renom')]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access tho this page.')]
    public function renomAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Gn $gn): Response
    { // trouver tous les personnages participants au prochain GN et ayant une renommé supérieur à 10
        $personnages = $gn->getPersonnagesRenom(10);

        return $this->render('gn/renom.twig', [
            'personnages' => $personnages,
            'gn' => $gn,
        ]);
    }

    #[Route('/{gn}/update', name: 'update')]
    #[IsGranted('ROLE_ORGA', message: 'You are not allowed to access tho this page.')]
    public function updateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Gn $gn,
    ): RedirectResponse|Response {
        $form = $this->createForm(GnForm::class, $gn)
            ->add('update', SubmitType::class, [
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
}
