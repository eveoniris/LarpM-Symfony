<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\InterJeu;
use App\Entity\Personnage;
use App\Entity\PersonnageChronologie;
use App\Enum\InterJeuParticipantStatut;
use App\Enum\Role;
use App\Form\InterJeuPersonnageAddType;
use App\Form\InterJeuType;
use App\Repository\GnRepository;
use App\Repository\InterJeuRepository;
use App\Security\MultiRolesExpression;
use App\Service\PagerService;
use App\Service\StatsService;
use DateTime;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gestion des inter-jeux : réservée au rôle INTER_JEU (seul en écriture) et aux admins.
 * La consultation par les orgas externes passe par InterConsultationController, leurs accès par InterOrgaController ;
 * l'import, les statuts et la validation des propositions par InterParticipantController.
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
}
