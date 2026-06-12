<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class GameExtension extends AbstractExtension
{
    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('renomme_broche', $this->renommeBroche(...)),
        ];
    }

    /**
     * Retourne l'indicateur de broche physique selon le score de renommée.
     * 0–4  : illustre (pas de broche)
     * 5–9  : petite broche
     * 10–19: broche moyenne
     * 20+  : grande broche (légende vivante).
     */
    public function renommeBroche(int $score): string
    {
        return match (true) {
            $score >= 20 => 'grande broche (légende vivante)',
            $score >= 10 => 'broche moyenne',
            $score >= 5 => 'petite broche',
            default => 'illustre (pas de broche)',
        };
    }
}
