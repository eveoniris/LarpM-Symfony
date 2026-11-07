<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\RulebookRegle;
use App\Enum\RulebookStatut;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:rulebook:revue', description: 'Liste les règles à faire, à confirmer ou buguées (revue périodique), les plus anciennes d\'abord')]
class RulebookRevue extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('jours', null, InputOption::VALUE_REQUIRED, 'Ne garde que les règles non modifiées depuis au moins N jours', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $jours = max(0, (int) $input->getOption('jours'));

        /** @var list<RulebookRegle> $regles */
        $regles = $this->entityManager
            ->getRepository(RulebookRegle::class)
            ->createQueryBuilder('r')
            ->andWhere('r.statut IN (:statuts)')
            ->setParameter('statuts', [RulebookStatut::TODO, RulebookStatut::A_CONFIRMER, RulebookStatut::BUGUE])
            ->orderBy('r.updatedAt', 'ASC')
            ->getQuery()
            ->getResult();

        $maintenant = new DateTime();
        $lignes = [];
        foreach ($regles as $regle) {
            $age = (int) $regle->getUpdatedAt()->diff($maintenant)->format('%a');
            if ($age < $jours) {
                continue;
            }

            $lignes[] = [$regle->getCode(), $regle->getStatut()->label(), $regle->getTitre(), $regle->getUpdatedAt()->format('d/m/Y'), $age . ' j'];
        }

        $io->title('Revue du rulebook');
        if ([] === $lignes) {
            $io->success('Aucune règle à revoir.');

            return Command::SUCCESS;
        }

        $io->table(['Code', 'Statut', 'Règle', 'Modifiée le', 'Âge'], $lignes);
        $io->text(\sprintf('%d règle(s) à revoir.', \count($lignes)));

        return Command::SUCCESS;
    }
}
