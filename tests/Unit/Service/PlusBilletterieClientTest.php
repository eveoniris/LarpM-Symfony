<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\HelloAsso\PlusBilletterieClient;
use App\Service\HelloAsso\PlusBilletterieException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class PlusBilletterieClientTest extends TestCase
{
    private const string ORGANIZER = 'org1';

    private function client(callable|array $responses): PlusBilletterieClient
    {
        return new PlusBilletterieClient(new MockHttpClient($responses), 'cle', self::ORGANIZER);
    }

    private static function json(array $data, int $status = 200): MockResponse
    {
        return new MockResponse(json_encode($data, JSON_THROW_ON_ERROR), [
            'http_code' => $status,
            'response_headers' => ['content-type: application/json'],
        ]);
    }

    public function testLitLaRouteAttendeesAvecPrefixeOrganisateur(): void
    {
        $urls = [];
        $client = $this->client(function (string $method, string $url) use (&$urls): MockResponse {
            $urls[] = $url;

            return self::json([
                'data' => [
                    ['_id' => 'a1', 'email' => 'Joueur@Example.org', 'status' => 'enabled', 'productId' => 'p1', 'firstName' => 'Jeanne', 'lastName' => 'Darc'],
                    ['_id' => 'a2', 'email' => 'autre@example.org', 'status' => 'used', 'productId' => 'p1'],
                ],
                'metadata' => ['totalCount' => 2],
            ]);
        });

        $page = $client->listAttendees('evt1', 0, 100);

        $this->assertStringContainsString('/organizers/org1/events/evt1/attendees', $urls[0]);
        $this->assertCount(2, $page['items']);
        $this->assertSame('joueur@example.org', $page['items'][0]->email);
        $this->assertSame('Jeanne Darc', $page['items'][0]->nom);
        $this->assertTrue($page['termine']);
        $this->assertSame(2, $page['suivant']);
    }

    public function testPaginationParParticipant(): void
    {
        $client = $this->client([
            self::json(['data' => [['_id' => 'a1'], ['_id' => 'a2']], 'metadata' => ['totalCount' => 3]]),
        ]);

        $page = $client->listAttendees('evt1', 0, 2);

        $this->assertFalse($page['termine']);
        $this->assertSame(2, $page['suivant']);
    }

    public function testFiltreParProduit(): void
    {
        $url = '';
        $client = $this->client(function (string $method, string $requested) use (&$url): MockResponse {
            $url = $requested;

            return self::json(['data' => []]);
        });

        $client->listAttendees('evt1', 0, 100, 'prod-enfant');

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('[{"productId":"prod-enfant"}]', $query['filters']);
    }

    public function testRepliSurMovementsSiAttendeesRefuse(): void
    {
        $urls = [];
        $client = $this->client(function (string $method, string $url) use (&$urls): MockResponse {
            $urls[] = $url;

            if (str_contains($url, '/attendees')) {
                return self::json(['message' => 'Unauthorized'], 401);
            }

            return self::json([
                'data' => [[
                    '_id' => 'mvt1',
                    'type' => 'order',
                    'status' => 'successful',
                    'eventId' => 'evt1',
                    'sellingItems' => [['products' => [['productId' => 'p1', 'attendeeId' => 'att1', 'attendeeEmail' => 'x@example.org', 'status' => 'enabled']]]],
                ]],
                'metadata' => ['totalCount' => 1],
            ]);
        });

        $page = $client->listAttendees('evt1', 0, 100);

        $this->assertCount(2, $urls);
        $this->assertStringContainsString('/organizers/org1/movements', $urls[1]);
        $this->assertCount(1, $page['items']);
        $this->assertSame('att1', $page['items'][0]->id);
        $this->assertSame('movements', $page['source']);
        $this->assertSame(401, $page['statutAttendees']);
    }

    public function testSourceAttendeesQuandLaRouteRepond(): void
    {
        $client = $this->client([self::json(['data' => [['_id' => 'a1']], 'metadata' => ['totalCount' => 1]])]);

        $page = $client->listAttendees('evt1');

        $this->assertSame('attendees', $page['source']);
        $this->assertNull($page['statutAttendees']);
    }

    public function testDiagnosticRenvoieLeStatutDeChaqueRoute(): void
    {
        $client = $this->client(
            static fn (string $method, string $url): MockResponse => self::json([], str_contains($url, '/attendees') ? 401 : 200),
        );

        $routes = $client->diagnose('evt1');

        $this->assertCount(4, $routes);
        $parRoute = array_column($routes, 'statut', 'route');
        $this->assertSame(200, $parRoute['/movements']);
        $this->assertSame(401, $parRoute['/events/{eventId}/attendees']);
    }

    public function testCleRefuseePartout(): void
    {
        $client = $this->client(fn (): MockResponse => self::json(['message' => 'Unauthorized'], 401));

        $this->expectException(PlusBilletterieException::class);
        $this->expectExceptionMessage('Clé API HelloAsso refusée');

        $client->listAttendees('evt1');
    }
}
