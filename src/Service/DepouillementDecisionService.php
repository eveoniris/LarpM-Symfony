<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Competence;
use App\Entity\Connaissance;
use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Entity\Langue;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Sort;
use App\Entity\Technologie;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Exception\ContenuInvalideException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Décisions d'un responsable sur ce que le joueur a déclaré : confirmer, refuser, ou remettre en attente un élément refusé.
 * Compléter se fait en saisissant l'élément manquant (il est confirmé d'office, voir DecisionsDeclaration).
 *
 * Refuser retire l'élément de ce qui sera appliqué (les listes et champs de `contenu_joueur` et `declaration`) pour que la validation
 * ne l'applique jamais ; la décision garde le libellé ou la valeur refusés, et « remettre » les rétablit.
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
// @mago-expect lint:too-many-methods
readonly class DepouillementDecisionService
{
    public const string CONFIRMER = 'confirmer';

    public const string REFUSER = 'refuser';

    public const string REMETTRE = 'remettre';

    /** Champs en liste d'identifiants (dans `contenu_joueur`) et leur entité. */
    public const array LISTES = [
        'sorts' => Sort::class,
        'prieres' => Priere::class,
        'potions' => Potion::class,
        'langues' => Langue::class,
        'connaissances' => Connaissance::class,
        'technologies' => Technologie::class,
    ];

    /** Champs simples et la partie des données qui les porte. */
    private const array SIMPLES = [
        'competence_apprise' => 'contenu_joueur',
        'enseignant_numero' => 'contenu_joueur',
        'personnage_mort' => 'contenu_joueur',
        'circonstances_mort' => 'contenu_joueur',
        'sorti_du_jeu' => 'declaration',
        'sorti_details' => 'declaration',
        'eleves' => 'declaration',
        'constructions' => 'declaration',
        'renommee' => 'declaration',
        'renommee_points' => 'declaration',
        'precisions' => 'declaration',
        'autres' => 'declaration',
    ];

    public const array LIBELLES = [
        'sorts' => 'Sort',
        'prieres' => 'Prière',
        'potions' => 'Potion (recette)',
        'langues' => 'Langue',
        'connaissances' => 'Connaissance',
        'technologies' => 'Technologie',
        'competence_apprise' => 'Compétence apprise',
        'enseignant_numero' => 'Enseignant de la compétence',
        'personnage_mort' => 'Mort du personnage',
        'circonstances_mort' => 'Circonstances de la mort',
        'sorti_du_jeu' => 'Sortie du jeu',
        'sorti_details' => 'Détails de la sortie',
        'eleves' => 'Enseignements donnés',
        'constructions' => 'Constructions',
        'renommee' => 'Renommée',
        'renommee_points' => 'Renommée (points)',
        'precisions' => 'Précisions',
        'autres' => 'Autres informations',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws ContenuInvalideException si le dossier n'est pas modifiable, ou si le champ, l'élément ou la décision est inconnu
     */
    public function decider(Depouillement $depouillement, string $champ, string $element, string $decision, User $utilisateur): void
    {
        if (DepouillementType::JOUEUR !== $depouillement->getType() || !$depouillement->getStatut()->isEditable()) {
            throw new ContenuInvalideException(Depouillement::MESSAGE_NON_MODIFIABLE);
        }

        $estListe = isset(self::LISTES[$champ]);
        if (!$estListe && !isset(self::SIMPLES[$champ])) {
            throw new ContenuInvalideException('Ce champ ne se décide pas.');
        }

        $element = $estListe ? $element : DecisionsDeclaration::ELEMENT_UNIQUE;
        $donnees = $depouillement->getDonnees();
        $decisions = DecisionsDeclaration::depuis($donnees);
        $auteur = new AuteurDeclaration(false, (int) $utilisateur->getId(), new DateTimeImmutable());

        [$donnees, $decisions] = match ($decision) {
            self::CONFIRMER => [$donnees, DecisionsDeclaration::decider($decisions, $champ, $element, DecisionsDeclaration::ETAT_CONFIRME, $auteur)],
            self::REFUSER => $this->refuser($donnees, $decisions, $champ, $element, $auteur),
            self::REMETTRE => $this->remettre($donnees, $decisions, $champ, $element, $auteur),
            default => throw new ContenuInvalideException('Décision inconnue.'),
        };

        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($utilisateur)
                ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                ->setDataBefore(['decisions' => DecisionsDeclaration::depuis($depouillement->getDonnees())])
                ->setDataAfter(['decisions' => $decisions])
                ->setMotif(\sprintf('Déclaration du joueur : %s (%s %s)', $decision, self::LIBELLES[$champ], DecisionsDeclaration::ELEMENT_UNIQUE === $element ? '' : '#' . $element)),
        );
        $depouillement->setDonnees([...$donnees, 'decisions' => $decisions])->touch();
        $this->entityManager->flush();
    }

    /**
     * Les lignes déclarées (avec leur provenance et leur état) pour l'écran du staff.
     *
     * @return list<array{champ: string, libelleChamp: string, element: string, valeur: string, source: string, etat: string}>
     */
    public function lignes(Depouillement $depouillement): array
    {
        $donnees = $depouillement->getDonnees();
        $decisions = DecisionsDeclaration::depuis($donnees);
        $lignes = [];
        foreach ($decisions as $champ => $elements) {
            foreach (array_keys($elements) as $element) {
                $etat = DecisionsDeclaration::lire($decisions, $champ, $element);
                $lignes[] = [
                    'champ' => $champ,
                    'libelleChamp' => self::LIBELLES[$champ] ?? $champ,
                    'element' => (string) $element,
                    'valeur' => $this->valeurAffichee($donnees, $decisions, $champ, (string) $element),
                    'source' => $etat['source'],
                    'etat' => $etat['etat'],
                ];
            }
        }

        return $lignes;
    }

    /**
     * @param array<array-key, mixed>                            $donnees
     * @param array<string, array<string, array<string, mixed>>> $decisions
     *
     * @return array{0: array<array-key, mixed>, 1: array<string, array<string, array<string, mixed>>>}
     */
    private function refuser(array $donnees, array $decisions, string $champ, string $element, AuteurDeclaration $auteur): array
    {
        if (isset(self::LISTES[$champ])) {
            $contenu = self::partie($donnees, 'contenu_joueur');
            $ids = self::ids($contenu[$champ] ?? []);
            if (!\in_array((int) $element, $ids, strict: true)) {
                throw new ContenuInvalideException('Cet élément n\'est pas dans la déclaration.');
            }

            $contenu[$champ] = array_values(array_filter($ids, static fn (int $id): bool => $id !== (int) $element));
            $donnees['contenu_joueur'] = $contenu;
            $libelle = $this->libelle(self::classe($champ), (int) $element) ?? '#' . $element;

            return [$donnees, DecisionsDeclaration::decider($decisions, $champ, $element, DecisionsDeclaration::ETAT_REFUSE, $auteur, ['libelle' => $libelle])];
        }

        $partie = self::partie($donnees, self::SIMPLES[$champ]);
        $valeur = $partie[$champ] ?? null;
        $partie[$champ] = \is_bool($valeur) ? false : null;
        $donnees[self::SIMPLES[$champ]] = $partie;

        return [$donnees, DecisionsDeclaration::decider($decisions, $champ, $element, DecisionsDeclaration::ETAT_REFUSE, $auteur, ['valeur' => $valeur])];
    }

    /**
     * @param array<array-key, mixed>                            $donnees
     * @param array<string, array<string, array<string, mixed>>> $decisions
     *
     * @return array{0: array<array-key, mixed>, 1: array<string, array<string, array<string, mixed>>>}
     */
    private function remettre(array $donnees, array $decisions, string $champ, string $element, AuteurDeclaration $auteur): array
    {
        $entree = DecisionsDeclaration::entree($decisions, $champ, $element);
        if (null === $entree || DecisionsDeclaration::ETAT_REFUSE !== ($entree['etat'] ?? null)) {
            throw new ContenuInvalideException('Seul un élément refusé peut être remis en attente.');
        }

        if (isset(self::LISTES[$champ])) {
            if (null === $this->libelle(self::classe($champ), (int) $element)) {
                throw new ContenuInvalideException('Cet élément n\'existe plus.');
            }

            $contenu = self::partie($donnees, 'contenu_joueur');
            $contenu[$champ] = array_values(array_unique([...self::ids($contenu[$champ] ?? []), (int) $element]));
            $donnees['contenu_joueur'] = $contenu;
        } else {
            $partie = self::partie($donnees, self::SIMPLES[$champ]);
            $partie[$champ] = $entree['valeur'] ?? null;
            $donnees[self::SIMPLES[$champ]] = $partie;
        }

        return [$donnees, DecisionsDeclaration::decider($decisions, $champ, $element, DecisionsDeclaration::ETAT_EN_ATTENTE, $auteur)];
    }

    /**
     * @param array<array-key, mixed>                            $donnees
     * @param array<string, array<string, array<string, mixed>>> $decisions
     */
    private function valeurAffichee(array $donnees, array $decisions, string $champ, string $element): string
    {
        $entree = DecisionsDeclaration::entree($decisions, $champ, $element) ?? [];
        $refuse = DecisionsDeclaration::ETAT_REFUSE === ($entree['etat'] ?? null);

        if (isset(self::LISTES[$champ])) {
            $libelle = $refuse ? null : $this->libelle(self::classe($champ), (int) $element);

            return $libelle ?? (\is_string($entree['libelle'] ?? null) ? $entree['libelle'] : '#' . $element);
        }

        $valeur = $refuse ? $entree['valeur'] ?? null : self::partie($donnees, self::SIMPLES[$champ])[$champ] ?? null;
        if ('competence_apprise' === $champ && \is_int($valeur)) {
            return $this->libelle(Competence::class, $valeur) ?? '#' . $valeur;
        }

        return match (true) {
            true === $valeur => 'Oui',
            false === $valeur || null === $valeur => '-',
            \is_scalar($valeur) => (string) $valeur,
            default => '-',
        };
    }

    /**
     * @param class-string $classe
     */
    private function libelle(string $classe, int $id): ?string
    {
        $entite = $this->entityManager->find($classe, $id);

        return null !== $entite && method_exists($entite, 'getLabel') ? (string) $entite->getLabel() : null;
    }

    /**
     * @return class-string
     */
    private static function classe(string $champ): string
    {
        /** @var class-string $classe */
        $classe = self::LISTES[$champ];

        return $classe;
    }

    /**
     * @param array<array-key, mixed> $donnees
     *
     * @return array<string, mixed>
     */
    private static function partie(array $donnees, string $cle): array
    {
        $partie = $donnees[$cle] ?? [];

        /** @var array<string, mixed> $partie */
        $partie = \is_array($partie) ? $partie : [];

        return $partie;
    }

    /**
     * @return list<int>
     */
    private static function ids(mixed $valeur): array
    {
        $ids = [];
        foreach (\is_array($valeur) ? $valeur : [] as $element) {
            if (\is_int($element) || \is_string($element) && ctype_digit($element)) {
                $ids[] = (int) $element;
            }
        }

        return $ids;
    }
}
