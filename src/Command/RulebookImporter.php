<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Rulebook\ImportLigne;
use App\Service\Rulebook\RulebookImportService;
use App\Service\Rulebook\SpecParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:rulebook:importer', description: 'Importe les règles de la spec de gameplay (gameplay-rules-precise.md) dans le rulebook (simulation par défaut)')]
class RulebookImporter extends Command
{
    public function __construct(
        private readonly SpecParser $parser,
        private readonly RulebookImportService $importService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('fichier', InputArgument::REQUIRED, 'Chemin du fichier de spec markdown');
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Enregistre réellement les règles (sinon simulation)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fichier = (string) $input->getArgument('fichier');
        $appliquer = (bool) $input->getOption('apply');

        $contenu = is_file($fichier) && is_readable($fichier) ? file_get_contents($fichier) : false;
        if (false === $contenu) {
            $io->error(\sprintf('Fichier illisible : %s', $fichier));

            return Command::FAILURE;
        }

        $regles = $this->parser->parser($contenu);
        if ([] === $regles) {
            $io->error('Aucune règle trouvée (titres de niveau 2 attendus).');

            return Command::FAILURE;
        }

        $lignes = $this->importService->importer($regles, $appliquer);

        $io->table(['Domaine', 'Code', 'Titre', 'Statut', 'Tests', 'Résultat'], array_map(static fn (ImportLigne $ligne): array => [
            $ligne->regle->domaineTitre,
            $ligne->code,
            mb_strimwidth($ligne->regle->titre, 0, 60, '…'),
            $ligne->regle->statut->label() . ($ligne->regle->statutDetecte ? '' : ' (?)'),
            \count($ligne->regle->testsRefs),
            $ligne->creee ? 'créée' : 'déjà en base',
        ], $lignes));

        $creees = \count(array_filter($lignes, static fn (ImportLigne $ligne): bool => $ligne->creee));
        $sansStatut = array_filter($lignes, static fn (ImportLigne $ligne): bool => $ligne->creee && !$ligne->regle->statutDetecte);

        if ([] !== $sansStatut) {
            $io->warning(\sprintf(
                '%d règle(s) sans statut reconnu (marquées « (?) », importées « À confirmer »), à vérifier : %s',
                \count($sansStatut),
                implode(', ', array_map(static fn (ImportLigne $ligne): string => $ligne->code, $sansStatut)),
            ));
        }

        $resume = \sprintf('%d règle(s) lue(s), %d à créer, %d déjà en base.', \count($lignes), $creees, \count($lignes) - $creees);

        if ($appliquer) {
            $io->success('Import appliqué. ' . $resume);
        } else {
            $io->note('Simulation, rien n\'est enregistré. ' . $resume . ' Relancer avec --apply pour importer.');
        }

        return Command::SUCCESS;
    }
}
