<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * L'échange client_id / client_secret est limité par IP : au-delà de la limite, réponse 429
 * sans même vérifier les identifiants.
 */
#[Group('functional')]
class ApiClientTokenLimiteTest extends WebTestCase
{
    private const string IP = '203.0.113.7';

    protected function tearDown(): void
    {
        static::getContainer()->get('cache.rate_limiter')->clear();
        parent::tearDown();
    }

    public function testAuDelaDeLaLimiteLaReponseEst429(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        static::getContainer()->get('cache.rate_limiter')->clear();

        $this->echanger($client);
        static::assertSame(401, $client->getResponse()->getStatusCode());

        /** @var RateLimiterFactoryInterface $limiteur */
        $limiteur = static::getContainer()->get('limiter.api_client_token_ip');
        $limiteur->create(self::IP)->consume(99);

        $this->echanger($client);
        static::assertSame(429, $client->getResponse()->getStatusCode());
    }

    private function echanger(KernelBrowser $client): void
    {
        $client->request(
            'POST',
            '/api/client/token',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'REMOTE_ADDR' => self::IP,
            ],
            (string) json_encode(['client_id' => 'lm_inconnu', 'client_secret' => bin2hex(random_bytes(8))]),
        );
    }
}
