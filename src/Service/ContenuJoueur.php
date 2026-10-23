<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Competence;
use App\Entity\Ingredient;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Ressource;
use App\Entity\Sort;
use App\Enum\ActionFinOpus;

/**
 * Ce que le dépouilleur constate dans une enveloppe de joueur (rempli directement par le formulaire).
 * Les listes de sorts, prières, ingrédients, ressources et potions sont des entités choisies par auto-complétion.
 */
// @mago-expect lint:too-many-properties
// @mago-expect lint:too-many-methods
final class ContenuJoueur
{
    // Pièces jointes à l'enveloppe.
    public bool $fichePersonnage = false;

    public bool $ficheRetour = false;

    public bool $ficheSort = false;

    public int $corruptionOuvertes = 0;

    public int $corruptionNonOuvertes = 0;

    public bool $fichePriere = false;

    public bool $carteAlchimiste = false;

    public int $pincesALinge = 0;

    /** Numéros des documents de jeu officiels numérotés (parchemins), en texte libre. */
    public ?string $documentsNumerotes = null;

    public bool $copiesRecettesSorts = false;

    // Appris pendant le jeu.
    /** @var list<Sort> */
    public array $sorts = [];

    /** @var list<Priere> */
    public array $prieres = [];

    /** @var list<Potion> potions (recettes) apprises pendant le jeu */
    public array $potions = [];

    /** Compétence apprise auprès d'un autre personnage pendant l'opus (un apprentissage au plus entre deux GN), avec son enseignant. */
    public ?Competence $competenceApprise = null;

    /** Numéro de personnage de l'enseignant de la compétence apprise. */
    public ?int $enseignantNumero = null;

    /** Ce que le dépouilleur n'a pas trouvé dans les listes : un orga sera sollicité. */
    public ?string $introuvables = null;

    // Les six actions de fin d'Opus : case "utilisée" et "ressources jointes".
    public bool $fruitsUtilise = false;

    public bool $fruitsJointes = false;

    public bool $soieUtilise = false;

    public bool $soieJointes = false;

    public bool $gemmeUtilise = false;

    public bool $gemmeJointes = false;

    public int $gemmePieces = 0;

    public bool $selUtilise = false;

    public bool $selJointes = false;

    /** @var list<Ingredient> */
    public array $selIngredients = [];

    public bool $fourrureUtilise = false;

    public bool $fourrureJointes = false;

    /** @var list<Ressource> */
    public array $fourrureRessources = [];

    public bool $ivoireUtilise = false;

    public bool $ivoireJointes = false;

    /** @var list<Potion> */
    public array $ivoirePotions = [];

    /** Surplus trouvé dans l'enveloppe, à reverser dans l'enveloppe de groupe. @var array<string, int> */
    public array $surplus = [];

    /** Le dépouilleur a enregistré les décisions sur les actions (sinon elles sont pré-sélectionnées d'après les ressources trouvées). */
    public bool $actionsDecidees = false;

    /** Le personnage est mort pendant cet opus (à vérifier et à enregistrer par un scénariste). */
    public bool $personnageMort = false;

    /** Précisions sur la mort (circonstances, lieu), facultatives. */
    public ?string $circonstancesMort = null;

    public const string DECISION_AUCUNE = 'aucune';

    public const string DECISION_VALIDEE = 'validee';

    public const string DECISION_INSUFFISANT = 'insuffisant';

    /**
     * Décision prise sur une action : aucune, validée (ressources consommées) ou souhaitée sans assez de ressources (sans effet).
     * Elle se range dans les deux cases historiques « utilisée » et « jointes », qui restent la forme enregistrée.
     */
    public function decisionAction(ActionFinOpus $action): string
    {
        $prefixe = $this->prefixe($action);

        return match (true) {
            $this->{$prefixe . 'Utilise'} && $this->{$prefixe . 'Jointes'} => self::DECISION_VALIDEE,
            $this->{$prefixe . 'Utilise'} => self::DECISION_INSUFFISANT,
            default => self::DECISION_AUCUNE,
        };
    }

    public function definirDecisionAction(ActionFinOpus $action, string $decision): void
    {
        $prefixe = $this->prefixe($action);
        $this->{$prefixe . 'Utilise'} = self::DECISION_AUCUNE !== $decision;
        $this->{$prefixe . 'Jointes'} = self::DECISION_VALIDEE === $decision;
    }

    /**
     * Propose une décision d'après les ressources trouvées : assez (validée), quelques-unes seulement (souhaitée mais insuffisante)
     * ou aucune (rien). Le dépouilleur vérifie et enregistre.
     */
    public function preselectionnerActions(): void
    {
        foreach (ActionFinOpus::cases() as $action) {
            $trouve = $this->surplus[$action->compteur()] ?? 0;
            $this->definirDecisionAction($action, match (true) {
                $trouve >= $action->quantiteRessource() => self::DECISION_VALIDEE,
                $trouve > 0 => self::DECISION_INSUFFISANT,
                default => self::DECISION_AUCUNE,
            });
        }
    }

    public function getFruitsDecision(): string
    {
        return $this->decisionAction(ActionFinOpus::FRUITS_LEGUMES);
    }

    public function setFruitsDecision(string $decision): void
    {
        $this->definirDecisionAction(ActionFinOpus::FRUITS_LEGUMES, $decision);
    }

    public function getSoieDecision(): string
    {
        return $this->decisionAction(ActionFinOpus::SOIE);
    }

    public function setSoieDecision(string $decision): void
    {
        $this->definirDecisionAction(ActionFinOpus::SOIE, $decision);
    }

    public function getGemmeDecision(): string
    {
        return $this->decisionAction(ActionFinOpus::GEMME);
    }

    public function setGemmeDecision(string $decision): void
    {
        $this->definirDecisionAction(ActionFinOpus::GEMME, $decision);
    }

    public function getSelDecision(): string
    {
        return $this->decisionAction(ActionFinOpus::SEL);
    }

    public function setSelDecision(string $decision): void
    {
        $this->definirDecisionAction(ActionFinOpus::SEL, $decision);
    }

    public function getFourrureDecision(): string
    {
        return $this->decisionAction(ActionFinOpus::FOURRURE);
    }

    public function setFourrureDecision(string $decision): void
    {
        $this->definirDecisionAction(ActionFinOpus::FOURRURE, $decision);
    }

    public function getIvoireDecision(): string
    {
        return $this->decisionAction(ActionFinOpus::IVOIRE);
    }

    public function setIvoireDecision(string $decision): void
    {
        $this->definirDecisionAction(ActionFinOpus::IVOIRE, $decision);
    }

    private function prefixe(ActionFinOpus $action): string
    {
        return match ($action) {
            ActionFinOpus::FRUITS_LEGUMES => 'fruits',
            ActionFinOpus::SOIE => 'soie',
            ActionFinOpus::GEMME => 'gemme',
            ActionFinOpus::SEL => 'sel',
            ActionFinOpus::FOURRURE => 'fourrure',
            ActionFinOpus::IVOIRE => 'ivoire',
        };
    }
}
