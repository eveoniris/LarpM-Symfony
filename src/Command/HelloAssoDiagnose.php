<?php

declare(strict_types=1);

namespace App\Command;

use SensitiveParameter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this->addOption('structure', null, InputOption::VALUE_REQUIRED, 'Affiche la structure (champs et types, sans valeurs personnelles) d\'une ressource : attendees, products, formresponses...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $key = trim($this->apiKey);

        $resource = $input->getOption('structure');
        if (\is_string($resource) && '' !== $resource) {
            return $this->showStructure($io, $key, (string) $input->getArgument('eventId'), $resource);
        }
        $io->writeln(\sprintf('Clé chargée : longueur %d, empreinte %s, organizerId %s', \strlen($key), substr(hash('sha256', $key), 0, 8), $this->organizerId));

        $org = self::BASE_URL . '/organizers/' . rawurlencode($this->organizerId);
        $eventId = rawurlencode((string) $input->getArgument('eventId'));
        $routes = [
            'organisateur (avec clé)' => [$org, true],
            'organisateur (sans clé)' => [$org, false],
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

    private function showStructure(SymfonyStyle $io, string $key, string $eventId, string $resource): int
    {
        $url = \sprintf('%s/organizers/%s/events/%s/%s', self::BASE_URL, rawurlencode($this->organizerId), rawurlencode($eventId), rawurlencode($resource));

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['X-API-KEY' => $key, 'Accept' => 'application/json'],
                'query' => ['limit' => 'forms' === $resource ? 20 : 1],
                'timeout' => 20,
            ]);
            $status = $response->getStatusCode();
            $data = $response->toArray(false);
        } catch (ExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->writeln(\sprintf('%s -> %d', $resource, $status));
        if ('forms' === $resource) {
            $io->writeln($this->formsSummary($data));

            return Command::SUCCESS;
        }

        $first = \is_array($data['data'] ?? null) && [] !== $data['data'] ? $data['data'][0] : $data;
        $io->writeln(json_encode($this->shape($first, $resource), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        return Command::SUCCESS;
    }

    /**
     * Formulaires et champs (clé, type, libellé). Ce sont des intitulés de questions, jamais des réponses de participants.
     *
     * @param array<array-key, mixed> $data
     */
    private function formsSummary(array $data): string
    {
        $lines = [];
        foreach (\is_array($data['data'] ?? null) ? $data['data'] : [] as $form) {
            if (!\is_array($form)) {
                continue;
            }

            $lines[] = \sprintf('Formulaire %s : %s', $this->text($form['_id'] ?? null), $this->text($form['name'] ?? null));
            foreach (\is_array($form['fields'] ?? null) ? $form['fields'] : [] as $field) {
                if (\is_array($field)) {
                    $lines[] = \sprintf('  %s | %s | %s', $this->text($field['key'] ?? null), $this->text($field['type'] ?? null), $this->text($field['name'] ?? null));
                }
            }
        }

        return [] === $lines ? 'Aucun formulaire.' : implode("\n", $lines);
    }

    private function text(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : '?';
    }

    /** Remplace les valeurs par leur type ; seuls les libellés techniques (label, title, type, name des produits) sont conservés. */
    private function shape(mixed $value, string $resource, string $name = ''): mixed
    {
        if (\is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $this->shape($v, $resource, (string) $k);
            }

            return $out;
        }

        $showable = \in_array($name, ['label', 'title', 'type', 'fieldType', 'productType'], true) || 'name' === $name && 'products' === $resource;
        if (\is_string($value)) {
            return $showable ? mb_substr($value, 0, 60) : 'string(' . mb_strlen($value) . ')';
        }

        return get_debug_type($value);
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
