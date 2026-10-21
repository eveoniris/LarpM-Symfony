<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AttributeType;
use App\Entity\Personnage;
use App\Entity\Priere;
use App\Service\PrieresCorrectionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Ramène les prières des personnages vivants au quota de leurs compétences : retire les prières en trop
 * (sphère hors religion Fervent/Fanatique, ou niveau sans compétence Prêtrise) et ajoute les manquantes
 * quand le personnage a une religion principale Fervent/Fanatique.
 */
#[AsCommand(name: 'app:prieres:corriger', description: 'Ramène les prières des personnages au quota de leurs compétences (simulation par défaut)')]
class PrieresCorriger extends Command
{
    private const int LOT = 200;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PrieresCorrectionService $correction,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Applique réellement les retraits et ajouts (sinon simulation)')
            ->addOption('personnage', null, InputOption::VALUE_REQUIRED, 'Limite à un personnage (id)')
            ->addOption('classe', null, InputOption::VALUE_REQUIRED, 'Limite à une classe (id), ex. 21 pour les prêtres')
            ->addOption('sans-orphelines', null, InputOption::VALUE_NONE, 'Ne retire pas les prières des niveaux sans compétence Prêtrise')
            ->addOption('sans-ajouts', null, InputOption::VALUE_NONE, 'N\'ajoute pas les prières manquantes')
            ->addOption('limite', null, InputOption::VALUE_REQUIRED, 'Nombre de lignes listées (0 = toutes)', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');
        $avecOrphelines = !$input->getOption('sans-orphelines');
        $avecAjouts = !$input->getOption('sans-ajouts');

        $rows = [];
        $bilan = ['retirer' => 0, 'ajouter' => 0, 'ambigu' => 0];
        $personnagesTouches = 0;

        foreach (array_chunk($this->ids($input), self::LOT) as $lot) {
            foreach ($this->entityManager->getRepository(Personnage::class)->findBy(['id' => $lot]) as $personnage) {
                $touche = false;
                foreach ($this->correction->analyser($personnage, $avecOrphelines) as $action) {
                    if ('ajouter' === $action['action'] && !$avecAjouts) {
                        continue;
                    }

                    $touche = true;
                    ++$bilan[$action['action']];
                    $rows[] = [
                        $personnage->getId(),
                        $personnage->getNom(),
                        $personnage->getClasse()->getId(),
                        $action['action'],
                        $action['niveau'],
                        $action['priere']?->getLabel() ?? '-',
                        $action['priere']?->getSphere()?->getLabel() ?? '-',
                        $action['motif'],
                    ];

                    if ($apply) {
                        $this->appliquer($personnage, $action);
                    }
                }

                $personnagesTouches += $touche ? 1 : 0;
            }

            if ($apply) {
                $this->entityManager->flush();
            }
            $this->entityManager->clear();
        }

        if ([] === $rows) {
            $io->success('Aucune prière à corriger.');

            return Command::SUCCESS;
        }

        $limite = max(0, (int) $input->getOption('limite'));
        $io->table(['Id', 'Personnage', 'Classe', 'Action', 'Niveau', 'Prière', 'Sphère', 'Motif'], 0 === $limite ? $rows : \array_slice($rows, offset: 0, length: $limite));
        $io->table(['Retraits', 'Ajouts', 'Cas ambigus (rien fait)', 'Personnages'], [[$bilan['retirer'], $bilan['ajouter'], $bilan['ambigu'], $personnagesTouches]]);

        if (!$apply) {
            $io->note("Simulation : rien n'a été écrit. Relancer avec --apply pour appliquer.");

            return Command::SUCCESS;
        }

        $io->success($bilan['retirer'] . ' prière(s) retirée(s), ' . $bilan['ajouter'] . ' ajoutée(s).');

        return Command::SUCCESS;
    }

    /** @param array{action: string, niveau: int, priere: ?Priere, motif: string} $action */
    private function appliquer(Personnage $personnage, array $action): void
    {
        $priere = $action['priere'];
        if (null === $priere) {
            return;
        }

        if ('retirer' === $action['action']) {
            $personnage->removePriere($priere);

            return;
        }

        $priere->addPersonnage($personnage);
    }

    /** @return list<int> */
    private function ids(InputInterface $input): array
    {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select('DISTINCT p.id')
            ->from(Personnage::class, 'p')
            ->leftJoin('p.prieres', 'pr')
            ->leftJoin('p.competences', 'c')
            ->leftJoin('c.competenceAttributes', 'ca')
            ->leftJoin('ca.attributeType', 'at')
            ->where('p.vivant = true')
            ->andWhere('pr.id IS NOT NULL OR at.label IN (:labels)')
            ->setParameter('labels', AttributeType::$PRIERES)
            ->orderBy('p.id', 'ASC');

        if (null !== $input->getOption('personnage')) {
            $qb->andWhere('p.id = :id')->setParameter('id', (int) $input->getOption('personnage'));
        }
        if (null !== $input->getOption('classe')) {
            $qb->andWhere('IDENTITY(p.classe) = :classe')->setParameter('classe', (int) $input->getOption('classe'));
        }

        return array_map(static fn (array $row): int => (int) $row['id'], $qb->getQuery()->getArrayResult());
    }
}
