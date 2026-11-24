<?php

declare(strict_types=1);

// Cron OVH : le formulaire n'accepte qu'un fichier PHP sans argument, on fixe donc les commandes ici.
// Planification : langage PHP 8.5, fichier larpmanager/bin/cron-purge-logs.php, une fois par jour.
// Enchaîne les rétentions : refus d'accès (error_acces.log, 31 jours), historique de connexion (IP anonymisées à 30 jours, lignes supprimées
// à 12 mois), log_action (lignes de plus de 5 ans) puis les blocages d'IP expirés depuis plus de 30 jours. Le code retour est le plus élevé.
// Pour une simulation (aucune suppression), mettre PURGE_LOGS_APPLY à false.

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Runtime\RunnerInterface;

const PURGE_LOGS_APPLY = true;

const PURGE_LOGS_COMMANDS = ['app:login-history:purger', 'app:log-action:purger', 'app:acces-log:purger', 'app:ip-bloquee:purger'];

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context): RunnerInterface {
    $application = new Application(new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']));
    $application->setAutoExit(false);

    // Le runtime n'accepte qu'un objet exécutable en retour : ce runner enchaîne les commandes.
    return new readonly class($application) implements RunnerInterface {
        public function __construct(
            private Application $application,
        ) {
        }

        public function run(): int
        {
            $exitCode = 0;
            foreach (PURGE_LOGS_COMMANDS as $command) {
                $input = new ArrayInput(['command' => $command, '--apply' => PURGE_LOGS_APPLY, '--no-interaction' => true]);
                $exitCode = max($exitCode, $this->application->run($input, new ConsoleOutput()));
            }

            return $exitCode;
        }
    };
};
