<?php

declare(strict_types=1);

namespace App\Service\Discord;

use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Liaison OAuth2 Discord (scope `identify`) : prouve qu'un utilisateur du site possède bien un compte Discord.
 */
final readonly class DiscordOAuthClient
{
    private const string API = 'https://discord.com/api/v10';

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%discord.oauth.id%')]
        private string $clientId,
        #[Autowire('%discord.oauth.secret%')]
        #[SensitiveParameter]
        private string $clientSecret,
    ) {
    }

    public function authorizeUrl(string $redirectUri, string $state): string
    {
        return 'https://discord.com/oauth2/authorize?'
        . http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'scope' => 'identify',
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'prompt' => 'consent',
        ]);
    }

    /**
     * Échange le code d'autorisation contre un jeton, puis lit l'identité du compte Discord.
     *
     * @throws DiscordOAuthException
     */
    public function fetchIdentity(string $code, string $redirectUri): DiscordIdentity
    {
        try {
            $token = $this->httpClient->request('POST', self::API . '/oauth2/token', [
                'body' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ],
            ])->toArray();

            $accessToken = $token['access_token'] ?? null;
            if (!\is_string($accessToken) || '' === $accessToken) {
                throw new DiscordOAuthException('Jeton Discord absent de la réponse.');
            }

            $me = $this->httpClient->request('GET', self::API . '/users/@me', [
                'auth_bearer' => $accessToken,
            ])->toArray();
        } catch (ExceptionInterface $exception) {
            throw new DiscordOAuthException('Échec de la communication avec Discord : ' . $exception->getMessage(), 0, $exception);
        }

        $id = $me['id'] ?? null;
        if (!\is_string($id) || 1 !== preg_match('/^\d{15,25}$/', $id)) {
            throw new DiscordOAuthException('Identifiant Discord invalide dans la réponse.');
        }

        return new DiscordIdentity($id, (string) ($me['username'] ?? ''));
    }
}
