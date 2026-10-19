<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\PersonnageLangues;
use App\Enum\LangueSourceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:langues:dedoublonner', description: 'Supprime les doublons de langues des personnages en gardant la source qui prime (simulation par défaut)')]
class LangueDedoublonner extends Command
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Applique réellement les suppressions (sinon simulation)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');

        /** @var list<PersonnageLangues> $all */
        $all = $this->entityManager->getRepository(PersonnageLangues::class)->findAll();

        $groups = [];
        foreach ($all as $personnageLangue) {
            $key = $personnageLangue->getPersonnage()->getId() . '-' . $personnageLangue->getLangue()->getId();
            $groups[$key][] = $personnageLangue;
        }

        $rows = [];
        $removed = 0;
        foreach ($groups as $group) {
            if (\count($group) < 2) {
                continue;
            }

            usort(
                $group,
                static fn (PersonnageLangues $a, PersonnageLangues $b): int => (
                    [LangueSourceType::priorityOf($a->getSource()), $a->getId()] <=> [LangueSourceType::priorityOf($b->getSource()), $b->getId()]
                ),
            );
            $kept = array_shift($group);

            foreach ($group as $duplicate) {
                $rows[] = [
                    $kept->getPersonnage()->getId(),
                    (string) $kept->getLangue(),
                    $kept->getSource(),
                    $duplicate->getSource() . ($duplicate->getTriggerTag() ? ' – ' . $duplicate->getTriggerTag() : ''),
                ];

                if ($apply) {
                    $this->entityManager->remove($duplicate);
                }
                ++$removed;
            }
        }

        if ([] === $rows) {
            $io->success('Aucun doublon.');

            return Command::SUCCESS;
        }

        $io->table(['Personnage', 'Langue', 'Source gardée', 'Doublon retiré'], $rows);

        if (!$apply) {
            $io->note($removed . ' doublon(s) seraient supprimés. Relancer avec --apply pour appliquer.');

            return Command::SUCCESS;
        }

        $this->entityManager->flush();
        $io->success($removed . ' doublon(s) supprimé(s).');

        return Command::SUCCESS;
    }
}
