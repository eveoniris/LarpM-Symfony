<?php

declare(strict_types=1);

namespace App\Service\Discord;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Vérifie la signature Ed25519 que Discord joint à chaque requête vers l'Interactions Endpoint URL.
 */
final readonly class DiscordSignatureVerifier
{
    public function __construct(
        #[Autowire('%discord.api.key%')]
        private string $publicKeyHex,
    ) {
    }

    public function isValid(string $body, ?string $signatureHex, ?string $timestamp): bool
    {
        if (null === $signatureHex || null === $timestamp || '' === $timestamp) {
            return false;
        }
        if (1 !== preg_match('/^[0-9a-f]{128}$/i', $signatureHex) || 1 !== preg_match('/^[0-9a-f]{64}$/i', $this->publicKeyHex)) {
            return false;
        }

        return sodium_crypto_sign_verify_detached(sodium_hex2bin($signatureHex), $timestamp . $body, sodium_hex2bin($this->publicKeyHex));
    }
}
