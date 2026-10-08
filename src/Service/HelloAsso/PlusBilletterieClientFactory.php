<?php

declare(strict_types=1);

namespace App\Service\HelloAsso;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Choisit le client réel quand une clé API est configurée, sinon le faux client.
 */
final readonly class PlusBilletterieClientFactory
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[SensitiveParameter]
        private string $apiKey,
        private string $organizerId,
    ) {
    }

    public function create(): PlusBilletterieClientInterface
    {
        if ('' === trim($this->apiKey)) {
            return new FakePlusBilletterieClient();
        }

        return new PlusBilletterieClient($this->httpClient, trim($this->apiKey), $this->organizerId);
    }
}
