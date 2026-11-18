<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\LoginFingerprint;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * L'empreinte est stable, dépend de l'User-Agent, de la langue et du sel, et jamais de l'IP.
 */
#[Group('unit')]
class LoginFingerprintTest extends TestCase
{
    public function testEmpreinteStableEtFormatSha256(): void
    {
        $service = new LoginFingerprint('sel-a');
        $request = $this->request('Firefox/130', 'fr-FR,fr;q=0.9');

        $first = $service->compute($request);

        static::assertSame($first, $service->compute($this->request('Firefox/130', 'fr-FR,fr;q=0.9')));
        static::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $first);
    }

    public function testChangeAvecLUserAgentLaLangueEtLeSel(): void
    {
        $base = (new LoginFingerprint('sel-a'))->compute($this->request('Firefox/130', 'fr-FR'));

        static::assertNotSame($base, (new LoginFingerprint('sel-a'))->compute($this->request('Chrome/130', 'fr-FR')));
        static::assertNotSame($base, (new LoginFingerprint('sel-a'))->compute($this->request('Firefox/130', 'en-US')));
        static::assertNotSame($base, (new LoginFingerprint('sel-b'))->compute($this->request('Firefox/130', 'fr-FR')));
    }

    public function testIndependantDeLIpEtInsensibleALaCasseDeLaLangue(): void
    {
        $service = new LoginFingerprint('sel-a');
        $a = $this->request('Firefox/130', 'fr-FR', '203.0.113.1');
        $b = $this->request('Firefox/130', 'FR-fr', '198.51.100.7');

        static::assertSame($service->compute($a), $service->compute($b));
    }

    public function testNullSansAucunEnTete(): void
    {
        $request = Request::create('/');
        $request->headers->remove('User-Agent');
        $request->headers->remove('Accept-Language');

        static::assertNull((new LoginFingerprint('sel-a'))->compute($request));
    }

    private function request(string $userAgent, string $language, string $ip = '203.0.113.1'): Request
    {
        return Request::create(
            '/',
            'GET',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => $ip,
                'HTTP_USER_AGENT' => $userAgent,
                'HTTP_ACCEPT_LANGUAGE' => $language,
            ],
        );
    }
}
