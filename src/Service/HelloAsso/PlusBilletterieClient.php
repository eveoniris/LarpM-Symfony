<?php

declare(strict_types=1);

namespace App\Service\HelloAsso;

use SensitiveParameter;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client de l'API HelloAsso Plus Billetterie (lecture seule). Authentification : en-tête X-API-KEY.
 *
 * Les participants sont lus via la ressource « movements » (achats) : les routes « events/attendees »
 * renvoient 401 avec la clé actuelle. Un mouvement peut contenir plusieurs billets, la pagination porte
 * donc sur les mouvements et non sur les participants.
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
        $url = \sprintf('%s/organizers/%s/movements', self::BASE_URL, rawurlencode($this->organizerId));

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['X-API-KEY' => $this->apiKey, 'Accept' => 'application/json'],
                'query' => ['limit' => $limit, 'skip' => $skip, 'sort' => '{"_createdAt":"asc"}'],
                'timeout' => 20,
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

        $movements = \is_array($data['data'] ?? null) ? $data['data'] : [];
        $total = $data['metadata']['totalCount'] ?? null;

        $items = [];
        foreach ($movements as $movement) {
            if (\is_array($movement)) {
                array_push($items, ...self::attendeesFromMovement($movement, $eventId));
            }
        }

        $suivant = $skip + \count($movements);

        return [
            'items' => $items,
            'termine' => [] === $movements || \count($movements) < $limit || \is_int($total) && $suivant >= $total,
            'suivant' => $suivant,
        ];
    }

    /**
     * Extrait les billets d'un mouvement d'achat. Seules les données utiles au rapprochement sont conservées
     * (pas d'adresse ni de données bancaires).
     *
     * @param array<string, mixed> $movement
     *
     * @return list<Attendee>
     */
    public static function attendeesFromMovement(array $movement, string $eventId): array
    {
        if ('order' !== ($movement['type'] ?? null) || 'successful' !== ($movement['status'] ?? null) || ($movement['eventId'] ?? null) !== $eventId) {
            return [];
        }

        $client = \is_array($movement['clientDetails'] ?? null) ? $movement['clientDetails'] : [];
        $attendees = [];

        foreach (\is_array($movement['sellingItems'] ?? null) ? $movement['sellingItems'] : [] as $sellingItem) {
            foreach (\is_array($sellingItem['products'] ?? null) ? $sellingItem['products'] : [] as $product) {
                if (!\is_array($product) || !isset($product['attendeeId'])) {
                    continue;
                }

                $attendees[] = Attendee::fromArray([
                    '_id' => $product['attendeeId'],
                    'orderId' => $movement['_id'] ?? null,
                    'productId' => $product['productId'] ?? null,
                    'email' => $product['attendeeEmail'] ?? null,
                    'firstName' => $client['firstName'] ?? null,
                    'lastName' => $client['lastName'] ?? null,
                    'status' => $product['status'] ?? null,
                    'price' => $product['price'] ?? null,
                    'clientReference' => $movement['clientReference'] ?? null,
                    'formResponseId' => $product['formResponseId'] ?? null,
                    'movementStatus' => $movement['status'] ?? null,
                ]);
            }
        }

        return $attendees;
    }
}
