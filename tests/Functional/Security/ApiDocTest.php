<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class ApiDocTest extends WebTestCase
{
    public function testDocOpenApiAccessibleSansJeton(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/doc.json');

        static::assertSame(200, $client->getResponse()->getStatusCode());
        /** @var array{paths: array<string, mixed>} $doc */
        $doc = json_decode((string) $client->getResponse()->getContent(), true);
        static::assertArrayHasKey('/api/login_check', $doc['paths']);
        static::assertArrayHasKey('/api/admin/groupes', $doc['paths']);
        static::assertArrayHasKey('/api/token/refresh', $doc['paths']);
    }

    public function testSwaggerUiAccessibleSansJeton(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/doc');

        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
