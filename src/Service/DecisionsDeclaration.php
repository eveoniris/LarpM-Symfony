<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Provenance et décision de chaque élément déclaré dans un dossier de retour de jeu.
 *
 * Forme enregistrée dans `donnees['decisions']` : `champ => element => {source, etat, par, le}`. Un champ liste (sorts, prières, potions...)
 * a une décision par élément (l'identifiant de l'entité), un champ simple (mort, renommée...) une seule, sous la clé `_`.
 * Un élément sans entrée vient du dépouillement et est confirmé (anciens dossiers, simulations).
 *
 * Ce que le joueur ajoute est « en attente » jusqu'à la décision d'un responsable ; ce que le staff saisit est confirmé d'office.
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
final class DecisionsDeclaration
{
    public const string SOURCE_JOUEUR = 'joueur';

    public const string SOURCE_DEPOUILLEUR = 'depouilleur';

    public const string ETAT_EN_ATTENTE = 'en_attente';

    public const string ETAT_CONFIRME = 'confirme';

    public const string ELEMENT_UNIQUE = '_';

    /**
     * Les décisions gardées dans les données d'un dossier (vides pour un dossier qui n'en a pas encore).
     *
     * @param array<array-key, mixed> $donnees données du dossier
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    public static function depuis(array $donnees): array
    {
        /** @var array<string, array<string, array<string, mixed>>> $decisions */
        $decisions = $donnees['decisions'] ?? [];

        return $decisions;
    }

    /**
     * Met à jour les décisions d'après ce qui a changé : un élément ajouté ou modifié reçoit la source de l'auteur, un élément
     * retiré perd sa décision.
     *
     * @param array<string, array<string, array<string, mixed>>> $decisions
     * @param array<array-key, mixed>                            $avant     valeurs suivies avant l'enregistrement (liste d'identifiants ou valeur simple)
     * @param array<array-key, mixed>                            $apres     valeurs suivies après l'enregistrement
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    public static function suivre(array $decisions, array $avant, array $apres, AuteurDeclaration $auteur): array
    {
        foreach (array_unique([...array_keys($avant), ...array_keys($apres)]) as $cle) {
            $champ = (string) $cle;
            $ancien = $avant[$champ] ?? null;
            $nouveau = $apres[$champ] ?? null;

            if (\is_array($ancien) || \is_array($nouveau)) {
                $decisions = self::suivreListe($decisions, $champ, self::liste($ancien), self::liste($nouveau), $auteur);

                continue;
            }

            $ancien = self::normaliser($ancien);
            $nouveau = self::normaliser($nouveau);
            if ($ancien === $nouveau) {
                continue;
            }

            $decisions = null === $nouveau
                ? self::retirer($decisions, $champ, self::ELEMENT_UNIQUE)
                : self::poser($decisions, $champ, self::ELEMENT_UNIQUE, $auteur);
        }

        return $decisions;
    }

    /**
     * Décision d'un élément, avec la valeur par défaut d'un élément venu du dépouillement.
     *
     * @param array<string, array<string, array<string, mixed>>> $decisions
     *
     * @return array{source: string, etat: string}
     */
    public static function lire(array $decisions, string $champ, int|string $element = self::ELEMENT_UNIQUE): array
    {
        $entree = $decisions[$champ][(string) $element] ?? [];

        return [
            'source' => (string) ($entree['source'] ?? self::SOURCE_DEPOUILLEUR),
            'etat' => (string) ($entree['etat'] ?? self::ETAT_CONFIRME),
        ];
    }

    /**
     * Par champ : combien d'éléments le joueur a déclarés, et combien attendent une décision.
     *
     * @param array<string, array<string, array<string, mixed>>> $decisions
     *
     * @return array<string, array{joueur: int, en_attente: int}>
     */
    public static function resume(array $decisions): array
    {
        $resume = [];
        foreach ($decisions as $champ => $elements) {
            $joueur = 0;
            $attente = 0;
            foreach (array_keys($elements) as $element) {
                $decision = self::lire($decisions, $champ, $element);
                $joueur += self::SOURCE_JOUEUR === $decision['source'] ? 1 : 0;
                $attente += self::ETAT_EN_ATTENTE === $decision['etat'] ? 1 : 0;
            }
            $resume[$champ] = ['joueur' => $joueur, 'en_attente' => $attente];
        }

        return $resume;
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $decisions
     * @param list<int|string>                                   $ancien
     * @param list<int|string>                                   $nouveau
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private static function suivreListe(array $decisions, string $champ, array $ancien, array $nouveau, AuteurDeclaration $auteur): array
    {
        foreach (array_diff($nouveau, $ancien) as $ajoute) {
            $decisions = self::poser($decisions, $champ, (string) $ajoute, $auteur);
        }

        foreach (array_diff($ancien, $nouveau) as $retire) {
            $decisions = self::retirer($decisions, $champ, (string) $retire);
        }

        return $decisions;
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $decisions
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private static function poser(array $decisions, string $champ, string $element, AuteurDeclaration $auteur): array
    {
        $decisions[$champ][$element] = [
            'source' => $auteur->parJoueur ? self::SOURCE_JOUEUR : self::SOURCE_DEPOUILLEUR,
            'etat' => $auteur->parJoueur ? self::ETAT_EN_ATTENTE : self::ETAT_CONFIRME,
            'par' => $auteur->utilisateur,
            'le' => $auteur->le->format(DATE_ATOM),
        ];

        return $decisions;
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $decisions
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private static function retirer(array $decisions, string $champ, string $element): array
    {
        unset($decisions[$champ][$element]);
        if (isset($decisions[$champ]) && [] === $decisions[$champ]) {
            unset($decisions[$champ]);
        }

        return $decisions;
    }

    /**
     * @return list<int|string>
     */
    private static function liste(mixed $valeur): array
    {
        $elements = [];
        foreach (\is_array($valeur) ? $valeur : [] as $element) {
            if (\is_int($element) || \is_string($element)) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    /**
     * Une valeur simple « vide » (rien, texte blanc, faux, zéro) équivaut à une absence : null.
     */
    private static function normaliser(mixed $valeur): mixed
    {
        if (\is_string($valeur)) {
            $valeur = trim($valeur);
        }

        if (null === $valeur || '' === $valeur || false === $valeur || 0 === $valeur || !\is_scalar($valeur)) {
            return null;
        }

        return \is_bool($valeur) ? true : $valeur;
    }
}
