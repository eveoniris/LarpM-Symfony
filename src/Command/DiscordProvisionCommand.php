<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\DiscordGuild;
use App\Enum\DiscordProvisionStatus;
use App\Repository\DiscordGuildRepository;
use App\Service\Discord\DiscordApiException;
use App\Service\Discord\DiscordProvisioningService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Crée sur Discord le rôle, la catégorie et le salon des groupes transverses qui n'en ont pas (simulation par défaut).
 */
#[AsCommand(name: 'discord:provision', description: 'Crée les rôles, catégories et salons Discord des groupes transverses (simulation sans --apply)')]
final class DiscordProvisionCommand extends Command
{
    public function __construct(
        private readonly DiscordGuildRepository $guilds,
        private readonly DiscordProvisioningService $provisioning,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('guild', null, InputOption::VALUE_REQUIRED, 'Identifiant Discord de la guilde (toutes les guildes configurées si absent)')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Crée réellement les éléments sur Discord (sinon simulation)')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Nombre maximal de groupes traités par guilde');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');
        $limitOption = $input->getOption('limit');
        $limit = null !== $limitOption && (int) $limitOption > 0 ? (int) $limitOption : null;
        $guildId = $input->getOption('guild');

        $guilds = null === $guildId ? $this->guilds->findAllOrdered() : array_filter([$this->guilds->findOneBy(['guildId' => (string) $guildId])]);
        if ([] === $guilds) {
            $io->error('Aucune guilde configurée correspondante (voir /admin/discord).');

            return Command::FAILURE;
        }

        $io->note($apply ? 'Mode réel : les éléments sont créés sur Discord.' : 'Simulation : rien n\'est créé. Relancez avec --apply pour appliquer.');

        $failed = false;
        foreach ($guilds as $guild) {
            $io->section(\sprintf('%s (%s)', $guild->getNom(), $guild->getGuildId()));

            try {
                $lines = $this->provisioning->provision($guild, $apply, $limit);
            } catch (DiscordApiException $exception) {
                $io->error($exception->getMessage());
                $failed = true;

                continue;
            }

            if ([] === $lines) {
                $io->writeln('Rien à provisionner.');
                $this->reportUnmatched($io, $guild);

                continue;
            }

            $io->table(['Groupe', 'Rôle', 'Catégorie et salon', 'État'], array_map(static fn ($line): array => [
                $line->groupe,
                $line->roleName,
                '' === $line->channelName ? $line->categoryName : $line->categoryName . ' / #' . $line->channelName,
                trim($line->status->label() . ' ' . $line->detail),
            ], $lines));

            foreach ($lines as $line) {
                if (DiscordProvisionStatus::ERREUR === $line->status) {
                    $failed = true;
                }
            }

            $this->reportUnmatched($io, $guild);
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    /** Catégories numérotées présentes sur Discord mais sans groupe associé : à vérifier à la main, jamais modifiées. */
    private function reportUnmatched(SymfonyStyle $io, DiscordGuild $guild): void
    {
        try {
            $unmatched = $this->provisioning->unmatchedCategories($guild);
        } catch (DiscordApiException) {
            return;
        }

        if ([] === $unmatched) {
            return;
        }

        $io->writeln(\sprintf('<comment>%d catégorie(s) numérotée(s) sans groupe correspondant (non modifiées) :</comment>', \count($unmatched)));
        $io->listing(array_column($unmatched, 'name'));
    }
}
