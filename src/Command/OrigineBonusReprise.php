<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\OrigineBonusRepriseMode;
use App\Enum\OrigineBonusRepriseStatut;
use App\Service\OrigineBonusReprise as Reprise;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Reprise des personnages existants : fige le bonus d'origine d'après les fiefs ACTUELS des groupes de leur première
 * participation (aucun historique des fiefs n'existe). Simulation par défaut.
 *
 * Un cas est « douteux » quand la nouvelle règle (fiefs possédés) diffère de l'ancien calcul (territoire de référence
 * du groupe) : c'est à valider par les orgas avant d'appliquer.
 */
#[AsCommand(name: 'app:origine-bonus:reprise', description: "Fige le bonus d'origine des personnages existants d'après les fiefs actuels (simulation par défaut)")]
class OrigineBonusReprise extends Command
{
    public function __construct(
        private readonly Reprise $reprise,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Applique réellement la reprise (sinon simulation)')
            ->addOption('sans-douteux', null, InputOption::VALUE_NONE, "N'applique pas les cas douteux (ils restent sur l'ancien calcul)")
            ->addOption('limite', null, InputOption::VALUE_REQUIRED, 'Nombre de cas douteux listés (0 = tous)', '50');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $mode = match (true) {
            !$input->getOption('apply') => OrigineBonusRepriseMode::SIMULATION,
            (bool) $input->getOption('sans-douteux') => OrigineBonusRepriseMode::SANS_DOUTEUX,
            default => OrigineBonusRepriseMode::TOUT,
        };

        $result = $this->reprise->executer($mode);

        $this->afficherBilan($io, $result['counts']);
        $this->afficherDouteux($io, $result['douteux'], max(0, (int) $input->getOption('limite')));

        if (!$mode->isApply()) {
            $io->note("Simulation : rien n'a été écrit. Relancer avec --apply pour enregistrer (--sans-douteux pour laisser les cas douteux de côté).");

            return Command::SUCCESS;
        }

        $io->success($result['appliques'] . ' personnage(s) repris' . (OrigineBonusRepriseMode::SANS_DOUTEUX === $mode ? ' (cas douteux laissés de côté).' : '.'));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, int> $counts
     */
    private function afficherBilan(SymfonyStyle $io, array $counts): void
    {
        $io->table(['Résultat', 'Personnages'], [
            ['Bonus acquis (fief commun)', $counts[OrigineBonusRepriseStatut::ACQUIS->value]],
            ['Bonus refusé (aucun fief commun)', $counts[OrigineBonusRepriseStatut::REFUSE->value]],
            ['Différé (pas de groupe à la 1re participation)', $counts[OrigineBonusRepriseStatut::DIFFERE->value]],
            ['Décision déjà enregistrée', $counts[OrigineBonusRepriseStatut::DEJA->value]],
            ['Origine sans bonus NATIVE (rien à décider)', $counts[OrigineBonusRepriseStatut::SANS_BONUS->value]],
        ]);
    }

    /**
     * @param list<array{int, string, string, string, string, string}> $douteux
     */
    private function afficherDouteux(SymfonyStyle $io, array $douteux, int $limite): void
    {
        if ([] === $douteux) {
            return;
        }

        $affiches = 0 === $limite ? $douteux : \array_slice($douteux, offset: 0, length: $limite);
        $io->section(\count($douteux) . " cas douteux (nouvelle règle différente de l'ancien calcul)");
        $io->table(['Id', 'Personnage', 'Origine', 'Groupe 1re participation', 'Ancien calcul', 'Nouvelle règle'], $affiches);
        if (\count($affiches) < \count($douteux)) {
            $io->writeln(\sprintf('%d ligne(s) affichée(s) sur %d : --limite=0 pour tout lister.', \count($affiches), \count($douteux)));
        }
    }
}
