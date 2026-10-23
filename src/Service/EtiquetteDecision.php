<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\EtiquetteActionObjet;
use App\Enum\EtiquetteDestination;

/**
 * Résultat de l'application des règles à une étiquette.
 */
final readonly class EtiquetteDecision
{
    public bool $speciale;

    public ?string $facture;

    public ?string $nature;

    /** @var list<string> à signaler au dépouilleur (solliciter un orga expert et/ou consigner dans les remarques) */
    public array $anomalies;

    /**
     * @param list<string> $anomaliesSupplementaires anomalies propres à la couleur et au constat, ajoutées à celles de la qualité
     */
    public function __construct(
        public EtiquetteDestination $destination,
        public ?EtiquetteActionObjet $actionObjet,
        public bool $aPrevoirProchainOpus,
        EtiquetteQualite $qualite,
        array $anomaliesSupplementaires = [],
    ) {
        $this->speciale = $qualite->speciale;
        $this->facture = $qualite->facture;
        $this->nature = $qualite->nature;
        $this->anomalies = [...$qualite->anomalies, ...$anomaliesSupplementaires];
    }
}
