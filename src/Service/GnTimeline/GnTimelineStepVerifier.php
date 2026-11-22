<?php

declare(strict_types=1);

namespace App\Service\GnTimeline;

use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineType;

/**
 * Règles de cohérence d'une étape de chronologie : date, paramètres propres au type, exécution automatique.
 *
 * Normalise aussi l'étape (date ignorée pour un verrouillage, valeurs par défaut du vieillissement).
 */
final class GnTimelineStepVerifier
{
    /**
     * @return array<string, string> message d'erreur par champ du formulaire (vide si l'étape est valide)
     */
    public function verifier(GnTimelineStep $etape): array
    {
        $erreurs = [];
        $type = $etape->getType();

        if (GnTimelineType::VERROUILLAGE === $type) {
            // la date est celle du GN, jamais une saisie propre
            $etape->setDatePrevue(null);
        } elseif (null === $etape->getDatePrevue()) {
            $erreurs['datePrevue'] = 'Indiquez la date prévue.';
        }

        if (GnTimelineType::VIEILLISSEMENT === $type) {
            $etape->setAnnees($etape->getAnnees() ?? GnTimelineStep::ANNEES_DEFAUT)->setAgeMort($etape->getAgeMort() ?? GnTimelineStep::AGE_MORT_DEFAUT);
            if ($etape->getAnnees() < 1 || $etape->getAgeMort() < 1) {
                $erreurs['annees'] = "Les années et l'âge doivent être supérieurs à 0.";
            }
        }

        if (GnTimelineType::XP_PARTICIPATION === $type && ($etape->getXp() ?? 0) <= 0) {
            $erreurs['xp'] = "Indiquez l'XP à donner (supérieur à 0).";
        }

        if ($etape->isAuto() && !$type->autoAutorise()) {
            $erreurs['auto'] = "Ce type d'étape ne peut pas être exécuté automatiquement.";
        }

        return $erreurs;
    }
}
