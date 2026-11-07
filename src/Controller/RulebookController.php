<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\RulebookDomaine;
use App\Entity\RulebookRegle;
use App\Enum\Role;
use App\Enum\RulebookStatut;
use App\Repository\RulebookRegleRepository;
use App\Security\Voter\RulebookVoter;
use App\Service\Rulebook\MarkdownRenderer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Consultation du rulebook (règles de gameplay), réservée à ROLE_BETA_TEST le temps du test.
 *
 * Scénaristes : version claire des règles publiées. Équipe règle (ROLE_REGLE) et admins : toutes les règles, détail
 * technique, statuts et brouillons. Voir {@see RulebookVoter}.
 */
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
final class RulebookController extends AbstractController
{
    public function __construct(
        private readonly RulebookRegleRepository $regleRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MarkdownRenderer $renderer,
    ) {
    }

    #[Route('/rulebook', name: 'rulebook.index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted(RulebookVoter::ACCESS);

        $equipeRegle = $this->isGranted(Role::REGLE->value);
        $recherche = trim((string) $request->query->get('q', ''));
        $statut = $equipeRegle ? RulebookStatut::tryFrom((string) $request->query->get('statut', '')) : null;
        $domaineCode = (string) $request->query->get('domaine', '');

        $regles = $this->regleRepository->findConsultables($equipeRegle, $recherche, $statut, $domaineCode);

        // Domaines qui ont au moins une règle consultable, pour le sommaire (indépendant du filtre de domaine).
        $domaines = [];
        foreach ($this->regleRepository->findConsultables($equipeRegle, $recherche, $statut) as $regle) {
            $domaine = $regle->getDomaine();
            $domaines[$domaine->getCode()] ??= ['domaine' => $domaine, 'nombre' => 0];
            ++$domaines[$domaine->getCode()]['nombre'];
        }

        /** @var array<string, array{domaine: RulebookDomaine, regles: list<RulebookRegle>}> $groupes */
        $groupes = [];
        foreach ($regles as $regle) {
            $domaine = $regle->getDomaine();
            $groupes[$domaine->getCode()] ??= ['domaine' => $domaine, 'regles' => []];
            $groupes[$domaine->getCode()]['regles'][] = $regle;
        }

        return $this->render('rulebook/index.twig', [
            'groupes' => $groupes,
            'domaines' => $domaines,
            'total' => \count($regles),
            'equipeRegle' => $equipeRegle,
            'statuts' => RulebookStatut::cases(),
            'recherche' => $recherche,
            'statutFiltre' => $statut,
            'domaineFiltre' => $domaineCode,
        ]);
    }

    #[Route('/rulebook/regle/{code}', name: 'rulebook.regle', requirements: ['code' => '[A-Za-z0-9-]+'], methods: ['GET'])]
    public function regle(string $code): Response
    {
        $this->denyAccessUnlessGranted(RulebookVoter::ACCESS);

        $regle = $this->entityManager->getRepository(RulebookRegle::class)->findOneBy(['code' => $code]);
        // Une règle que l'on n'a pas le droit de voir est présentée comme inexistante.
        if (!$regle instanceof RulebookRegle || !$this->isGranted(RulebookVoter::VIEW, $regle)) {
            throw $this->createNotFoundException('Règle introuvable.');
        }

        $technique = $this->isGranted(RulebookVoter::VIEW_TECHNIQUE, $regle);

        return $this->render('rulebook/regle.twig', [
            'regle' => $regle,
            'texteClairHtml' => $this->renderer->rendre($regle->getTexteClair()),
            'detailTechniqueHtml' => $technique ? $this->renderer->rendre($regle->getDetailTechnique()) : null,
            'equipeRegle' => $technique,
        ]);
    }
}
