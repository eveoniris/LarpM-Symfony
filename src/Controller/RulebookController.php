<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\RulebookDomaine;
use App\Entity\RulebookRegle;
use App\Entity\RulebookRevision;
use App\Entity\User;
use App\Enum\Role;
use App\Enum\RulebookStatut;
use App\Form\RulebookRegleType;
use App\Repository\RulebookRegleRepository;
use App\Security\Voter\RulebookVoter;
use App\Service\Rulebook\MarkdownNormalizer;
use App\Service\Rulebook\MarkdownRenderer;
use App\Service\Rulebook\RulebookEditionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Consultation du rulebook (règles de gameplay), réservée à ROLE_BETA_TEST le temps du test.
 *
 * Scénaristes : version claire des règles publiées. Équipe règle (ROLE_REGLE) et admins : toutes les règles, détail
 * technique, statuts et brouillons. Voir {@see RulebookVoter}.
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
#[IsGranted('ROLE_BETA_TEST', message: 'Fonctionnalité en cours de test.')]
final class RulebookController extends AbstractController
{
    // @mago-expect lint:excessive-parameter-list
    public function __construct(
        private readonly RulebookRegleRepository $regleRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MarkdownRenderer $renderer,
        private readonly MarkdownNormalizer $normalizer,
        private readonly RulebookEditionService $edition,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
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
        $detailHtml = $technique ? $this->renderer->rendre($regle->getDetailTechnique()) : null;

        // Références de tests pas déjà citées dans le détail technique (évite l'affichage en double).
        $testsRefs = [];
        if ($technique) {
            foreach ($regle->getTestsRefs() as $ref) {
                if (!str_contains((string) $detailHtml, htmlspecialchars((string) $ref, ENT_NOQUOTES))) {
                    $testsRefs[] = $ref;
                }
            }
        }

        return $this->render('rulebook/regle.twig', [
            'regle' => $regle,
            'texteClairHtml' => $this->renderer->rendre($regle->getTexteClair()),
            'detailTechniqueHtml' => $detailHtml,
            'testsRefs' => $testsRefs,
            'equipeRegle' => $technique,
        ]);
    }

    #[Route('/rulebook/nouvelle', name: 'rulebook.regle.nouvelle', methods: ['GET', 'POST'])]
    public function nouvelle(Request $request): Response
    {
        $this->denyAccessUnlessGranted(Role::REGLE->value);

        $regle = new RulebookRegle();
        $domaine = $this->entityManager->getRepository(RulebookDomaine::class)->findOneBy(['code' => (string) $request->query->get('domaine', '')]);
        if ($domaine instanceof RulebookDomaine) {
            $regle->setDomaine($domaine);
            $regle->setPosition(\count($domaine->getRegles()) + 1);
        }

        return $this->editer($request, $regle, true);
    }

    #[Route('/rulebook/regle/{code}/editer', name: 'rulebook.regle.editer', requirements: ['code' => '[A-Za-z0-9-]+'], methods: ['GET', 'POST'])]
    public function modifier(Request $request, string $code): Response
    {
        $regle = $this->trouverRegle($code);
        $this->denyAccessUnlessGranted(RulebookVoter::EDIT, $regle);

        return $this->editer($request, $regle, false);
    }

    #[Route('/rulebook/regle/{code}/historique', name: 'rulebook.regle.historique', requirements: ['code' => '[A-Za-z0-9-]+'], methods: ['GET'])]
    public function historique(string $code): Response
    {
        $regle = $this->trouverRegle($code);
        $this->denyAccessUnlessGranted(RulebookVoter::EDIT, $regle);

        return $this->render('rulebook/historique.twig', ['regle' => $regle]);
    }

    #[Route('/rulebook/regle/{code}/restaurer/{version}', name: 'rulebook.regle.restaurer', requirements: ['code' => '[A-Za-z0-9-]+', 'version' => '\d+'], methods: ['POST'])]
    public function restaurer(Request $request, string $code, int $version): Response
    {
        $this->denyAccessUnlessGranted(Role::ADMIN->value);

        if (!$this->isCsrfTokenValid('rulebook_restaurer_' . $code, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton invalide.');
        }

        $regle = $this->trouverRegle($code);
        $source = $this->entityManager->getRepository(RulebookRevision::class)->findOneBy(['regle' => $regle, 'version' => $version]);
        if (!$source instanceof RulebookRevision) {
            throw $this->createNotFoundException('Version introuvable.');
        }

        $utilisateur = $this->getUser();
        $this->edition->restaurer($regle, $source, $utilisateur instanceof User ? $utilisateur : null);
        $this->addFlash('success', \sprintf('Version %d restaurée (nouvelle version %d).', $version, $regle->getVersion()));

        return $this->redirectToRoute('rulebook.regle', ['code' => $regle->getCode()]);
    }

    /** Aperçu du markdown pour l'éditeur : même rendu sûr que la page de lecture. */
    #[Route('/rulebook/apercu', name: 'rulebook.apercu', methods: ['POST'])]
    public function apercu(Request $request): Response
    {
        $this->denyAccessUnlessGranted(Role::REGLE->value);

        if (!$this->isCsrfTokenValid('rulebook_apercu', (string) $request->headers->get('X-CSRF-Token'))) {
            throw $this->createAccessDeniedException('Jeton invalide.');
        }

        $texte = $this->normalizer->normaliser((string) $request->getContent())->texte;

        return new Response($this->renderer->rendre($texte));
    }

    private function trouverRegle(string $code): RulebookRegle
    {
        $regle = $this->entityManager->getRepository(RulebookRegle::class)->findOneBy(['code' => $code]);
        if (!$regle instanceof RulebookRegle) {
            throw $this->createNotFoundException('Règle introuvable.');
        }

        return $regle;
    }

    private function editer(Request $request, RulebookRegle $regle, bool $creation): Response
    {
        $empreinte = $creation ? null : $this->edition->empreinte($regle);
        $form = $this->createForm(RulebookRegleType::class, $regle, ['creation' => $creation]);
        if (!$form->isSubmitted()) {
            $form->get('testsRefs')->setData(implode("\n", $regle->getTestsRefs()));
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lignes = preg_split('/\R/', (string) $form->get('testsRefs')->getData(), -1, PREG_SPLIT_NO_EMPTY);
            $regle->setTestsRefs(array_values(array_unique(array_map('trim', false === $lignes ? [] : $lignes))));

            if ($regle->isPubliee() && '' === trim((string) $regle->getTexteClair())) {
                $form->get('publiee')->addError(new FormError('Rédigez la version claire avant de publier la règle.'));
            } elseif ($creation && null !== $this->entityManager->getRepository(RulebookRegle::class)->findOneBy(['code' => $regle->getCode()])) {
                $form->get('code')->addError(new FormError('Ce code existe déjà.'));
            } else {
                $utilisateur = $this->getUser();
                $resultat = $this->edition->enregistrer($regle, $utilisateur instanceof User ? $utilisateur : null, (string) $form->get('motif')->getData(), $empreinte);

                $message = $resultat['modifiee'] ? \sprintf('Règle %s enregistrée (version %d).', $regle->getCode(), $resultat['version']) : 'Aucune modification à enregistrer.';
                if ($resultat['nettoyes'] > 0) {
                    $message .= \sprintf(' %d caractère(s) invisible(s) ou indésirable(s) retiré(s) des textes.', $resultat['nettoyes']);
                }
                $this->addFlash('success', $message);

                return $this->redirectToRoute('rulebook.regle', ['code' => $regle->getCode()]);
            }
        }

        return $this->render('rulebook/editer.twig', [
            'form' => $form,
            'regle' => $regle,
            'creation' => $creation,
            'csrfApercu' => $this->csrfTokenManager->getToken('rulebook_apercu')->getValue(),
        ]);
    }
}
