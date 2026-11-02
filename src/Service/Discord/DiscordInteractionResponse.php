<?php

declare(strict_types=1);

namespace App\Service\Discord;

use Closure;

/**
 * Réponse à une interaction Discord : le corps JSON immédiat et, pour un accusé de réception différé,
 * le travail à exécuter une fois la réponse envoyée.
 */
final readonly class DiscordInteractionResponse
{
    private const int FLAG_EPHEMERAL = 64;

    /**
     * @param array<string, mixed> $body
     * @param (Closure(): void)|null $afterResponse
     */
    public function __construct(
        public array $body,
        public ?Closure $afterResponse = null,
    ) {
    }

    public static function pong(): self
    {
        return new self(['type' => 1]);
    }

    /** Message visible seulement de l'auteur de la commande. */
    public static function message(string $content): self
    {
        return new self(['type' => 4, 'data' => ['content' => $content, 'flags' => self::FLAG_EPHEMERAL]]);
    }

    /**
     * Accusé de réception éphémère : Discord attend une réponse en moins de 3 s, le vrai message suit.
     *
     * @param Closure(): void $afterResponse
     */
    public static function deferred(Closure $afterResponse): self
    {
        return new self(['type' => 5, 'data' => ['flags' => self::FLAG_EPHEMERAL]], $afterResponse);
    }
}
