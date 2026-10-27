<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\Personnage;
use App\Enum\CompetenceFamilyType;

/**
 * Pièces d'une enveloppe de joueur qui concernent le profil du personnage : un non-magicien n'a pas d'enveloppe de corruption, un non
 * alchimiste ni herboriste n'a pas de carte, un non-prêtre n'a pas de fiche de prière.
 *
 * Les pièces non attendues ne sont pas supprimées : le dépouilleur peut toujours les cocher (elles restent dans une section repliée),
 * car un élément inattendu dans l'enveloppe doit pouvoir être signalé. Sans personnage connu, tout est attendu.
 *
 * Le tableau « pièce => profil » est dans les constantes pour être ajusté en un seul endroit.
 */
readonly class PiecesAttendues
{
    /** Pièces demandées pour tous les personnages. */
    public const array POUR_TOUS = ['fichePersonnage', 'ficheRetour', 'pincesALinge', 'documentsNumerotes'];

    /** Familles de compétence qui font d'un personnage un magicien (la connaissance d'un sort le fait aussi). */
    public const array FAMILLES_MAGIE = [CompetenceFamilyType::MAGIC];

    /** Familles de compétence qui font d'un personnage un alchimiste ou un herboriste. */
    public const array FAMILLES_ALCHIMIE = [CompetenceFamilyType::ALCHEMY, CompetenceFamilyType::HERBALISM];

    /** Familles de compétence qui font d'un personnage un prêtre (la connaissance d'une prière le fait aussi). */
    public const array FAMILLES_PRETRISE = [CompetenceFamilyType::PRIESTHOOD];

    public function __construct(
        private DepouillementEnveloppeResolver $resolver,
    ) {
    }

    /**
     * @return array{pieces: array<string, bool>, profil: string} pour chaque pièce, si le profil du personnage l'attend, et le profil en toutes lettres
     */
    public function pour(Depouillement $depouillement): array
    {
        $participant = $depouillement->getParticipant();
        $personnage = null === $participant ? null : $this->resolver->personnageDuNumero($participant, (int) $depouillement->getNumero());

        return $this->pourPersonnage($personnage);
    }

    /**
     * @return array{pieces: array<string, bool>, profil: string}
     */
    public function pourPersonnage(?Personnage $personnage): array
    {
        if (null === $personnage) {
            return ['pieces' => $this->pieces(true, true, true), 'profil' => 'personnage inconnu : toutes les pièces sont proposées'];
        }

        $familles = [];
        foreach ($personnage->getCompetences() as $competence) {
            $type = $competence->getCompetenceFamily()?->getCompetenceFamilyType();
            if (null !== $type) {
                $familles[$type->value] = true;
            }
        }

        $magie = $this->connait($familles, self::FAMILLES_MAGIE) || !$personnage->getSorts()->isEmpty();
        $alchimie = $this->connait($familles, self::FAMILLES_ALCHIMIE);
        $pretre = $this->connait($familles, self::FAMILLES_PRETRISE) || !$personnage->getPrieres()->isEmpty();

        $profil = array_filter([$magie ? 'magie' : null, $alchimie ? 'alchimie ou herboristerie' : null, $pretre ? 'prêtrise' : null]);

        return [
            'pieces' => $this->pieces($magie, $alchimie, $pretre),
            'profil' => [] === $profil ? 'ni magie, ni alchimie, ni herboristerie, ni prêtrise' : implode(', ', $profil),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function pieces(bool $magie, bool $alchimie, bool $pretre): array
    {
        return [
            'fichePersonnage' => true,
            'ficheRetour' => true,
            'pincesALinge' => true,
            'documentsNumerotes' => true,
            'ficheSort' => $magie,
            'corruptionOuvertes' => $magie,
            'corruptionNonOuvertes' => $magie,
            'copiesRecettesSorts' => $magie || $alchimie,
            'carteAlchimiste' => $alchimie,
            'fichePriere' => $pretre,
        ];
    }

    /**
     * @param array<string, true>        $familles familles du personnage, indexées par valeur de CompetenceFamilyType
     * @param list<CompetenceFamilyType> $cherchees
     */
    private function connait(array $familles, array $cherchees): bool
    {
        foreach ($cherchees as $type) {
            if (\array_key_exists($type->value, $familles)) {
                return true;
            }
        }

        return false;
    }
}
