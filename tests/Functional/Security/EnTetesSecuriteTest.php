<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\EventListener\EnTetesSecuriteSubscriber;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
class EnTetesSecuriteTest extends WebTestCase
{
    public function testLesEnTetesDeSecuriteSontPosesSurUnePagePublique(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        static::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        static::assertResponseHeaderSame('X-Frame-Options', 'SAMEORIGIN');
        static::assertResponseHeaderSame('Referrer-Policy', 'strict-origin-when-cross-origin');
        static::assertResponseNotHasHeader('Strict-Transport-Security');
    }

    public function testLaCspEstEnModeRapportParDefaut(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        static::assertResponseHeaderSame('Content-Security-Policy-Report-Only', EnTetesSecuriteSubscriber::CSP);
        static::assertResponseNotHasHeader('Content-Security-Policy');
        static::assertStringContainsString("object-src 'none'", EnTetesSecuriteSubscriber::CSP);
        static::assertStringContainsString('report-uri /csp/rapport', EnTetesSecuriteSubscriber::CSP);
    }

    public function testHstsEnHttps(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login', server: ['HTTPS' => 'on']);

        static::assertResponseHeaderSame('Strict-Transport-Security', 'max-age=31536000');
    }
}
