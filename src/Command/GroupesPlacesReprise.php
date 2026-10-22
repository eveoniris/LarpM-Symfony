<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Gn;
use App\Entity\GroupeGn;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Repère les groupes PJ dont les membres dépassent déjà les places calculées automatiquement
 * (places par défaut du GN + 2 par fief) et fixe leur limite manuelle au nombre
 * de membres actuel. Les autres groupes gardent le calcul automatique.
 */
#[AsCommand(name: 'app:groupes:places-reprise', description: 'Fixe la limite de places des groupes dont les membres dépassent le calcul automatique (simulation par défaut)')]
class GroupesPlacesReprise extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('gn', null, InputOption::VALUE_REQUIRED, 'Id du GN (par défaut le prochain GN)')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Écrit réellement les limites (sinon simulation)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $gnRepository = $this->entityManager->getRepository(Gn::class);
        $gn = $input->getOption('gn') ? $gnRepository->find((int) $input->getOption('gn')) : $gnRepository->findNext();

        if (!$gn instanceof Gn) {
            $io->error('GN introuvable.');

            return Command::FAILURE;
        }

        $apply = (bool) $input->getOption('apply');
        $rows = [];
        $aFixer = 0;
        $dejaManuels = 0;

        /** @var list<GroupeGn> $groupesGn */
        $groupesGn = $this->entityManager
            ->getRepository(GroupeGn::class)
            ->createQueryBuilder('gg')
            ->innerJoin('gg.groupe', 'g')
            ->addSelect('g')
            ->where('gg.gn = :gn')
            ->andWhere('g.pj = true OR g.pj IS NULL')
            ->setParameter('gn', $gn)
            ->orderBy('g.numero')
            ->getQuery()
            ->getResult();

        foreach ($groupesGn as $groupeGn) {
            $groupe = $groupeGn->getGroupe();
            $membres = $groupeGn->getNombreMembres();
            $calculee = $groupe->getPlaceCalculee($gn);
            $manuelle = $groupeGn->getPlacesMax();

            if (null !== $manuelle) {
                ++$dejaManuels;
                if ($membres > $manuelle) {
                    $rows[] = [$groupe->getNumero(), $groupe->getNom(), $calculee, $manuelle, $membres, 'limite manuelle dépassée (rien fait)'];
                }

                continue;
            }

            if ($membres <= $calculee) {
                continue;
            }

            ++$aFixer;
            $rows[] = [$groupe->getNumero(), $groupe->getNom(), $calculee, '-', $membres, 'fixer la limite à ' . $membres];

            if ($apply) {
                $groupeGn->setPlacesMax($membres);
            }
        }

        $io->title('Places des groupes PJ, GN ' . $gn->getLabel());

        if ([] === $rows) {
            $io->success('Aucun groupe à reprendre : tous les groupes tiennent dans leurs places.');

            return Command::SUCCESS;
        }

        $io->table(['N°', 'Groupe', 'Places calculées', 'Limite manuelle', 'Membres', 'Action'], $rows);
        $io->table(['Limites à fixer', 'Déjà manuels'], [[$aFixer, $dejaManuels]]);

        if (!$apply) {
            $io->note("Simulation : rien n'a été écrit. Relancer avec --apply pour fixer les limites.");

            return Command::SUCCESS;
        }

        $this->entityManager->flush();
        $io->success($aFixer . ' limite(s) fixée(s).');

        return Command::SUCCESS;
    }
}
