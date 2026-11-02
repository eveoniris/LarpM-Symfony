<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Discord;

use App\Service\Discord\DiscordOAuthClient;
use App\Service\Discord\DiscordOAuthException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class DiscordOAuthClientTest extends TestCase
{
    public function testAuthorizeUrlContientLesParametresOAuth(): void
    {
        $client = new DiscordOAuthClient(new MockHttpClient(), '123', 'secret');

        $url = $client->authorizeUrl('https://site.test/discord/callback', 'abc');
        parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://discord.com/oauth2/authorize?', $url);
        $this->assertSame('123', $query['client_id']);
        $this->assertSame('identify', $query['scope']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('abc', $query['state']);
        $this->assertSame('https://site.test/discord/callback', $query['redirect_uri']);
    }

    public function testFetchIdentityLitLIdentite(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"access_token":"tok"}'),
            new MockResponse(json_encode(['id' => '80351110224678912', 'username' => 'nelly'], \JSON_THROW_ON_ERROR)),
        ]);

        $identity = (new DiscordOAuthClient($http, '123', 'secret'))->fetchIdentity('code', 'https://site.test/cb');

        $this->assertSame('80351110224678912', $identity->id);
        $this->assertSame('nelly', $identity->username);
    }

    public function testFetchIdentityRefuseUnIdentifiantInvalide(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"access_token":"tok"}'),
            new MockResponse(json_encode(['id' => 'pas-un-id', 'username' => 'x'], \JSON_THROW_ON_ERROR)),
        ]);

        $this->expectException(DiscordOAuthException::class);
        (new DiscordOAuthClient($http, '123', 'secret'))->fetchIdentity('code', 'https://site.test/cb');
    }

    public function testFetchIdentityEchoueSiDiscordRepondEnErreur(): void
    {
        $http = new MockHttpClient([new MockResponse('{"error":"invalid_grant"}', ['http_code' => 400])]);

        $this->expectException(DiscordOAuthException::class);
        (new DiscordOAuthClient($http, '123', 'secret'))->fetchIdentity('code', 'https://site.test/cb');
    }

    public function testFetchIdentityEchoueSansJeton(): void
    {
        $http = new MockHttpClient([new MockResponse('{}')]);

        $this->expectException(DiscordOAuthException::class);
        (new DiscordOAuthClient($http, '123', 'secret'))->fetchIdentity('code', 'https://site.test/cb');
    }
}
