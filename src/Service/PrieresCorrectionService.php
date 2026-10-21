<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AttributeType;
use App\Entity\Personnage;
use App\Entity\Priere;
use App\Entity\Religion;
use App\Entity\Sphere;

/**
 * Compare les prières d'un personnage au quota que donnent ses compétences (attributs « Prière 1 à 4 »)
 * et propose les retraits et ajouts qui le ramènent au quota. Ne modifie rien : l'application est faite par l'appelant.
 */
class PrieresCorrectionService
{
    public const string MOTIF_HORS_RELIGION = 'sphère hors religion Fervent/Fanatique';
    public const string MOTIF_ORPHELINE = 'aucune compétence Prêtrise pour ce niveau';
    public const string MOTIF_MANQUANTE = 'prière manquante de la religion Fervent/Fanatique';
    public const string MOTIF_AMBIGU = 'excédent sans prière identifiable à retirer';

    private const int NIVEAU_FERVENT = 2;

    /**
     * @return list<array{action: 'retirer'|'ajouter'|'ambigu', niveau: int, priere: ?Priere, motif: string}>
     */
    public function analyser(Personnage $personnage, bool $avecOrphelines = true): array
    {
        $spheres = $this->spheresFerventes($personnage);
        $religion = $this->religionPrincipaleFervente($personnage);
        $actions = [];

        for ($niveau = 1; $niveau <= 4; ++$niveau) {
            $attendu = $this->attendu($personnage, $niveau);
            $possedees = $this->possedees($personnage, $niveau);
            $ecart = \count($possedees) - $attendu;

            if ($ecart < 0 && null !== $religion) {
                $actions = [...$actions, ...$this->manquantes($niveau, $possedees, -$ecart, $religion)];
            }

            if ($ecart > 0 && (0 !== $attendu || $avecOrphelines)) {
                $candidates = 0 === $attendu ? $possedees : $this->horsReligion($possedees, $spheres);
                $motif = 0 === $attendu ? self::MOTIF_ORPHELINE : self::MOTIF_HORS_RELIGION;
                $actions = [...$actions, ...$this->retraits($niveau, $candidates, $ecart, $motif)];
            }
        }

        return $actions;
    }

    /**
     * @param list<Priere> $possedees
     * @param list<Sphere> $spheres sphères des religions Fervent/Fanatique
     * @return list<Priere>
     */
    private function horsReligion(array $possedees, array $spheres): array
    {
        return array_values(array_filter($possedees, static fn (Priere $priere): bool => !\in_array($priere->getSphere(), $spheres, strict: true)));
    }

    /**
     * @param list<Priere> $candidates
     * @return list<array{action: 'retirer'|'ambigu', niveau: int, priere: ?Priere, motif: string}>
     */
    private function retraits(int $niveau, array $candidates, int $ecart, string $motif): array
    {
        $actions = [];
        foreach (\array_slice($candidates, offset: 0, length: $ecart) as $priere) {
            $actions[] = ['action' => 'retirer', 'niveau' => $niveau, 'priere' => $priere, 'motif' => $motif];
        }

        if (\count($candidates) < $ecart) {
            $actions[] = ['action' => 'ambigu', 'niveau' => $niveau, 'priere' => null, 'motif' => self::MOTIF_AMBIGU];
        }

        return $actions;
    }

    /**
     * @param list<Priere> $possedees
     * @return list<array{action: 'ajouter', niveau: int, priere: Priere, motif: string}>
     */
    private function manquantes(int $niveau, array $possedees, int $manque, Religion $religion): array
    {
        $actions = [];
        foreach ($religion->getSpheres() as $sphere) {
            foreach ($sphere->getPrieres() as $priere) {
                if (\count($actions) === $manque) {
                    return $actions;
                }
                if ($niveau === $priere->getNiveau() && !\in_array($priere, $possedees, strict: true)) {
                    $actions[] = ['action' => 'ajouter', 'niveau' => $niveau, 'priere' => $priere, 'motif' => self::MOTIF_MANQUANTE];
                }
            }
        }

        return $actions;
    }

    private function attendu(Personnage $personnage, int $niveau): int
    {
        $total = 0;
        foreach ($personnage->getCompetences() as $competence) {
            $valeur = $competence->getAttributeValue(AttributeType::$PRIERES[$niveau - 1]);
            if (null !== $valeur) {
                $total += (int) $valeur;
            }
        }

        return $total;
    }

    /** @return list<Priere> */
    private function possedees(Personnage $personnage, int $niveau): array
    {
        $prieres = [];
        foreach ($personnage->getPrieres() as $priere) {
            if ($niveau === $priere->getNiveau()) {
                $prieres[] = $priere;
            }
        }

        return $prieres;
    }

    /** @return list<Sphere> */
    private function spheresFerventes(Personnage $personnage): array
    {
        $spheres = [];
        foreach ($personnage->getPersonnagesReligions() as $personnageReligion) {
            if (self::NIVEAU_FERVENT > ($personnageReligion->getReligionLevel()?->getIndex() ?? 0)) {
                continue;
            }
            foreach ($personnageReligion->getReligion()->getSpheres() as $sphere) {
                $spheres[] = $sphere;
            }
        }

        return $spheres;
    }

    /** Religion principale du personnage, seulement si elle est Fervent ou Fanatique (même règle que l'attribution automatique). */
    private function religionPrincipaleFervente(Personnage $personnage): ?Religion
    {
        $religion = $personnage->getMainReligion();
        if (null === $religion) {
            return null;
        }

        foreach ($personnage->getPersonnagesReligions() as $personnageReligion) {
            if ($personnageReligion->getReligion() === $religion && self::NIVEAU_FERVENT <= ($personnageReligion->getReligionLevel()?->getIndex() ?? 0)) {
                return $religion;
            }
        }

        return null;
    }
}
