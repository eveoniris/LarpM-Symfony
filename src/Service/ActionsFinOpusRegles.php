<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\ActionFinOpus;
use App\Enum\ActionStatut;

/**
 * Contrôle des six actions de fin d'Opus d'après le contenu réel de l'enveloppe.
 *
 * Une action cochée dont les ressources exigées sont jointes est acceptée. Si elles manquent elle est refusée : sans effet, et
 * ce qu'elle devait préserver n'est pas préservé (cela va dans le reliquat de l'enveloppe de groupe).
 */
// @mago-expect lint:kan-defect
final class ActionsFinOpusRegles
{
    /**
     * Ressources et pièces trouvées moins ce que les actions acceptées consomment : c'est ce qui est reversé au groupe.
     *
     * @param array<string, int>    $trouve  compteurs de l'enveloppe (CompteursEnveloppe)
     * @param array<string, string> $statuts statut de chaque action (valeur de ActionFinOpus => valeur de ActionStatut)
     *
     * @return array<string, int>
     */
    public static function surplusNet(array $trouve, array $statuts): array
    {
        foreach ($statuts as $valeur => $statut) {
            $action = ActionFinOpus::tryFrom($valeur);
            if (null === $action || ActionStatut::ACCEPTEE->value !== $statut) {
                continue;
            }

            $trouve[$action->compteur()] = max(0, ($trouve[$action->compteur()] ?? 0) - $action->quantiteRessource());
        }

        return $trouve;
    }

    /**
     * @return array<string, ActionFinOpusResultat> indexé par la valeur de ActionFinOpus
     */
    public function evaluer(ContenuJoueur $contenu): array
    {
        $resultats = [];
        foreach (ActionFinOpus::cases() as $action) {
            $resultats[$action->value] = $this->evaluerAction($action, $contenu);
        }

        return $resultats;
    }

    private function evaluerAction(ActionFinOpus $action, ContenuJoueur $contenu): ActionFinOpusResultat
    {
        // Nombre de pièces, ingrédients, ressources ou potions que le joueur annonce conserver.
        $elements = $this->elements($contenu, $action);

        if (!$this->utilisee($contenu, $action)) {
            $anomalies = $elements > 0 ? [\sprintf('%s : des éléments sont listés alors que l\'action n\'est pas cochée, ils sont ignorés.', $action->ressource())] : [];

            return new ActionFinOpusResultat($action, ActionStatut::NON_UTILISEE, 0, $anomalies);
        }

        if (!$this->ressourcesJointes($contenu, $action)) {
            $manque = \sprintf('%s : les %d ressource(s) exigée(s) ne sont pas dans l\'enveloppe, l\'action est refusée et sans effet.', $action->ressource(), $action->quantiteRessource());
            $reliquat = null === $action->plafond() ? [] : ['Ce que le joueur voulait préserver n\'est pas préservé : à placer dans le reliquat de l\'enveloppe de groupe.'];

            return new ActionFinOpusResultat($action, ActionStatut::REFUSEE, 0, [$manque, ...$reliquat]);
        }

        return new ActionFinOpusResultat($action, ActionStatut::ACCEPTEE, $elements, $this->anomaliesElements($action, $elements));
    }

    /**
     * @return list<string>
     */
    private function anomaliesElements(ActionFinOpus $action, int $elements): array
    {
        $plafond = $action->plafond();
        if (null === $plafond) {
            return [];
        }

        if ($elements > $plafond) {
            return [\sprintf('%s : %d éléments annoncés, le maximum est %d.', $action->ressource(), $elements, $plafond)];
        }

        return 0 === $elements ? [\sprintf('%s : action acceptée mais aucun élément à conserver n\'est indiqué.', $action->ressource())] : [];
    }

    private function utilisee(ContenuJoueur $contenu, ActionFinOpus $action): bool
    {
        return match ($action) {
            ActionFinOpus::FRUITS_LEGUMES => $contenu->fruitsUtilise,
            ActionFinOpus::SOIE => $contenu->soieUtilise,
            ActionFinOpus::GEMME => $contenu->gemmeUtilise,
            ActionFinOpus::SEL => $contenu->selUtilise,
            ActionFinOpus::FOURRURE => $contenu->fourrureUtilise,
            ActionFinOpus::IVOIRE => $contenu->ivoireUtilise,
        };
    }

    private function ressourcesJointes(ContenuJoueur $contenu, ActionFinOpus $action): bool
    {
        return match ($action) {
            ActionFinOpus::FRUITS_LEGUMES => $contenu->fruitsJointes,
            ActionFinOpus::SOIE => $contenu->soieJointes,
            ActionFinOpus::GEMME => $contenu->gemmeJointes,
            ActionFinOpus::SEL => $contenu->selJointes,
            ActionFinOpus::FOURRURE => $contenu->fourrureJointes,
            ActionFinOpus::IVOIRE => $contenu->ivoireJointes,
        };
    }

    private function elements(ContenuJoueur $contenu, ActionFinOpus $action): int
    {
        return match ($action) {
            ActionFinOpus::GEMME => $contenu->gemmePieces,
            ActionFinOpus::SEL => \count($contenu->selIngredients),
            ActionFinOpus::FOURRURE => \count($contenu->fourrureRessources),
            ActionFinOpus::IVOIRE => \count($contenu->ivoirePotions),
            default => 0,
        };
    }
}
