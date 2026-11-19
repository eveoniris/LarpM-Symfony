<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Lit une liste d'identifiants saisie à la main ou collée depuis un tableur (une colonne sans en-tête).
 */
final class IdListParser
{
    /**
     * Sépare sur virgules, points-virgules, espaces et retours à la ligne ; ignore ce qui n'est pas un entier positif ;
     * supprime les doublons en gardant l'ordre de saisie.
     *
     * @return list<int>
     */
    public function parse(string $raw): array
    {
        $ids = [];
        $tokens = preg_split('/[\s,;]+/', $raw, -1, \PREG_SPLIT_NO_EMPTY);
        foreach (false === $tokens ? [] : $tokens as $token) {
            if (!ctype_digit($token) || (int) $token <= 0) {
                continue;
            }
            $ids[(int) $token] = true;
        }

        return array_keys($ids);
    }
}
