<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Gn;
use App\Entity\LogAction;
use App\Entity\Personnage;
use App\Enum\LogActionType;
use App\Enum\TriggerType;
use App\Repository\GnRepository;
use App\Service\Competence\LitteratureTriggerRattrapage;
use App\Service\PersonnageService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:litterature:rattraper-triggers', description: 'Recrée les triggers sort et potion manquants des personnages avec Littérature (simulation par défaut)')]
class LitteratureRattraperTriggers extends Command
{
    public function __construct(
        protected readonly PersonnageService $personnageService,
        protected readonly LitteratureTriggerRattrapage $rattrapage,
        protected readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('gn', InputArgument::OPTIONAL, 'Id du GN si ce n\'est pas le prochain', default: null)
            ->addOption('depuis', null, InputOption::VALUE_REQUIRED, 'Un choix déjà fait depuis cette date (Y-m-d) compte comme consommé (défaut : fin du GN précédent)')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Crée réellement les triggers (sinon simulation)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');

        /** @var GnRepository $gnRepository */
        $gnRepository = $this->entityManager->getRepository(Gn::class);
        $gn = $input->getArgument('gn') ? $gnRepository->find($input->getArgument('gn')) : $gnRepository->findNext();
        if (!$gn instanceof Gn) {
            $io->error('GN not found');

            return Command::INVALID;
        }

        $since = $this->resolveSince($gn, $input->getOption('depuis'));
        if (false === $since) {
            $io->error('Date --depuis invalide (format Y-m-d)');

            return Command::INVALID;
        }

        $io->title(\sprintf('Rattrapage des triggers Littérature pour le GN "%s"', $gn->getLabel()));
        $io->writeln($since ? 'Choix déjà faits pris en compte depuis le ' . $since->format('d/m/Y') : 'Aucune borne de date pour les choix déjà faits');

        $skipped = ['trigger' => 0, 'choix' => 0];
        $rows = [];
        foreach ($this->rattrapage->findMissing($gn, $since, $skipped) as [$personnage, $level, $tag]) {
            $rows[] = [$personnage->getId(), (string) $personnage->getNom(), $level->value, $tag->value];

            if ($apply) {
                $this->personnageService->addPersonnageTrigger($personnage, $tag, false, $gn);
                $this->log($personnage, $tag, $gn);
            }
        }

        if ($rows) {
            $io->table(['Personnage', 'Nom', 'Littérature', 'Trigger manquant'], $rows);
        }

        $io->writeln(\sprintf('Ignorés : %d (trigger présent), %d (choix déjà fait)', $skipped['trigger'], $skipped['choix']));

        if (!$apply) {
            $io->note(\sprintf('Simulation : %d trigger(s) seraient créés. Relancer avec --apply pour les créer.', \count($rows)));

            return Command::SUCCESS;
        }

        $this->entityManager->flush();
        $io->success(\sprintf('%d trigger(s) créés', \count($rows)));

        return Command::SUCCESS;
    }

    /** @return DateTime|false|null null = pas de borne, false = date invalide */
    private function resolveSince(Gn $gn, mixed $option): DateTime|false|null
    {
        if (!\is_string($option) || '' === $option) {
            return $this->rattrapage->getDefaultSince($gn);
        }

        $date = DateTime::createFromFormat('!Y-m-d', $option);

        return $date instanceof DateTime ? $date : false;
    }

    private function log(Personnage $personnage, TriggerType $tag, Gn $gn): void
    {
        $logAction = new LogAction();
        $logAction->setDate(new DateTime());
        $logAction->setType(LogActionType::TRIGGER_REPARATION);
        $logAction->setData(['personnage_id' => $personnage->getId(), 'tag' => $tag->value, 'gn_id' => $gn->getId()]);
        $this->entityManager->persist($logAction);
    }
}
