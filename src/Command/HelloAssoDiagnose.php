<?php

declare(strict_types=1);

namespace App\Command;

use SensitiveParameter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:helloasso:diagnose', description: 'Teste la clé HelloAsso Plus Billetterie réellement chargée (longueur, empreinte, statuts HTTP), sans l\'afficher')]
class HelloAssoDiagnose extends Command
{
    private const string BASE_URL = 'https://api.plusbilletterie.helloasso.com/v1';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%helloasso.plus.api_key%')]
        #[SensitiveParameter]
        private readonly string $apiKey,
        #[Autowire('%helloasso.plus.organizer_id%')]
        private readonly string $organizerId,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('eventId', InputArgument::REQUIRED, 'Identifiant d\'évènement HelloAsso (champ du GN)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $key = trim($this->apiKey);
        $io->writeln(\sprintf('Clé chargée : longueur %d, empreinte %s, organizerId %s', \strlen($key), substr(hash('sha256', $key), 0, 8), $this->organizerId));

        $org = self::BASE_URL . '/organizers/' . rawurlencode($this->organizerId);
        $eventId = rawurlencode((string) $input->getArgument('eventId'));
        $routes = [
            '/movements (avec clé)' => [$org . '/movements?limit=1', true],
            '/movements (sans clé)' => [$org . '/movements?limit=1', false],
            '/events/{eventId}/attendees (avec clé)' => [$org . '/events/' . $eventId . '/attendees?limit=1', true],
            '/events/{eventId}/attendees (sans clé)' => [$org . '/events/' . $eventId . '/attendees?limit=1', false],
        ];

        foreach ($routes as $label => [$url, $withKey]) {
            $headers = ['Accept' => 'application/json'];
            if ($withKey) {
                $headers['X-API-KEY'] = $key;
            }

            $io->writeln(\sprintf('%s -> %s', $label, $this->status($url, ['headers' => $headers])));
        }

        // Variantes de la requête qui compte (attendees avec clé) pour trouver ce qui diffère d'un appel PowerShell/curl.
        $attendees = $org . '/events/' . $eventId . '/attendees?limit=1';
        $variants = [
            'Accept */*' => ['headers' => ['Accept' => '*/*', 'X-API-KEY' => $key]],
            'sans Accept' => ['headers' => ['X-API-KEY' => $key]],
            'HTTP/1.1' => ['http_version' => '1.1', 'headers' => ['Accept' => 'application/json', 'X-API-KEY' => $key]],
            'HTTP/1.1 + Accept */*' => ['http_version' => '1.1', 'headers' => ['Accept' => '*/*', 'X-API-KEY' => $key]],
            'User-Agent PowerShell' => ['headers' => ['Accept' => '*/*', 'User-Agent' => 'Mozilla/5.0 (Windows NT; Windows NT 10.0; fr-FR) WindowsPowerShell/5.1', 'X-API-KEY' => $key]],
        ];
        foreach ($variants as $label => $options) {
            $io->writeln(\sprintf('attendees, %s -> %s', $label, $this->status($attendees, $options)));
        }

        return Command::SUCCESS;
    }

    /** @param array<string, mixed> $options */
    private function status(string $url, array $options): string
    {
        try {
            return (string) $this->httpClient->request('GET', $url, [...$options, 'timeout' => 20])->getStatusCode();
        } catch (ExceptionInterface $exception) {
            return 'erreur : ' . $exception->getMessage();
        }
    }
}
