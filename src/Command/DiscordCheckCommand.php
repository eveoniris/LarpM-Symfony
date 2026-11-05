<?php

declare(strict_types=1);

namespace App\Command;

use SensitiveParameter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Diagnostic de la configuration Discord : présence et forme de chaque variable (jamais leur valeur),
 * extension sodium, validité du jeton du bot et du secret OAuth auprès de Discord.
 */
#[AsCommand(name: 'discord:check', description: 'Vérifie la configuration Discord (variables DISCORD_*, jeton du bot, secret OAuth) sans afficher les valeurs')]
final class DiscordCheckCommand extends Command
{
    private const string API = 'https://discord.com/api/v10';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%discord.bot.id%')]
        private readonly string $botId,
        #[Autowire('%discord.api.key%')]
        private readonly string $publicKey,
        #[Autowire('%discord.bot.token%')]
        #[SensitiveParameter]
        private readonly string $botToken,
        #[Autowire('%discord.oauth.id%')]
        private readonly string $oauthId,
        #[Autowire('%discord.oauth.secret%')]
        #[SensitiveParameter]
        private readonly string $oauthSecret,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('offline', null, InputOption::VALUE_NONE, 'Ne contacte pas Discord (contrôle de forme seulement)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rows = [
            ['DISCORD_BOT_ID', ...$this->describe($this->botId, '/^\d{15,25}$/', 'identifiant numérique de l\'application')],
            ['DISCORD_OAUTH_ID', ...$this->describe($this->oauthId, '/^\d{15,25}$/', 'identifiant numérique, identique à DISCORD_BOT_ID')],
            ['DISCORD_API_KEY (clé publique)', ...$this->describe($this->publicKey, '/^[0-9a-f]{64}$/i', '64 caractères hexadécimaux')],
            ['DISCORD_BOT_TOKEN', ...$this->describe($this->botToken, '/^[\w.-]{50,100}$/', 'environ 70 caractères')],
            ['DISCORD_OAUTH_SECRET', ...$this->describe($this->oauthSecret, '/^[\w-]{32}$/', '32 caractères')],
        ];
        $io->table(['Variable', 'Forme', 'Longueur', 'Attendu'], $rows);

        $failed = false;
        foreach ($rows as $row) {
            $failed = $failed || 'OK' !== $row[1];
        }
        if ($this->botId !== $this->oauthId) {
            $io->warning('DISCORD_BOT_ID et DISCORD_OAUTH_ID diffèrent : pour une même application ils sont identiques.');
        }

        if (\extension_loaded('sodium')) {
            $io->writeln('Extension sodium : <info>OK</info> (signature des commandes possible).');
        } else {
            $io->writeln('Extension sodium : <error>absente</error> (la signature des commandes ne peut pas être vérifiée).');
            $failed = true;
        }

        if (!$input->getOption('offline')) {
            $failed = $this->checkBotToken($io) || $failed;
            $failed = $this->checkOAuthSecret($io) || $failed;
        }

        $io->writeln(\sprintf('Interactions Endpoint URL à renseigner dans le portail : <comment>https://<votre-site>/discord/interactions</comment>'));
        $io->writeln(\sprintf('Redirect OAuth2 à renseigner dans le portail : <comment>https://<votre-site>/discord/callback</comment>'));

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function describe(string $value, string $pattern, string $expected): array
    {
        if ('' === $value || 'todo' === $value) {
            return ['ABSENTE', '0', $expected];
        }

        return [1 === preg_match($pattern, $value) ? 'OK' : 'FORME INATTENDUE', (string) \strlen($value), $expected];
    }

    private function checkBotToken(SymfonyStyle $io): bool
    {
        try {
            $status = $this->httpClient->request('GET', self::API . '/users/@me', ['headers' => ['Authorization' => 'Bot ' . $this->botToken]])->getStatusCode();
        } catch (ExceptionInterface $exception) {
            $io->writeln('Jeton du bot auprès de Discord : <error>injoignable</error> (' . $exception->getMessage() . ')');

            return true;
        }

        $io->writeln(200 === $status ? 'Jeton du bot auprès de Discord : <info>valide</info>.' : \sprintf('Jeton du bot auprès de Discord : <error>refusé (HTTP %d)</error>.', $status));

        return 200 !== $status;
    }

    private function checkOAuthSecret(SymfonyStyle $io): bool
    {
        try {
            $status = $this->httpClient->request('POST', self::API . '/oauth2/token', [
                'auth_basic' => [$this->oauthId, $this->oauthSecret],
                'body' => ['grant_type' => 'client_credentials', 'scope' => 'identify'],
            ])->getStatusCode();
        } catch (ExceptionInterface $exception) {
            $io->writeln('Secret OAuth auprès de Discord : <error>injoignable</error> (' . $exception->getMessage() . ')');

            return true;
        }

        $io->writeln(200 === $status ? 'Secret OAuth auprès de Discord : <info>valide</info>.' : \sprintf('Secret OAuth auprès de Discord : <error>refusé (HTTP %d)</error>.', $status));

        return 200 !== $status;
    }
}
