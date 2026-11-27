<?php

declare(strict_types=1);

namespace App\Tests\Functional\Csp;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
final class CspRapportTest extends WebTestCase
{
    public function testUnRapportValideEstAccepteSansEtreConnecte(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/csp/rapport',
            server: ['CONTENT_TYPE' => 'application/csp-report'],
            content: json_encode(
                [
                    'csp-report' => [
                        'document-uri' => 'https://larpmanager.test/login',
                        'violated-directive' => 'script-src-elem',
                        'blocked-uri' => 'https://exemple.test/x.js',
                        'line-number' => 12,
                    ],
                ],
                \JSON_THROW_ON_ERROR,
            ),
        );

        static::assertResponseStatusCodeSame(204);
    }

    public function testUnCorpsQuiNEstPasUnRapportEstRefuse(): void
    {
        $client = static::createClient();
        $client->request('POST', '/csp/rapport', content: '{"autre": 1}');

        static::assertResponseStatusCodeSame(400);
    }

    public function testUnCorpsTropGrosEstRefuse(): void
    {
        $client = static::createClient();
        $client->request('POST', '/csp/rapport', content: str_repeat('a', times: 9000));

        static::assertResponseStatusCodeSame(413);
    }

    public function testSeulPostEstAccepte(): void
    {
        $client = static::createClient();
        $client->request('GET', '/csp/rapport');

        static::assertResponseStatusCodeSame(405);
    }
}
