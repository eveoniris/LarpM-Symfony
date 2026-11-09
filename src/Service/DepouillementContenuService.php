<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Entity\User;
use App\Enum\ActionStatut;
use App\Enum\DepouillementType;
use App\Exception\ContenuInvalideException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Contenu constaté dans une enveloppe de joueur : pièces jointes, apprentissages, six actions de fin d'Opus et surplus.
 *
 * Tant que le dépouillement n'est pas validé, tout est gardé dans `donnees['contenu_joueur']` (brouillon de suivi) : les listes
 * y sont des identifiants. Rien n'est appliqué à la fiche du joueur avant la validation (étape 12).
 */
// @mago-expect lint:cyclomatic-complexity
readonly class DepouillementContenuService
{
    private const string CLE = 'contenu_joueur';

    /** Préfixe des lignes de remarques ajoutées automatiquement pour ce que le dépouilleur n'a pas trouvé dans les listes. */
    private const string PREFIXE_INTROUVABLE = '[Introuvable] ';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActionsFinOpusRegles $regles,
        private ContenuJoueurSerialiseur $serialiseur,
        private ControleApprentissage $controleApprentissage,
    ) {
    }

    /**
     * @throws ContenuInvalideException si le dépouillement n'est pas une enveloppe de joueur ou n'est plus modifiable
     */
    public function enregistrerContenuJoueur(Depouillement $depouillement, ContenuJoueur $contenu, User $utilisateur, bool $parJoueur = false): void
    {
        if (DepouillementType::JOUEUR !== $depouillement->getType()) {
            throw new ContenuInvalideException('Ce contenu ne concerne que les enveloppes de joueur.');
        }

        if (!$depouillement->getStatut()->isEditable()) {
            throw new ContenuInvalideException(Depouillement::MESSAGE_NON_MODIFIABLE);
        }

        $contenu->surplus = CompteursEnveloppe::normaliser($contenu->surplus);
        $avant = $depouillement->getDonnees()[self::CLE] ?? null;
        $apres = $this->serialiseur->versTableau($contenu, $this->statuts($contenu));

        $decisions = DecisionsDeclaration::suivre(
            DecisionsDeclaration::depuis($depouillement->getDonnees()),
            $this->valeursSuivies(\is_array($avant) ? $avant : []),
            $this->valeursSuivies($apres),
            new AuteurDeclaration($parJoueur, (int) $utilisateur->getId(), new DateTimeImmutable()),
        );

        $depouillement->setDonnees([...$depouillement->getDonnees(), self::CLE => $apres, 'decisions' => $decisions]);
        $this->majRemarquesIntrouvables($depouillement, $contenu->introuvables);
        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($utilisateur)
                ->setActionType(DepouillementHistory::ACTION_MISE_A_JOUR)
                ->setDataBefore(null === $avant ? null : [self::CLE => $avant])
                ->setDataAfter([self::CLE => $apres])
                ->setMotif('Contenu de l\'enveloppe de joueur enregistré'),
        );
        $depouillement->touch();
        $this->entityManager->flush();
    }

    /**
     * Contenu enregistré (ou vide), avec les entités retrouvées à partir des identifiants gardés.
     */
    public function lireContenuJoueur(Depouillement $depouillement): ContenuJoueur
    {
        /** @var array<string, mixed> $donnees */
        $donnees = $depouillement->getDonnees()[self::CLE] ?? [];

        return $this->serialiseur->depuisTableau($donnees);
    }

    /**
     * @return array<string, ActionFinOpusResultat>
     */
    public function evaluerActions(ContenuJoueur $contenu): array
    {
        return $this->regles->evaluer($contenu);
    }

    /**
     * Anomalies de toutes les actions, plus ce que le dépouilleur n'a pas trouvé : tout ce qui demande un orga expert.
     *
     * @return list<string>
     */
    public function anomalies(ContenuJoueur $contenu, ?Depouillement $depouillement = null): array
    {
        $anomalies = [];
        foreach ($this->regles->evaluer($contenu) as $resultat) {
            $anomalies = [...$anomalies, ...$resultat->anomalies];
        }

        // L'enseignant d'une compétence apprise est contrôlé dès la saisie, pas seulement à la validation.
        $apprentissage = null === $depouillement ? null : $this->controleApprentissage->avertissement($depouillement, $contenu);
        if (null !== $apprentissage) {
            $anomalies[] = $apprentissage;
        }

        if (null !== $contenu->introuvables && '' !== trim($contenu->introuvables)) {
            $anomalies[] = 'Des éléments sont introuvables dans les listes : solliciter un orga (noté dans les remarques).';
        }

        return $anomalies;
    }

    public function compterAcceptees(ContenuJoueur $contenu): int
    {
        return \count(array_filter($this->regles->evaluer($contenu), static fn (ActionFinOpusResultat $resultat): bool => ActionStatut::ACCEPTEE === $resultat->statut));
    }

    /**
     * Ce que le joueur peut déclarer dans le contenu (apprentissages et mort) : ce sont ces valeurs dont on suit la provenance.
     *
     * @param array<array-key, mixed> $contenu
     *
     * @return array<string, mixed>
     */
    private function valeursSuivies(array $contenu): array
    {
        return [
            'sorts' => $contenu['sorts'] ?? [],
            'prieres' => $contenu['prieres'] ?? [],
            'potions' => $contenu['potions'] ?? [],
            'langues' => $contenu['langues'] ?? [],
            'connaissances' => $contenu['connaissances'] ?? [],
            'technologies' => $contenu['technologies'] ?? [],
            'enseignant_technologie_numero' => $contenu['enseignant_technologie_numero'] ?? null,
            'competence_apprise' => $contenu['competence_apprise'] ?? null,
            'enseignant_numero' => $contenu['enseignant_numero'] ?? null,
            'personnage_mort' => $contenu['personnage_mort'] ?? false,
            'circonstances_mort' => $contenu['circonstances_mort'] ?? null,
        ];
    }

    /**
     * Statut de chaque action, pour le brouillon et l'historique.
     *
     * @return array<string, string> action => statut
     */
    private function statuts(ContenuJoueur $contenu): array
    {
        return array_map(static fn (ActionFinOpusResultat $resultat): string => $resultat->statut->value, $this->regles->evaluer($contenu));
    }

    /**
     * Les lignes "[Introuvable]" sont réécrites à chaque enregistrement pour ne jamais se dupliquer.
     */
    private function majRemarquesIntrouvables(Depouillement $depouillement, ?string $introuvables): void
    {
        $lignes = array_filter(explode("\n", (string) $depouillement->getRemarques()), static fn (string $ligne): bool => !str_starts_with($ligne, self::PREFIXE_INTROUVABLE));

        if (null !== $introuvables && '' !== trim($introuvables)) {
            $lignes[] = self::PREFIXE_INTROUVABLE . trim(str_replace(["\r", "\n"], replace: ' ', subject: $introuvables));
        }

        $texte = trim(implode("\n", $lignes));
        $depouillement->setRemarques('' === $texte ? null : $texte);
    }
}
