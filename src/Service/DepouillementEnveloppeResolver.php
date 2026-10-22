<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Gn;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Enum\DepouillementType;
use App\Exception\EnveloppeIntrouvableException;
use App\Repository\DepouillementRepository;

/**
 * Retrouve ce que le dépouilleur ne connaît pas (session de groupe, participant) à partir de ce qu'il connaît :
 * le GN de la page et le numéro inscrit sur l'enveloppe (numéro de groupe, ou numéro de personnage).
 */
readonly class DepouillementEnveloppeResolver
{
    public function __construct(
        private DepouillementRepository $depouillementRepository,
    ) {
    }

    /**
     * Tolère "21 Argos" ou "02328" : seuls les chiffres de tête comptent, sans zéros inutiles.
     *
     * @throws EnveloppeIntrouvableException si la saisie ne commence pas par un nombre
     */
    public function normaliserNumero(string $saisie): string
    {
        if (1 !== preg_match('/^\s*(\d+)/', $saisie, $correspondances)) {
            throw new EnveloppeIntrouvableException('Le numéro doit commencer par des chiffres.');
        }

        $sansZeros = ltrim($correspondances[1], characters: '0');

        return '' === $sansZeros ? '0' : $sansZeros;
    }

    /**
     * @param string $numero numéro déjà normalisé
     *
     * @throws EnveloppeIntrouvableException si aucun groupe ou personnage de ce GN ne porte ce numéro
     */
    public function resoudre(Gn $gn, DepouillementType $type, string $numero): GroupeGn|Participant
    {
        if (DepouillementType::GROUPE === $type) {
            return $this->depouillementRepository->findGroupeGnParNumero($gn, (int) $numero) ?? throw new EnveloppeIntrouvableException(\sprintf('Aucun groupe n°%s dans ce GN.', $numero));
        }

        return $this->depouillementRepository->findParticipantParPersonnage($gn, (int) $numero) ?? throw new EnveloppeIntrouvableException(\sprintf('Aucun personnage n°%s inscrit à ce GN.', $numero));
    }
}
