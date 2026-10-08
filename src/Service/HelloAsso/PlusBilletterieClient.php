<?php

declare(strict_types=1);

namespace App\Service\HelloAsso;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client de l'API HelloAsso Plus Billetterie (lecture seule). Authentification : en-tête X-API-KEY.
 */
class PlusBilletterieClient implements PlusBilletterieClientInterface
{
    public const string BASE_URL = 'https://api.plusbilletterie.helloasso.com/v1';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[SensitiveParameter]
        private readonly string $apiKey,
        private readonly string $organizerId,
    ) {
    }

    public function listAttendees(string $eventId, int $skip = 0, int $limit = 100): array
    {
        $url = \sprintf('%s/organizers/%s/events/%s/attendees', self::BASE_URL, rawurlencode($this->organizerId), rawurlencode($eventId));

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['X-API-KEY' => $this->apiKey, 'Accept' => 'application/json'],
                'query' => ['limit' => $limit, 'skip' => $skip],
                'timeout' => 15,
            ]);

            $status = $response->getStatusCode();
            if (401 === $status || 403 === $status) {
                throw new PlusBilletterieException('Clé API HelloAsso refusée (HTTP ' . $status . ').');
            }
            if (429 === $status) {
                throw new PlusBilletterieException('Trop de requêtes vers HelloAsso (HTTP 429), réessayez dans un instant.');
            }
            if ($status >= 400) {
                throw new PlusBilletterieException('Erreur HelloAsso (HTTP ' . $status . ').');
            }

            $data = $response->toArray(false);
        } catch (ExceptionInterface $exception) {
            // Le message ne contient jamais la clé (elle n'est que dans les en-têtes).
            throw new PlusBilletterieException('Appel HelloAsso impossible : ' . $exception->getMessage(), 0, $exception);
        }

        // L'enveloppe exacte est à confirmer : liste directe ou clé « data » / « items ».
        $rows = array_is_list($data) ? $data : $data['data'] ?? $data['items'] ?? $data['attendees'] ?? [];
        $items = [];
        foreach (\is_array($rows) ? $rows : [] as $row) {
            if (\is_array($row)) {
                $items[] = Attendee::fromArray($row);
            }
        }

        return ['items' => $items, 'termine' => \count($items) < $limit];
    }
}
