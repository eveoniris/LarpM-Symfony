<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Connaissance;
use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Entity\Langue;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Sort;
use App\Entity\Technologie;
use App\Entity\User;
use App\Exception\ContenuInvalideException;
use App\Exception\EnveloppeIntrouvableException;
use App\Repository\GnRepository;
use App\Security\Voter\DepouillementVoter;
use App\Service\ContenuJoueur;
use App\Service\DeclarationJoueur;
use App\Service\DepouillementContenuService;
use App\Service\DepouillementDeclarationService;
use App\Service\DepouillementOrdresService;
use App\Service\DepouillementRetourGroupeService;
use App\Service\DepouillementService;
use App\Service\DepouillementSuiviGroupeService;
use App\Service\RetourGroupe;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Retour de jeu du joueur et de son groupe pour l'app mobile : mêmes services et mêmes droits que le site (DepouillementVoter).
 * Le joueur ne voit et ne modifie que son retour, et le retour du groupe dont il est chef ou titulaire. Fonctionnalité bêta :
 * ROLE_BETA_TEST requis tant que le module n'est pas ouvert aux joueurs.
 */
#[Route('/api/joueur/gn/{gnId}', name: 'api.joueur.retour.', requirements: ['gnId' => '\d+'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:too-many-methods
// @mago-expect lint:kan-defect
final class ApiJoueurRetourController extends AbstractController
{
    private const array TEXTES = ['sorti_details', 'eleves', 'constructions', 'renommee', 'precisions', 'autres'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DepouillementService $depouillementService,
        private readonly GnRepository $gns,
    ) {
    }

    #[OA\Get(
        summary: 'Mon retour de jeu',
        description: 'Réservé à un bêta-testeur inscrit à ce GN avec un personnage. `ouvert` vaut false tant que le joueur n\'a pas commencé son retour. Les listes (sorts, prières, potions, langues, connaissances, technologies) sont des identifiants.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Retour de jeu', content: new OA\JsonContent(example: [
                'ouvert' => true,
                'statut' => 'en_depouillement',
                'modifiable' => true,
                'declaration' => ['sorti_du_jeu' => false, 'sorti_details' => null, 'eleves' => null, 'constructions' => null, 'renommee' => null, 'precisions' => null, 'autres' => null],
                'appris' => [
                    'sorts' => [3],
                    'prieres' => [],
                    'potions' => [],
                    'langues' => [],
                    'connaissances' => [],
                    'technologies' => [],
                    'enseignant_technologie_numero' => null,
                    'competence_apprise' => null,
                    'enseignant_numero' => null,
                    'personnage_mort' => false,
                    'circonstances_mort' => null,
                ],
            ])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Fonctionnalité réservée aux bêta-testeurs, ou retour d\'un autre joueur'),
            new OA\Response(response: 404, description: 'GN inconnu ou pas de personnage inscrit à ce GN'),
        ],
    )]
    #[Route('/retour', name: 'joueur', methods: ['GET'])]
    public function lireJoueur(int $gnId, DepouillementContenuService $contenuService, DepouillementDeclarationService $declarationService): JsonResponse
    {
        $contexte = $this->contexte($gnId);
        if ($contexte instanceof JsonResponse) {
            return $contexte;
        }

        [$gn, $user] = $contexte;
        $dossier = $this->depouillementService->trouverPourJoueur($gn, $user);
        if (!$dossier instanceof Depouillement) {
            return $this->json(['ouvert' => false]);
        }

        $this->denyAccessUnlessGranted(DepouillementVoter::VOIR, $dossier);

        return $this->json($this->versJoueur($dossier, $contenuService, $declarationService));
    }

    #[OA\Put(
        summary: 'Enregistrer mon retour de jeu',
        description: 'Ouvre le retour s\'il n\'existe pas, puis met à jour les clés fournies (les autres sont conservées). Corps : `declaration` (sorti_du_jeu, sorti_details, eleves, constructions, renommee, precisions, autres) et `appris` (listes d\'identifiants, enseignant_technologie_numero, competence_apprise, enseignant_numero, personnage_mort, circonstances_mort). Les langues, connaissances et technologies secrètes sont refusées (422). Modifiable tant qu\'un organisateur n\'a pas pris le relais.',
        tags: ['Joueur'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: [
            'declaration' => ['precisions' => 'Rien à signaler'],
            'appris' => ['sorts' => [3], 'personnage_mort' => false],
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Retour enregistré (même forme que GET)'),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas de saisie possible pour ce joueur (relais pris par un organisateur) ou fonctionnalité bêta'),
            new OA\Response(response: 404, description: 'GN inconnu ou pas de personnage inscrit'),
            new OA\Response(response: 422, description: 'Corps invalide, identifiant inconnu ou secret, ou retour non modifiable'),
        ],
    )]
    #[Route('/retour', name: 'joueur.enregistrer', methods: ['PUT'])]
    public function enregistrerJoueur(int $gnId, Request $request, DepouillementContenuService $contenuService, DepouillementDeclarationService $declarationService): JsonResponse
    {
        $contexte = $this->contexte($gnId);
        if ($contexte instanceof JsonResponse) {
            return $contexte;
        }

        [$gn, $user] = $contexte;
        $corps = $this->corps($request);
        if (null === $corps) {
            return $this->json(['error' => 'Corps JSON objet requis'], 422);
        }

        try {
            $dossier = $this->depouillementService->trouverPourJoueur($gn, $user) ?? $this->depouillementService->ouvrirPourJoueur($gn, $user);
        } catch (EnveloppeIntrouvableException $exception) {
            return $this->json(['error' => $exception->getMessage()], 404);
        }

        $this->denyAccessUnlessGranted(DepouillementVoter::SAISIR, $dossier);
        $parJoueur = !$this->isGranted(DepouillementVoter::CONTENU, $dossier);

        try {
            $declaration = $this->appliquerDeclaration($declarationService->lire($dossier), $corps['declaration'] ?? []);
            $contenu = $this->appliquerAppris($contenuService->lireContenuJoueur($dossier), $corps['appris'] ?? []);
            $declarationService->enregistrer($dossier, $declaration, $user, $parJoueur);
            $contenuService->enregistrerContenuJoueur($dossier, $contenu, $user, $parJoueur);
        } catch (ContenuInvalideException $exception) {
            return $this->json(['error' => $exception->getMessage()], 422);
        }

        return $this->json($this->versJoueur($dossier, $contenuService, $declarationService));
    }

    #[OA\Post(
        summary: 'Envoyer mon retour de jeu',
        description: 'Passe le retour en « à valider » : il n\'est plus modifiable par le joueur.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Retour envoyé', content: new OA\JsonContent(example: ['statut' => 'a_valider'])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Pas votre retour, ou fonctionnalité bêta'),
            new OA\Response(response: 404, description: 'GN inconnu ou aucun retour ouvert'),
            new OA\Response(response: 422, description: 'Retour déjà envoyé'),
        ],
    )]
    #[Route('/retour/envoyer', name: 'joueur.envoyer', methods: ['POST'])]
    public function envoyerJoueur(int $gnId): JsonResponse
    {
        $contexte = $this->contexte($gnId);
        if ($contexte instanceof JsonResponse) {
            return $contexte;
        }

        [$gn, $user] = $contexte;

        return $this->envoyer($this->depouillementService->trouverPourJoueur($gn, $user), $user);
    }

    #[OA\Get(
        summary: 'Le retour de mon groupe',
        description: 'Réservé au chef de groupe et aux joueurs des six titres. Donne les titres (numéros de personnage), les ordres des trois phases avec leur validation par le wargame, les précisions et le compteur de retours reçus.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Retour du groupe', content: new OA\JsonContent(example: [
                'ouvert' => true,
                'statut' => 'en_depouillement',
                'modifiable' => true,
                'titres' => ['suzerain' => 12, 'chef_guerre' => null, 'diplomate' => null, 'intendant' => null, 'navigateur' => null, 'eminence_grise' => null],
                'ordres' => [['phase' => 1, 'texte' => 'Conquérir la côte', 'valide' => false]],
                'precisions' => null,
                'suivi' => ['recus' => 3, 'attendus' => 8, 'clotures' => 1],
            ])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Ni chef de groupe ni titulaire, ou fonctionnalité bêta'),
            new OA\Response(response: 404, description: 'GN inconnu'),
        ],
    )]
    #[Route('/retour-groupe', name: 'groupe', methods: ['GET'])]
    public function lireGroupe(int $gnId, DepouillementRetourGroupeService $retourService, DepouillementOrdresService $ordresService, DepouillementSuiviGroupeService $suiviService): JsonResponse
    {
        $contexte = $this->contexte($gnId, avecPersonnage: false);
        if ($contexte instanceof JsonResponse) {
            return $contexte;
        }

        [$gn, $user] = $contexte;
        if (!$this->depouillementService->groupeDuJoueur($gn, $user) instanceof \App\Entity\GroupeGn) {
            return $this->json(['error' => 'Vous n\'êtes ni chef de groupe ni titulaire d\'un titre de groupe à ce GN.'], 403);
        }

        $dossier = $this->dossierGroupe($gn, $user, ouvrir: false);
        if (!$dossier instanceof Depouillement) {
            return $this->json(['ouvert' => false]);
        }

        $this->denyAccessUnlessGranted(DepouillementVoter::VOIR, $dossier);

        return $this->json($this->versGroupe($dossier, $retourService, $ordresService, $suiviService));
    }

    #[OA\Put(
        summary: 'Enregistrer le retour de mon groupe',
        description: 'Ouvre le retour du groupe s\'il n\'existe pas, puis met à jour les clés fournies. Corps : `titres` (clé du titre => numéro de personnage inscrit à ce GN, ou null), `ordres` (numéro de phase 1 à 3 => texte), `precisions`. Partagé entre le chef de groupe et les titulaires jusqu\'à la reprise par un organisateur.',
        tags: ['Joueur'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(example: ['titres' => ['diplomate' => 14], 'ordres' => ['1' => 'Conquérir la côte']])),
        responses: [
            new OA\Response(response: 200, description: 'Retour enregistré (même forme que GET)'),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Ni chef de groupe ni titulaire, relais pris par un organisateur, ou fonctionnalité bêta'),
            new OA\Response(response: 404, description: 'GN inconnu'),
            new OA\Response(response: 422, description: 'Corps invalide, numéro de titre hors GN, ou retour non modifiable'),
        ],
    )]
    #[Route('/retour-groupe', name: 'groupe.enregistrer', methods: ['PUT'])]
    public function enregistrerGroupe(
        int $gnId,
        Request $request,
        DepouillementRetourGroupeService $retourService,
        DepouillementOrdresService $ordresService,
        DepouillementSuiviGroupeService $suiviService,
    ): JsonResponse {
        $contexte = $this->contexte($gnId, avecPersonnage: false);
        if ($contexte instanceof JsonResponse) {
            return $contexte;
        }

        [$gn, $user] = $contexte;
        $corps = $this->corps($request);
        if (null === $corps) {
            return $this->json(['error' => 'Corps JSON objet requis'], 422);
        }

        $dossier = $this->dossierGroupe($gn, $user, ouvrir: true);
        if (!$dossier instanceof Depouillement) {
            return $this->json(['error' => 'Vous n\'êtes ni chef de groupe ni titulaire d\'un titre de groupe à ce GN.'], 403);
        }

        $this->denyAccessUnlessGranted(DepouillementVoter::SAISIR, $dossier);

        try {
            $retourService->enregistrer($dossier, $this->appliquerGroupe($retourService->lire($dossier), $corps), $user);
        } catch (ContenuInvalideException $exception) {
            return $this->json(['error' => $exception->getMessage()], 422);
        }

        return $this->json($this->versGroupe($dossier, $retourService, $ordresService, $suiviService));
    }

    #[OA\Post(
        summary: 'Envoyer le retour de mon groupe',
        description: 'Passe le retour du groupe en « à valider » : plus modifiable par les titulaires.',
        tags: ['Joueur'],
        responses: [
            new OA\Response(response: 200, description: 'Retour envoyé', content: new OA\JsonContent(example: ['statut' => 'a_valider'])),
            new OA\Response(response: 401, description: 'Jeton absent ou invalide'),
            new OA\Response(response: 403, description: 'Ni chef de groupe ni titulaire, ou fonctionnalité bêta'),
            new OA\Response(response: 404, description: 'GN inconnu ou aucun retour de groupe ouvert'),
            new OA\Response(response: 422, description: 'Retour déjà envoyé'),
        ],
    )]
    #[Route('/retour-groupe/envoyer', name: 'groupe.envoyer', methods: ['POST'])]
    public function envoyerGroupe(int $gnId): JsonResponse
    {
        $contexte = $this->contexte($gnId, avecPersonnage: false);
        if ($contexte instanceof JsonResponse) {
            return $contexte;
        }

        [$gn, $user] = $contexte;

        return $this->envoyer($this->dossierGroupe($gn, $user, ouvrir: false), $user);
    }

    /**
     * @return array{0: Gn, 1: User}|JsonResponse
     */
    private function contexte(int $gnId, bool $avecPersonnage = true): array|JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isGranted('ROLE_BETA_TEST')) {
            return $this->json(['error' => 'Fonctionnalité en cours de test.'], 403);
        }

        $gn = $this->gns->find($gnId);
        if (!$gn instanceof Gn) {
            return $this->json(['error' => 'GN non trouvé'], 404);
        }

        if ($avecPersonnage && null === $user->getParticipant($gn)?->getPersonnage()) {
            return $this->json(['error' => 'Aucun personnage inscrit à ce GN'], 404);
        }

        return [$gn, $user];
    }

    private function dossierGroupe(Gn $gn, User $user, bool $ouvrir): ?Depouillement
    {
        $groupeGn = $this->depouillementService->groupeDuJoueur($gn, $user);
        if (!$groupeGn instanceof \App\Entity\GroupeGn) {
            return null;
        }

        return $ouvrir
            ? $this->depouillementService->ouvrirPourGroupe($gn, $user)
            : $this->depouillementService->trouverReel($gn, \App\Enum\DepouillementType::GROUPE, (string) $groupeGn->getGroupe()->getNumero());
    }

    private function envoyer(?Depouillement $dossier, User $user): JsonResponse
    {
        if (!$dossier instanceof Depouillement) {
            return $this->json(['error' => 'Aucun retour ouvert'], 404);
        }

        $this->denyAccessUnlessGranted(DepouillementVoter::SAISIR, $dossier);
        if (!$this->depouillementService->terminerSaisie($dossier, $user)) {
            return $this->json(['error' => Depouillement::MESSAGE_NON_MODIFIABLE], 422);
        }

        return $this->json(['statut' => $dossier->getStatut()->value]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function corps(Request $request): ?array
    {
        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload) || array_is_list($payload)) {
            return null;
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function versJoueur(Depouillement $dossier, DepouillementContenuService $contenuService, DepouillementDeclarationService $declarationService): array
    {
        $contenu = $contenuService->lireContenuJoueur($dossier);

        return [
            'ouvert' => true,
            'statut' => $dossier->getStatut()->value,
            'modifiable' => $dossier->getStatut()->isEditable() && $this->isGranted(DepouillementVoter::SAISIR, $dossier),
            'declaration' => $declarationService->lire($dossier)->versTableau(),
            'appris' => [
                'sorts' => $this->ids($contenu->sorts),
                'prieres' => $this->ids($contenu->prieres),
                'potions' => $this->ids($contenu->potions),
                'langues' => $this->ids($contenu->langues),
                'connaissances' => $this->ids($contenu->connaissances),
                'technologies' => $this->ids($contenu->technologies),
                'enseignant_technologie_numero' => $contenu->enseignantTechnologieNumero,
                'competence_apprise' => $contenu->competenceApprise?->getId(),
                'enseignant_numero' => $contenu->enseignantNumero,
                'personnage_mort' => $contenu->personnageMort,
                'circonstances_mort' => $contenu->circonstancesMort,
            ],
        ];
    }

    /**
     * @param list<Sort|Priere|Potion|Langue|Connaissance|Technologie> $entites
     *
     * @return list<int|null>
     */
    private function ids(array $entites): array
    {
        return array_values(array_map(static fn (Sort|Priere|Potion|Langue|Connaissance|Technologie $entite): ?int => $entite->getId(), $entites));
    }

    /**
     * @return array<string, mixed>
     */
    private function versGroupe(
        Depouillement $dossier,
        DepouillementRetourGroupeService $retourService,
        DepouillementOrdresService $ordresService,
        DepouillementSuiviGroupeService $suiviService,
    ): array {
        $retour = $retourService->lire($dossier);
        $suivi = $suiviService->suivi($dossier);
        $ordres = [];
        foreach ($ordresService->phases($dossier) as $numero => $phase) {
            $ordres[] = ['phase' => $numero, 'texte' => $phase['texte'], 'valide' => $phase['valide']];
        }

        return [
            'ouvert' => true,
            'statut' => $dossier->getStatut()->value,
            'modifiable' => $dossier->getStatut()->isEditable() && $this->isGranted(DepouillementVoter::SAISIR, $dossier),
            'titres' => $retour->versTableau()['titres'],
            'ordres' => $ordres,
            'precisions' => $retour->precisions,
            'suivi' => ['recus' => $suivi['recus'], 'attendus' => $suivi['attendus'], 'clotures' => $suivi['clotures']],
        ];
    }

    /**
     * @throws ContenuInvalideException si un champ a un type inattendu
     */
    private function appliquerDeclaration(DeclarationJoueur $declaration, mixed $donnees): DeclarationJoueur
    {
        if (!\is_array($donnees)) {
            throw new ContenuInvalideException('`declaration` doit être un objet.');
        }

        if (\array_key_exists('sorti_du_jeu', $donnees)) {
            $declaration->sortiDuJeu = true === $donnees['sorti_du_jeu'];
        }

        foreach (self::TEXTES as $cle) {
            if (!\array_key_exists($cle, $donnees)) {
                continue;
            }

            $valeur = $donnees[$cle];
            if (null !== $valeur && (!\is_string($valeur) || mb_strlen($valeur) > 5000)) {
                throw new ContenuInvalideException(\sprintf('`%s` doit être un texte de 5000 caractères au plus.', $cle));
            }

            match ($cle) {
                'sorti_details' => $declaration->sortiDetails = $valeur,
                'eleves' => $declaration->eleves = $valeur,
                'constructions' => $declaration->constructions = $valeur,
                'renommee' => $declaration->renommee = $valeur,
                'precisions' => $declaration->precisions = $valeur,
                default => $declaration->autres = $valeur,
            };
        }

        return $declaration;
    }

    /**
     * @throws ContenuInvalideException si un identifiant est inconnu, secret, ou mal typé
     */
    private function appliquerAppris(ContenuJoueur $contenu, mixed $donnees): ContenuJoueur
    {
        if (!\is_array($donnees)) {
            throw new ContenuInvalideException('`appris` doit être un objet.');
        }

        if (\array_key_exists('sorts', $donnees)) {
            $contenu->sorts = $this->entites(Sort::class, $donnees['sorts'], false, 'sorts');
        }

        if (\array_key_exists('prieres', $donnees)) {
            $contenu->prieres = $this->entites(Priere::class, $donnees['prieres'], false, 'prieres');
        }

        if (\array_key_exists('potions', $donnees)) {
            $contenu->potions = $this->entites(Potion::class, $donnees['potions'], false, 'potions');
        }

        if (\array_key_exists('langues', $donnees)) {
            $contenu->langues = $this->entites(Langue::class, $donnees['langues'], true, 'langues');
        }

        if (\array_key_exists('connaissances', $donnees)) {
            $contenu->connaissances = $this->entites(Connaissance::class, $donnees['connaissances'], true, 'connaissances');
        }

        if (\array_key_exists('technologies', $donnees)) {
            $contenu->technologies = $this->entites(Technologie::class, $donnees['technologies'], true, 'technologies');
        }

        if (\array_key_exists('competence_apprise', $donnees)) {
            $competences = null === $donnees['competence_apprise'] ? [] : $this->entites(\App\Entity\Competence::class, [$donnees['competence_apprise']], false, 'competence_apprise');
            $contenu->competenceApprise = $competences[0] ?? null;
        }

        foreach (['enseignant_technologie_numero', 'enseignant_numero'] as $cle) {
            if (\array_key_exists($cle, $donnees)) {
                $numero = $this->entierOuNull($donnees[$cle], $cle);
                'enseignant_numero' === $cle ? ($contenu->enseignantNumero = $numero) : ($contenu->enseignantTechnologieNumero = $numero);
            }
        }

        if (\array_key_exists('personnage_mort', $donnees)) {
            $contenu->personnageMort = true === $donnees['personnage_mort'];
        }

        if (\array_key_exists('circonstances_mort', $donnees)) {
            $circonstances = $donnees['circonstances_mort'];
            if (null !== $circonstances && !\is_string($circonstances)) {
                throw new ContenuInvalideException('`circonstances_mort` doit être un texte.');
            }

            $contenu->circonstancesMort = $circonstances;
        }

        return $contenu;
    }

    private function appliquerGroupe(RetourGroupe $retour, mixed $corps): RetourGroupe
    {
        if (!\is_array($corps)) {
            throw new ContenuInvalideException('Corps invalide.');
        }

        if (\array_key_exists('titres', $corps)) {
            if (!\is_array($corps['titres'])) {
                throw new ContenuInvalideException('`titres` doit être un objet.');
            }

            foreach ($corps['titres'] as $cle => $numero) {
                if (!isset(RetourGroupe::TITRES[(string) $cle])) {
                    throw new ContenuInvalideException(\sprintf('Titre inconnu : %s.', (string) $cle));
                }

                $retour->titres[(string) $cle] = $this->entierOuNull($numero, 'titres.' . $cle);
            }
        }

        if (\array_key_exists('ordres', $corps)) {
            $ordres = $corps['ordres'];
            if (!\is_array($ordres)) {
                throw new ContenuInvalideException('`ordres` doit être un objet.');
            }

            foreach ($ordres as $phase => $texte) {
                $texte = $this->texteOuNull($texte, 'ordres.' . $phase);
                match ((string) $phase) {
                    '1' => $retour->ordres1 = $texte,
                    '2' => $retour->ordres2 = $texte,
                    '3' => $retour->ordres3 = $texte,
                    default => throw new ContenuInvalideException('Phase inconnue : 1 à 3.'),
                };
            }
        }

        if (\array_key_exists('precisions', $corps)) {
            $retour->precisions = $this->texteOuNull($corps['precisions'], 'precisions');
        }

        return $retour;
    }

    private function texteOuNull(mixed $valeur, string $nom): ?string
    {
        if (null !== $valeur && (!\is_string($valeur) || mb_strlen($valeur) > 10_000)) {
            throw new ContenuInvalideException(\sprintf('`%s` doit être un texte de 10000 caractères au plus.', $nom));
        }

        return $valeur;
    }

    private function entierOuNull(mixed $valeur, string $nom): ?int
    {
        if (null === $valeur) {
            return null;
        }

        if (!\is_int($valeur) || $valeur < 1) {
            throw new ContenuInvalideException(\sprintf('`%s` doit être un entier positif ou null.', $nom));
        }

        return $valeur;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $classe
     *
     * @return list<T>
     */
    private function entites(string $classe, mixed $ids, bool $nonSecret, string $nom): array
    {
        if (!\is_array($ids) || !array_is_list($ids) || \count($ids) > 200) {
            throw new ContenuInvalideException(\sprintf('`%s` doit être une liste d\'identifiants (200 au plus).', $nom));
        }

        foreach ($ids as $id) {
            if (!\is_int($id) || $id < 1) {
                throw new ContenuInvalideException(\sprintf('`%s` ne contient que des identifiants entiers positifs.', $nom));
            }
        }

        $ids = array_values(array_unique($ids));
        $criteres = ['id' => $ids];
        if ($nonSecret) {
            $criteres['secret'] = false;
        }

        $entites = [] === $ids ? [] : $this->entityManager->getRepository($classe)->findBy($criteres);
        if (\count($entites) !== \count($ids)) {
            throw new ContenuInvalideException(\sprintf('`%s` : identifiant inconnu%s.', $nom, $nonSecret ? ' ou secret (signalez-le dans les précisions)' : ''));
        }

        return array_values($entites);
    }
}
