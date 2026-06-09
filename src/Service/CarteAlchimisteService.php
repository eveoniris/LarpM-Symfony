<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Gn;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Enum\CompetenceFamilyType;
use Picqer\Barcode\BarcodeGeneratorSVG;

final class CarteAlchimisteService
{
    /**
     * Génère un code EAN-13 pour la carte alchimiste/herboriste.
     *
     * Format : [XXX][2][AAAAA][BBB][C]
     *   XXX   = id du GN (3 chiffres, padded 0 à gauche)
     *   2     = type joueur (fixe)
     *   AAAAA = id joueur (5 chiffres, padded 0 à gauche)
     *   BBB   = numéro de carte joueur (3 chiffres, padded 0 à gauche)
     *   C     = chiffre de contrôle EAN-13
     */
    public function generateEan(int $gnId, int $joueurId, int $numeroCarte): string
    {
        $payload = sprintf('%03d2%05d%03d', $gnId, $joueurId, $numeroCarte);
        $checksum = $this->ean13Checksum($payload);

        return $payload . $checksum;
    }

    /**
     * Génère un SVG du code barre EAN-13 (format carte standard).
     */
    public function generateBarcodeSvg(string $ean13): string
    {
        $generator = new BarcodeGeneratorSVG();

        return $generator->getBarcode($ean13, BarcodeGeneratorSVG::TYPE_EAN_13, 2, 60);
    }

    /**
     * Génère un SVG du code barre EAN-13 compact pour étiquette 45,7×25,4 mm.
     */
    public function generateBarcodeSvgLabel(string $ean13): string
    {
        $generator = new BarcodeGeneratorSVG();

        return $generator->getBarcode($ean13, BarcodeGeneratorSVG::TYPE_EAN_13, 1, 35);
    }

    /**
     * Détermine si un personnage a la compétence alchimie ou herboristerie.
     */
    public function hasCarteAlchimisteCompetence(Personnage $personnage): bool
    {
        return $personnage->hasCompetence(CompetenceFamilyType::ALCHEMY)
            || $personnage->hasCompetence(CompetenceFamilyType::HERBALISM);
    }

    /**
     * Retourne le libellé des compétences pertinentes du personnage.
     *
     * @return list<string>
     */
    public function getCompetenceLabels(Personnage $personnage): array
    {
        $labels = [];
        if ($personnage->hasCompetence(CompetenceFamilyType::ALCHEMY)) {
            $labels[] = 'Alchimiste';
        }
        if ($personnage->hasCompetence(CompetenceFamilyType::HERBALISM)) {
            $labels[] = 'Herboriste';
        }

        return $labels;
    }

    /**
     * Retourne les données carte de tous les participants éligibles d'un GN.
     *
     * @return list<array{participant: Participant, ean: string, barcode: string, competences: list<string>}>
     */
    public function getCartesForGn(Gn $gn): array
    {
        $cartes = [];
        $numeroCarte = 1;

        foreach ($gn->getParticipants() as $participant) {
            $personnage = $participant->getPersonnage();
            if (!$personnage) {
                continue;
            }
            if (!$this->hasCarteAlchimisteCompetence($personnage)) {
                continue;
            }
            $user = $personnage->getUser();
            if (!$user) {
                continue;
            }

            $ean = $this->generateEan((int) $gn->getId(), (int) $user->getId(), $numeroCarte);
            $cartes[] = [
                'participant' => $participant,
                'ean' => $ean,
                'barcode' => $this->generateBarcodeSvgLabel($ean),
                'competences' => $this->getCompetenceLabels($personnage),
            ];
            ++$numeroCarte;
        }

        return $cartes;
    }

    private function ean13Checksum(string $twelvedigits): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; ++$i) {
            $digit = (int) $twelvedigits[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        $check = (10 - ($sum % 10)) % 10;

        return (string) $check;
    }
}
