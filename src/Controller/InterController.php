<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\InterJeu;
use App\Entity\InterJeuOrga;
use App\Entity\Personnage;
use App\Entity\PersonnageChronologie;
use App\Entity\User;
use App\Enum\InterJeuParticipantStatut;
use App\Enum\Role;
use App\Form\InterJeuPersonnageAddType;
use App\Form\InterJeuPersonnagesImportType;
use App\Form\InterJeuType;
use App\Repository\GnRepository;
use App\Repository\InterJeuRepository;
use App\Repository\PersonnageRepository;
use App\Repository\UserRepository;
use App\Security\MultiRolesExpression;
use App\Service\PagerService;
use App\Service\StatsService;
use DateTime;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gestion des inter-jeux : réservée au rôle INTER_JEU (seul en écriture) et aux admins.
 * La consultation par les orgas externes passe par InterConsultationController.
 */
#[IsGranted(new MultiRolesExpression(Role::INTER_JEU, Role::ADMIN))]
#[Route('/inter-jeu', name: 'inter.')]
class InterController extends AbstractController
{
    #[Route('', name: 'list')]
    public function listAction(
        Request $request,
        PagerService $pagerService,
        InterJeuRepository $interJeuRepository,
    ): Response {
        $pagerService->setRequest($request)->setRepository($interJeuRepository);

        return $this->render('inter/list.html.twig', [
            'pagerService' => $pagerService,
            'paginator' => $interJeuRepository->searchPaginated($pagerService),
        ]);
    }

    #[Route('/add', name: 'add')]
    public function addAction(Request $request, GnRepository $gnRepository): RedirectResponse|Response
    {
        $interJeu = new InterJeu();

        $lastGn = $gnRepository
            ->createQueryBuilder('g')
            ->where('g.date_debut <= :today')
            ->setParameter('today', new DateTime('today'))
            ->orderBy('g.date_debut', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastGn?->getDateDebut()) {
            $interJeu->setDateReel($lastGn->getDateDebut());
        }

        return $this->handleCreateOrUpdate($request, $interJeu, InterJeuType::class, msg: [
            'entity' => 'inter-jeu',
            'entity_added' => 'L\'inter-jeu a été créé',
            'entity_updated' => 'L\'inter-jeu a été mis à jour',
            'entity_list' => 'Liste des inter-jeux',
            'title_add' => 'Créer un inter-jeu',
            'title_update' => 'Modifier l\'inter-jeu',
        ]);
    }

    #[Route('/{inter}/update', name: 'update', requirements: ['inter' => Requirement::DIGITS])]
    public function updateAction(Request $request, InterJeu $inter): RedirectResponse|Response
    {
        return $this->handleCreateOrUpdate($request, $inter, InterJeuType::class, msg: [
            'entity' => 'inter-jeu',
            'entity_added' => 'L\'inter-jeu a été créé',
            'entity_updated' => 'L\'inter-jeu a été mis à jour',
            'entity_list' => 'Liste des inter-jeux',
            'title_add' => 'Créer un inter-jeu',
            'title_update' => 'Modifier l\'inter-jeu',
        ]);
    }

    #[Route('/{inter}/delete', name: 'delete', requirements: ['inter' => Requirement::DIGITS])]
    public function deleteAction(InterJeu $inter): RedirectResponse|Response
    {
        return $this->genericDelete($inter, 'Supprimer un inter-jeu', 'L\'inter-jeu a été supprimé', 'inter.list', [
            ['route' => $this->generateUrl('inter.list'), 'name' => 'Liste des inter-jeux'],
            [
                'route' => $this->generateUrl('inter.detail', ['inter' => $inter->getId()]),
                'name' => $inter->getNom(),
            ],
            ['name' => 'Supprimer l\'inter-jeu'],
        ]);
    }

    #[Route('/{inter}', name: 'detail', requirements: ['inter' => Requirement::DIGITS])]
    public function detailAction(InterJeu $inter, StatsService $statsService): Response
    {
        $hasParticipants = !$inter->getPersonnages()->isEmpty();

        return $this->render('inter/detail.html.twig', [
            'interJeu' => $inter,
            'statuts' => InterJeuParticipantStatut::cases(),
            'chronologieDisponible' => $inter->canGenereteChronologie(),
            'classes' => $hasParticipants ? $statsService->getClassesInterJeu($inter)->getResult() : [],
            'competences' => $hasParticipants ? $statsService->getCompetenceInterJeu($inter)->getResult() : [],
        ]);
    }

    #[Route('/{inter}/personnages', name: 'personnages', requirements: ['inter' => Requirement::DIGITS])]
    public function personnagesAction(InterJeu $inter): Response
    {
        return $this->render('inter/personnages.html.twig', [
            'interJeu' => $inter,
            'statuts' => InterJeuParticipantStatut::cases(),
        ]);
    }

    #[Route('/{inter}/personnage/add', name: 'personnage.add', requirements: ['inter' => Requirement::DIGITS])]
    public function personnageAddAction(Request $request, InterJeu $inter): RedirectResponse|Response
    {
        $form = $this->createForm(InterJeuPersonnageAddType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Personnage $personnage */
            $personnage = $form->get('personnage')->getData();
            /** @var InterJeuParticipantStatut $statut */
            $statut = $form->get('statut')->getData();
            $inter->addPersonnage($personnage, $statut)->annulerProposition();
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('Le personnage a été ajouté à l\'inter-jeu (%s).', $statut->getLabel()));

            return $this->redirectToRoute('inter.personnages', ['inter' => $inter->getId()], 303);
        }

        return $this->render('inter/personnage_add.html.twig', [
            'interJeu' => $inter,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Import d'une liste d'identifiants (saisie ou CSV). Le CSV accepte une 2e colonne statut facultative
     * (inscrit, en attente, désisté) ; un personnage déjà présent voit son statut mis à jour.
     */
    #[Route('/{inter}/personnages/import', name: 'personnages.import', requirements: ['inter' => Requirement::DIGITS])]
    public function personnagesImportAction(
        Request $request,
        InterJeu $inter,
        PersonnageRepository $personnageRepository,
    ): RedirectResponse|Response {
        $form = $this->createForm(InterJeuPersonnagesImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var InterJeuParticipantStatut $statutDefaut */
            $statutDefaut = $form->get('statut')->getData();

            /** @var array<int, InterJeuParticipantStatut> $lignes identifiant => statut */
            $lignes = [];
            $statutsInconnus = [];

            $idsRaw = (string) $form->get('ids')->getData();
            $saisies = preg_split('/[\s,;]+/', $idsRaw) ?: [];
            foreach ($saisies as $part) {
                if ('' !== $part && ctype_digit($part)) {
                    $lignes[(int) $part] = $statutDefaut;
                }
            }

            /** @var ?UploadedFile $file */
            $file = $form->get('fichier')->getData();
            $lignesCsv = $file ? $this->lireCsv($file->getPathname()) : [];
            foreach ($lignesCsv as $colonnes) {
                $val = trim((string) ($colonnes[0] ?? ''));
                if ('' === $val || !ctype_digit($val)) {
                    continue; // en-tête ou ligne vide
                }
                $saisie = trim((string) ($colonnes[1] ?? ''));
                $statut = InterJeuParticipantStatut::fromSaisie($saisie);
                if (null === $statut && '' !== $saisie) {
                    $statutsInconnus[] = $val;
                }
                $lignes[(int) $val] = $statut ?? $statutDefaut;
            }

            $ajoutes = 0;
            $misAJour = 0;
            $introuvables = [];
            $propositionsTranchees = 0;
            foreach ($lignes as $id => $statut) {
                $personnage = $personnageRepository->find($id);
                if (!$personnage) {
                    $introuvables[] = $id;
                    continue;
                }
                $existante = $inter->getParticipation($personnage);
                null === $existante ? ++$ajoutes : ++$misAJour;
                if ($existante?->hasProposition()) {
                    ++$propositionsTranchees;
                }
                // Le statut importé fait foi : il tranche une éventuelle proposition d'orga externe
                $inter->addPersonnage($personnage, $statut)->annulerProposition();
            }

            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('%d personnage(s) ajouté(s), %d mis à jour.', $ajoutes, $misAJour));
            if ($propositionsTranchees > 0) {
                $this->addFlash('warning', \sprintf('%d proposition(s) d\'orga externe remplacée(s) par le statut importé.', $propositionsTranchees));
            }
            if ([] !== $introuvables) {
                $this->addFlash('warning', \sprintf('Identifiant(s) introuvable(s), ignoré(s) : %s.', implode(', ', $introuvables)));
            }
            if ([] !== $statutsInconnus) {
                $this->addFlash('warning', \sprintf('Statut non reconnu, statut par défaut appliqué pour : %s.', implode(', ', $statutsInconnus)));
            }

            return $this->redirectToRoute('inter.personnages', ['inter' => $inter->getId()], 303);
        }

        return $this->render('inter/personnages_import.html.twig', [
            'interJeu' => $inter,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{inter}/personnage/{personnage}/remove', name: 'personnage.remove', requirements: ['inter' => Requirement::DIGITS, 'personnage' => Requirement::DIGITS], methods: ['POST'])]
    public function personnageRemoveAction(Request $request, InterJeu $inter, Personnage $personnage): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('remove_personnage_' . $personnage->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, recommencez.');

            return $this->redirectToRoute('inter.personnages', ['inter' => $inter->getId()], 303);
        }

        $inter->removePersonnage($personnage);
        $this->entityManager->flush();
        $this->addFlash('success', 'Le personnage a été retiré de l\'inter-jeu.');

        return $this->redirectToRoute('inter.personnages', ['inter' => $inter->getId()], 303);
    }

    #[Route('/{inter}/personnage/{personnage}/statut', name: 'personnage.statut', requirements: ['inter' => Requirement::DIGITS, 'personnage' => Requirement::DIGITS], methods: ['POST'])]
    public function personnageStatutAction(Request $request, InterJeu $inter, Personnage $personnage): RedirectResponse
    {
        $participation = $inter->getParticipation($personnage);
        $statut = InterJeuParticipantStatut::tryFrom((string) $request->request->get('statut'));

        if (!$this->isCsrfTokenValid('statut_personnage_' . $personnage->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, recommencez.');
        } elseif (null === $participation || null === $statut) {
            $this->addFlash('error', 'Participant ou statut inconnu.');
        } else {
            $participation->setStatut($statut)->annulerProposition();
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('%s : %s.', $personnage->getNom(), $statut->getLabel()));
        }

        return $this->redirectToRoute('inter.personnages', ['inter' => $inter->getId()], 303);
    }

    /**
     * Validation ou refus d'une proposition d'un orga externe.
     */
    #[Route('/{inter}/personnage/{personnage}/proposition', name: 'personnage.proposition', requirements: ['inter' => Requirement::DIGITS, 'personnage' => Requirement::DIGITS], methods: ['POST'])]
    public function propositionAction(Request $request, InterJeu $inter, Personnage $personnage): RedirectResponse
    {
        $participation = $inter->getParticipation($personnage);

        if (!$this->isCsrfTokenValid('proposition_' . $personnage->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, recommencez.');
        } elseif (null === $participation || !$participation->hasProposition()) {
            $this->addFlash('error', 'Aucune proposition en attente pour ce personnage.');
        } elseif ('valider' === $request->request->get('decision')) {
            $participation->validerProposition();
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('Proposition validée : %s, %s.', $personnage->getNom(), mb_strtolower($participation->getStatut()->getLabel())));
        } elseif ($participation->isAjoutPropose()) {
            // Ajout refusé : la ligne n'existait que par la proposition
            $inter->removePersonnage($personnage);
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('Ajout refusé : %s retiré de la liste.', $personnage->getNom()));
        } else {
            $participation->annulerProposition();
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('Proposition refusée pour %s.', $personnage->getNom()));
        }

        return $this->redirectToRoute('inter.personnages', ['inter' => $inter->getId()], 303);
    }

    /**
     * Orgas externes ayant accès à l'inter-jeu (consultation et propositions). Ajouter un orga vaut validation de son accès.
     */
    #[Route('/{inter}/orgas', name: 'orgas', requirements: ['inter' => Requirement::DIGITS])]
    public function orgasAction(Request $request, InterJeu $inter, UserRepository $userRepository): RedirectResponse|Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('inter_orgas_' . $inter->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton de sécurité invalide, recommencez.');

                return $this->redirectToRoute('inter.orgas', ['inter' => $inter->getId()], 303);
            }

            $saisie = trim((string) $request->request->get('user'));
            $user = ctype_digit($saisie) ? $userRepository->find((int) $saisie) : $userRepository->findOneBy(['email' => $saisie]);

            if (!$user instanceof User) {
                $this->addFlash('error', 'Aucun utilisateur trouvé pour cet identifiant ou cet e-mail.');
            } elseif (null !== $inter->getOrga($user)) {
                $this->addFlash('warning', 'Cet utilisateur a déjà accès à l\'inter-jeu.');
            } else {
                $inter->addOrga($user, $this->getUser());
                $this->synchroniserRoleOrga($user, true);
                $this->entityManager->flush();
                $this->addFlash('success', \sprintf('Accès validé pour %s.', $user->getUsername()));
            }

            return $this->redirectToRoute('inter.orgas', ['inter' => $inter->getId()], 303);
        }

        return $this->render('inter/orgas.html.twig', [
            'interJeu' => $inter,
        ]);
    }

    #[Route('/{inter}/orga/{orga}/remove', name: 'orga.remove', requirements: ['inter' => Requirement::DIGITS, 'orga' => Requirement::DIGITS], methods: ['POST'])]
    public function orgaRemoveAction(Request $request, InterJeu $inter, InterJeuOrga $orga): RedirectResponse
    {
        if ($orga->getInterJeu() !== $inter || !$this->isCsrfTokenValid('remove_orga_' . $orga->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Demande invalide, recommencez.');

            return $this->redirectToRoute('inter.orgas', ['inter' => $inter->getId()], 303);
        }

        $user = $orga->getUser();
        $inter->removeOrga($orga);
        $this->entityManager->flush();
        $this->synchroniserRoleOrga($user, false);
        $this->entityManager->flush();
        $this->addFlash('success', \sprintf('Accès retiré pour %s.', $user->getUsername()));

        return $this->redirectToRoute('inter.orgas', ['inter' => $inter->getId()], 303);
    }

    #[Route('/{inter}/chronologie', name: 'chronologie', requirements: ['inter' => Requirement::DIGITS])]
    public function chronologieAction(Request $request, InterJeu $inter): RedirectResponse|Response
    {
        if (!$inter->canGenereteChronologie()) {
            $this->addFlash(
                'error',
                $inter->isChronologieGeneree()
                    ? 'La chronologie a déjà été générée pour cet inter-jeu.'
                    : 'L\'inter-jeu n\'est pas encore terminé.',
            );

            return $this->redirectToRoute('inter.detail', ['inter' => $inter->getId()], 303);
        }

        $form = $this
            ->createFormBuilder()
            ->add('confirm', SubmitType::class, [
                'label' => 'Confirmer la génération',
                'attr' => ['class' => 'btn btn-danger'],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $batchSize = 50;
            $i = 0;
            // Inscrits uniquement : les désistés et les personnages en attente n'ont pas participé
            foreach ($inter->getPersonnages() as $personnage) {
                $chronologie = new PersonnageChronologie();
                $chronologie->setPersonnage($personnage);
                $chronologie->setAnnee($inter->getAnneeJeu());
                $chronologie->setEvenement(\sprintf('Participation à %s', $inter->getNom()));
                $this->entityManager->persist($chronologie);
                if ((++$i % $batchSize) === 0) {
                    $this->entityManager->flush();
                }
            }

            $inter->setChronologieGeneree(true);
            $this->entityManager->flush();
            $this->addFlash('success', 'La chronologie a été générée pour tous les participants inscrits.');

            return $this->redirectToRoute('inter.detail', ['inter' => $inter->getId()], 303);
        }

        return $this->render('inter/chronologie_confirm.html.twig', [
            'interJeu' => $inter,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Lignes d'un CSV, séparateur virgule ou point-virgule (export Excel français).
     *
     * @return list<list<string|null>>
     */
    private function lireCsv(string $chemin): array
    {
        $handle = fopen($chemin, 'r');
        if (!$handle) {
            return [];
        }

        $premiere = (string) fgets($handle);
        $separateur = substr_count($premiere, ';') > substr_count($premiere, ',') ? ';' : ',';
        rewind($handle);
        // BOM UTF-8 des exports Excel : sinon la 1re cellule n'est plus un nombre et la ligne serait ignorée
        if ("\xEF\xBB\xBF" !== fread($handle, 3)) {
            rewind($handle);
        }

        $lignes = [];
        while (false !== ($colonnes = fgetcsv($handle, null, $separateur, '"', ''))) {
            $lignes[] = $colonnes;
        }
        fclose($handle);

        return $lignes;
    }

    /**
     * Le rôle ROLE_ORGA_INTER suit les accès : ajouté au premier accès validé, retiré quand il n'en reste aucun.
     * Il ne sert qu'à afficher le menu ; les droits réels sont vérifiés par InterJeuVoter.
     */
    private function synchroniserRoleOrga(User $user, bool $ajout): void
    {
        $roles = array_values(array_diff($user->getRoles(), ['ROLE_USER']));

        if ($ajout) {
            if (!\in_array(Role::ORGA_INTER->value, $roles, true)) {
                $roles[] = Role::ORGA_INTER->value;
                $user->setRoles($roles);
            }

            return;
        }

        $restants = $this->entityManager->getRepository(InterJeuOrga::class)->count(['user' => $user]);
        if (0 === $restants) {
            $user->setRoles(array_values(array_diff($roles, [Role::ORGA_INTER->value])));
        }
    }
}
