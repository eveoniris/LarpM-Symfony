<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:langues:groupe', description: 'Ajoute la langue du pays du groupe aux personnages qui ne l\'ont pas (simulation par défaut)')]
class LangueGroupeRattraper extends Command
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('gn', null, InputOption::VALUE_REQUIRED, 'Limite aux participants de ce GN (identifiant)')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Applique réellement les ajouts (sinon simulation)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');
        $gnId = $input->getOption('gn');

        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select('pa.id')
            ->from(Participant::class, 'pa')
            ->innerJoin('pa.personnage', 'p')
            ->innerJoin('pa.groupeGn', 'gg');
        if (null !== $gnId) {
            $qb->andWhere('pa.gn = :gn')->setParameter('gn', (int) $gnId);
        }

        $rows = [];
        $count = 0;
        foreach (array_column($qb->getQuery()->getScalarResult(), 'id') as $participantId) {
            $participant = $this->entityManager->find(Participant::class, $participantId);
            $personnage = $participant?->getPersonnage();
            $groupe = $participant?->getGroupe();
            $personnageLangue = $personnage?->addLangueGroupe($groupe);

            if (null !== $personnageLangue) {
                $rows[] = [$personnage->getId(), $groupe->getId(), (string) $personnageLangue->getLangue()];
                if ($apply) {
                    $this->entityManager->persist($personnageLangue);
                }
            }

            // Libère la mémoire par lots (la simulation n'a rien à écrire)
            if (0 === (++$count % 100)) {
                $apply && $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $apply && $this->entityManager->flush();

        if ([] === $rows) {
            $io->success('Aucune langue de groupe manquante.');

            return Command::SUCCESS;
        }

        $io->table(['Personnage', 'Groupe', 'Langue ajoutée'], $rows);

        if (!$apply) {
            $io->note(\count($rows) . ' langue(s) seraient ajoutées. Relancer avec --apply pour appliquer.');

            return Command::SUCCESS;
        }

        $io->success(\count($rows) . ' langue(s) ajoutée(s).');

        return Command::SUCCESS;
    }
}
