<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\AjoutMasseType;
use DateTime;

/** Saisie d'un ajout en masse (valeurs brutes du formulaire, validées par erreurs()). */
final readonly class AjoutMasseDemande
{
    public const int XP_MOTIF_MAX = 100;

    public function __construct(
        public AjoutMasseType $type,
        public string $valeurBrute,
        public string $motif,
        public string $dateBrute,
        public ?AjoutMasseMessage $message = null,
    ) {
    }

    public function valeur(): int
    {
        return (int) $this->valeurBrute;
    }

    public function motifPropre(): string
    {
        return trim($this->motif);
    }

    public function date(): DateTime
    {
        $date = DateTime::createFromFormat('!Y-m-d', $this->dateBrute);

        return false === $date ? new DateTime() : $date;
    }

    /**
     * @param list<int> $ids
     *
     * @return list<string>
     */
    public function erreurs(array $ids): array
    {
        $erreurs = [];
        if ([] === $ids) {
            $erreurs[] = 'Aucun numéro de personnage valide.';
        }
        if (!is_numeric($this->valeurBrute) || 0 === $this->valeur()) {
            $erreurs[] = $this->type->valeurLabel() . ' : entier non nul attendu.';
        }
        if ('' === $this->motifPropre() || AjoutMasseType::XP === $this->type && mb_strlen($this->motifPropre()) > self::XP_MOTIF_MAX) {
            $erreurs[] = "Motif obligatoire (100 caractères maximum pour l'XP).";
        }
        if (!DateTime::createFromFormat('!Y-m-d', $this->dateBrute)) {
            $erreurs[] = 'Date invalide.';
        }

        return $erreurs;
    }
}
