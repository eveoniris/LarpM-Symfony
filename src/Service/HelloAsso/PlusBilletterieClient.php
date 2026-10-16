<?php

declare(strict_types=1);

namespace App\Service\HelloAsso;

use SensitiveParameter;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client de l'API HelloAsso Plus Billetterie (lecture seule). Authentification : en-tête X-API-KEY.
 *
 * Les participants sont lus via `/organizers/{organizerId}/events/{eventId}/attendees` (les évènements sont
 * sous l'organisateur : le préfixe complet est obligatoire). Si la clé est refusée sur cette route, repli sur
 * la ressource « movements » (achats) : un mouvement peut alors contenir plusieurs billets, la pagination
 * porte donc sur les mouvements et non sur les participants.
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

    public function listAttendees(string $eventId, int $skip = 0, int $limit = 100, ?string $productId = null): array
    {
        $url = \sprintf('%s/organizers/%s/events/%s/attendees', self::BASE_URL, rawurlencode($this->organizerId), rawurlencode($eventId));

        $query = ['limit' => $limit, 'skip' => $skip, 'sort' => '{"_createdAt":"asc"}'];
        if (null !== $productId && '' !== $productId) {
            $query['filters'] = json_encode([['productId' => $productId]], JSON_THROW_ON_ERROR);
        }

        $refusedStatus = null;
        $response = $this->get($url, $query, [401, 403, 404], $refusedStatus);
        if (null === $response) {
            return $this->listFromMovements($eventId, $skip, $limit, $refusedStatus);
        }

        $attendees = \is_array($response['data'] ?? null) ? $response['data'] : [];

        $items = [];
        foreach ($attendees as $attendee) {
            if (\is_array($attendee)) {
                $items[] = Attendee::fromArray($attendee);
            }
        }

        return [...$this->page($items, \count($attendees), $response, $skip, $limit), 'source' => 'attendees', 'statutAttendees' => null];
    }

    /**
     * Teste en lecture seule les routes utiles et renvoie le statut HTTP de chacune (jamais la clé ni le corps).
     */
    public function diagnose(string $eventId): array
    {
        $org = self::BASE_URL . '/organizers/' . rawurlencode($this->organizerId);
        $event = $org . '/events/' . rawurlencode($eventId);

        $routes = [
            '/movements' => [$org . '/movements', ['limit' => 1]],
            '/events' => [$org . '/events', ['limit' => 1]],
            '/events/{eventId}' => [$event, []],
            '/events/{eventId}/attendees' => [$event . '/attendees', ['limit' => 1]],
        ];

        $results = [];
        foreach ($routes as $label => [$url, $query]) {
            try {
                $status = $this->httpClient->request('GET', $url, [
                    'headers' => ['X-API-KEY' => $this->apiKey, 'Accept' => 'application/json'],
                    'query' => $query,
                    'timeout' => 20,
                ])->getStatusCode();
                $results[] = ['route' => $label, 'statut' => $status, 'erreur' => null];
            } catch (ExceptionInterface $exception) {
                $results[] = ['route' => $label, 'statut' => null, 'erreur' => $exception->getMessage()];
            }
        }

        return $results;
    }

    /**
     * @return array{items: list<Attendee>, termine: bool, suivant: int, source: string, statutAttendees: int|null}
     *
     * @throws PlusBilletterieException
     */
    private function listFromMovements(string $eventId, int $skip, int $limit, ?int $attendeesStatus): array
    {
        $url = \sprintf('%s/organizers/%s/movements', self::BASE_URL, rawurlencode($this->organizerId));

        $data = $this->get($url, ['limit' => $limit, 'skip' => $skip, 'sort' => '{"_createdAt":"asc"}']) ?? [];
        $movements = \is_array($data['data'] ?? null) ? $data['data'] : [];

        $items = [];
        foreach ($movements as $movement) {
            if (\is_array($movement)) {
                array_push($items, ...self::attendeesFromMovement($movement, $eventId));
            }
        }

        return [...$this->page($items, \count($movements), $data, $skip, $limit), 'source' => 'movements', 'statutAttendees' => $attendeesStatus];
    }

    /**
     * @param list<Attendee>       $items
     * @param array<string, mixed> $data  réponse décodée de l'API
     *
     * @return array{items: list<Attendee>, termine: bool, suivant: int}
     */
    private function page(array $items, int $received, array $data, int $skip, int $limit): array
    {
        $total = $data['metadata']['totalCount'] ?? null;
        $suivant = $skip + $received;

        return [
            'items' => $items,
            'termine' => 0 === $received || $received < $limit || \is_int($total) && $suivant >= $total,
            'suivant' => $suivant,
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @param list<int>            $tolerated    statuts HTTP pour lesquels on renvoie null au lieu de lever une exception
     * @param int|null             $toleratedGot reçoit le statut HTTP toléré (null si la réponse est exploitable)
     *
     * @return array<string, mixed>|null
     *
     * @throws PlusBilletterieException
     */
    private function get(string $url, array $query, array $tolerated = [], ?int &$toleratedGot = null): ?array
    {
        $toleratedGot = null;

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['X-API-KEY' => $this->apiKey, 'Accept' => 'application/json'],
                'query' => $query,
                'timeout' => 20,
            ]);

            $status = $response->getStatusCode();
            if (\in_array($status, $tolerated, true)) {
                $toleratedGot = $status;

                return null;
            }
            if (401 === $status || 403 === $status) {
                throw new PlusBilletterieException('Clé API HelloAsso refusée (HTTP ' . $status . ').');
            }
            if (429 === $status) {
                throw new PlusBilletterieException('Trop de requêtes vers HelloAsso (HTTP 429), réessayez dans un instant.');
            }
            if ($status >= 400) {
                throw new PlusBilletterieException('Erreur HelloAsso (HTTP ' . $status . ').');
            }

            /** @var array<string, mixed> $decoded */
            $decoded = $response->toArray(false);

            return $decoded;
        } catch (ExceptionInterface $exception) {
            // Le message ne contient jamais la clé (elle n'est que dans les en-têtes).
            throw new PlusBilletterieException('Appel HelloAsso impossible : ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Extrait les billets d'un mouvement d'achat. Seules les données utiles au rapprochement sont conservées
     * (pas d'adresse ni de données bancaires).
     *
     * @param array<array-key, mixed> $movement
     *
     * @return list<Attendee>
     */
    public static function attendeesFromMovement(array $movement, string $eventId): array
    {
        return MovementAttendees::extract($movement, $eventId);
    }
}
