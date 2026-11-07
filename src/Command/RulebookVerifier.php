<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\RulebookRegle;
use App\Service\Rulebook\RulebookVerificateur;
use App\Service\Rulebook\TestsScanner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:rulebook:verifier', description: 'Vérifie le lien entre le rulebook et les tests (règles sans test, références cassées, codes inconnus)')]
class RulebookVerifier extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TestsScanner $scanner,
        private readonly RulebookVerificateur $verificateur,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('strict', null, InputOption::VALUE_NONE, 'Échoue (code 1) si une référence de test est cassée ou si un test cite un code de règle inconnu');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var list<RulebookRegle> $regles */
        $regles = $this->entityManager->getRepository(RulebookRegle::class)->findAll();
        $scan = $this->scanner->scanner($this->projectDir . '/tests', $this->projectDir);
        $resultat = $this->verificateur->verifier($regles, $scan['classes'], $scan['codes']);

        $io->title('Rulebook et tests');
        $io->text(\sprintf('%d règle(s), %d code(s) cité(s) en tag dans les tests.', \count($regles), \count($scan['codes'])));

        if ([] !== $resultat->sansTest) {
            $io->warning('Règles valides ou fixées sans aucun test : ' . implode(', ', $resultat->sansTest));
        }

        foreach ($resultat->referencesCassees as $code => $references) {
            $io->error(\sprintf('%s : référence(s) de test introuvable(s) : %s', $code, implode(', ', $references)));
        }

        foreach ($resultat->codesInconnus as $code => $fichiers) {
            $io->error(\sprintf('Code de règle inconnu "%s" cité par : %s', $code, implode(', ', $fichiers)));
        }

        if (!$resultat->aDesErreurs() && [] === $resultat->sansTest) {
            $io->success('Tout est cohérent.');
        }

        return $resultat->aDesErreurs() && (bool) $input->getOption('strict') ? Command::FAILURE : Command::SUCCESS;
    }
}
