<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Territoire;
use App\Repository\TerritoireRepository;
use App\Service\GeoJson;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * # Test sans écrire en base
 * docker compose exec frankenphp php bin/console app:territoire-pre-generer-frontaliers --dry-run
 *
 * # Lancement réel (10 km par défaut)
 * docker compose exec frankenphp php bin/console app:territoire-pre-generer-frontaliers
 *
 * # Avec distance personnalisée
 * docker compose exec frankenphp php bin/console app:territoire-pre-generer-frontaliers 25
 *
 * # Forcer le recalcul des fiefs déjà renseignés
 * docker compose exec frankenphp php bin/console app:territoire-pre-generer-frontaliers --force
 *
 * Comportement :
 * - Cible uniquement les fiefs (findFiefs() : enfant d'un territoire, sans enfants propres)
 * - Skip les fiefs sans GeoJSON
 * - Skip les fiefs qui ont déjà des frontaliers, sauf avec --force
 * - Réutilise exactement le même service GeoJson et la même logique que le bouton web
 */
#[AsCommand(name: 'app:territoire-pre-generer-frontaliers', description: 'Pré-génère les frontaliers culturels par GeoJSON pour tous les fiefs')]
class TerritoirePreGenererFrontaliers extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GeoJson $geoJson,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('max_distance', InputArgument::OPTIONAL, 'Distance max en km', 10.0)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Recalcule même si des frontaliers existent déjà')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule sans écrire en base');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Pré-génération des frontaliers culturels par GeoJSON');

        $maxDistance = max(0.1, min(100.0, (float) $input->getArgument('max_distance')));
        $force = (bool) $input->getOption('force');
        $dryRun = (bool) $input->getOption('dry-run');

        $io->writeln(\sprintf('Distance max : %.1f km | Force : %s | Dry-run : %s', $maxDistance, $force ? 'oui' : 'non', $dryRun ? 'oui' : 'non'));

        /** @var TerritoireRepository $repo */
        $repo = $this->entityManager->getRepository(Territoire::class);
        $fiefs = $repo->findFiefs();

        if (!$fiefs) {
            $io->warning('Aucun fief trouvé.');

            return Command::SUCCESS;
        }

        $this->geoJson->setMaxDistance($maxDistance);

        $stats = ['traites' => 0, 'sans_geojson' => 0, 'skips' => 0, 'total_voisins' => 0];

        $io->progressStart(\count($fiefs));

        foreach ($fiefs as $fief) {
            $io->progressAdvance();

            $rawGeo = $fief->getGeojson();
            if (!$rawGeo) {
                ++$stats['sans_geojson'];
                continue;
            }

            if (!$force && !$fief->getFrontaliersCulturels()->isEmpty()) {
                ++$stats['skips'];
                continue;
            }

            $geoData = json_decode($rawGeo, true);
            $candidats = $repo->findWithGeoJson($fief->getId());
            $nbAjoutes = 0;

            foreach ($candidats as $candidat) {
                $candidatGeo = json_decode($candidat->getGeojson(), true);
                if (!$candidatGeo) {
                    continue;
                }

                if (\count($this->geoJson->comparePoints($geoData, $candidatGeo)) > 0) {
                    $fief->addFrontalierCulturel($candidat);
                    ++$nbAjoutes;
                }
            }

            if (!$dryRun) {
                $this->entityManager->persist($fief);
            }

            ++$stats['traites'];
            $stats['total_voisins'] += $nbAjoutes;
        }

        $io->progressFinish();

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->success(\sprintf(
            '%d fiefs traités, %d voisins ajoutés | %d sans GeoJSON ignorés | %d déjà renseignés skippés',
            $stats['traites'],
            $stats['total_voisins'],
            $stats['sans_geojson'],
            $stats['skips'],
        ));

        return Command::SUCCESS;
    }
}
