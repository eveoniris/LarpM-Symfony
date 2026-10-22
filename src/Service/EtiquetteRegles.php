<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\EtiquetteActionObjet;
use App\Enum\EtiquetteCouleur;
use App\Enum\EtiquetteDestination;
use App\Enum\EtiquetteSituation;
use InvalidArgumentException;

/**
 * Règles des étiquettes d'objets et d'armes, lues sur la qualité inscrite en haut à droite (trois chiffres).
 *
 * Le chiffre des centaines donne la facture : 0 à 5 usuelle, 6 et 7 exceptionnelle, 8 et 9 chef-d'oeuvre.
 * Les deux derniers chiffres donnent la nature : 01 à 10 destinée particulière (non enchanté), 11 à 20 magie,
 * 21 à 57 prêtrise (un culte, 57 : chaudrons), 61 à 99 rien de spécial.
 *
 * Une étiquette est "spéciale" (à conserver) quand elle est exceptionnelle, chef-d'oeuvre, ou liée à une nature de 01 à 57.
 * Sinon elle est générique et retourne au stock. Spéciale aussi quand la nature est hors de la table : on préfère
 * conserver une étiquette de trop que perdre une étiquette spéciale.
 */
// @mago-expect lint:cyclomatic-complexity
final class EtiquetteRegles
{
    private const string CONSEIL = 'solliciter un orga expert et/ou le consigner dans les remarques.';

    /**
     * @throws InvalidArgumentException si la qualité n'a pas exactement trois chiffres
     */
    public function evaluer(EtiquetteCouleur $couleur, ?string $qualite, EtiquetteSituation $situation): EtiquetteDecision
    {
        $analyse = $this->analyser($qualite);

        return EtiquetteCouleur::BLEUE === $couleur ? $this->decisionBleue($situation, $analyse) : $this->decisionOrange($situation, $analyse);
    }

    /**
     * Bannière : chaque groupe en reçoit une, renouvelée à chaque GN. On la laisse dans l'enveloppe sans rien prévoir pour
     * l'opus suivant, mais on consigne le nombre : plusieurs bannières signalent un vol.
     */
    public function banniere(int $quantite): EtiquetteDecision
    {
        $anomalies = $quantite > 1 ? [\sprintf('Plusieurs bannières (%d) : un vol est probable, à signaler au scénariste.', $quantite)] : [];

        return new EtiquetteDecision(EtiquetteDestination::ENVELOPPE, null, false, new EtiquetteQualite(false, null, null, $anomalies));
    }

    /**
     * Sans qualité (étiquettes d'armes comptées en nombre) : générique.
     */
    private function analyser(?string $qualite): EtiquetteQualite
    {
        if (null === $qualite || '' === $qualite) {
            return new EtiquetteQualite(false, null, null, []);
        }

        if (1 !== preg_match('/^(\d)(\d{2})$/', $qualite, $correspondances)) {
            throw new InvalidArgumentException('La qualité d\'une étiquette a exactement trois chiffres.');
        }

        $centaine = (int) $correspondances[1];
        $code = (int) $correspondances[2];
        $horsTable = 0 === $code || $code > 57 && $code < 61;
        $anomalies = [];

        if ($horsTable) {
            $anomalies[] = \sprintf('La nature %02d n\'existe pas dans la table des étiquettes : %s', $code, self::CONSEIL);
        }

        if ($centaine < 6 && $code >= 11 && $code <= 57) {
            $anomalies[] = \sprintf('Un objet enchanté doit être au moins de facture exceptionnelle (qualité à partir de 600) : %s', self::CONSEIL);
        }

        return new EtiquetteQualite($centaine >= 6 || $horsTable || $code <= 57, $this->facture($centaine), $this->nature($code), $anomalies);
    }

    private function decisionOrange(EtiquetteSituation $situation, EtiquetteQualite $analyse): EtiquetteDecision
    {
        $destination = $analyse->speciale ? EtiquetteDestination::ENVELOPPE : EtiquetteDestination::STOCK;

        if (EtiquetteSituation::SANS_OBJET === $situation) {
            return new EtiquetteDecision($destination, null, $analyse->speciale, $analyse);
        }

        $anomalie = 'Un objet à étiquette orange appartient au joueur et devrait rester avec lui : il est remis au PC joueur comme objet trouvé.';

        return new EtiquetteDecision($destination, EtiquetteActionObjet::OBJET_TROUVE, $analyse->speciale, $analyse, [$anomalie]);
    }

    private function decisionBleue(EtiquetteSituation $situation, EtiquetteQualite $analyse): EtiquetteDecision
    {
        if (EtiquetteSituation::SANS_OBJET === $situation) {
            $anomalie = 'Étiquette bleue sans son objet : l\'objet est manquant, ce n\'est pas normal. Consigné comme étant au joueur, à rapporter au PC joueur.';

            return new EtiquetteDecision(EtiquetteDestination::AU_JOUEUR, null, false, $analyse, [$anomalie]);
        }

        $action = EtiquetteSituation::OBJET_REMIS_AU_PC === $situation ? EtiquetteActionObjet::DEJA_AU_PC : EtiquetteActionObjet::REMETTRE_AU_PC;

        return new EtiquetteDecision(EtiquetteDestination::AVEC_OBJET_AU_PC, $action, true, $analyse);
    }

    private function facture(int $centaine): string
    {
        return match (true) {
            $centaine >= 8 => 'chef-d\'oeuvre',
            $centaine >= 6 => 'exceptionnelle',
            default => 'usuelle',
        };
    }

    private function nature(int $code): string
    {
        return match (true) {
            $code >= 61 => 'rien de spécial',
            $code === 57 => 'lié aux chaudrons',
            $code >= 21 && $code <= 56 => 'enchanté par la prêtrise (culte)',
            $code >= 11 && $code <= 20 => 'enchanté par la magie',
            $code >= 1 && $code <= 10 => 'objet à la destinée particulière (non enchanté)',
            default => 'hors table',
        };
    }
}
