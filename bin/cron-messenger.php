<?php

declare(strict_types=1);

// Cron OVH : le formulaire n'accepte qu'un fichier PHP sans argument, on fixe donc les commandes ici.
// Planification : langage PHP 8.5, fichier larpmanager/bin/cron-messenger.php, à la fréquence la plus courte
// autorisée par l'hébergement (toutes les heures sur OVH : le champ minute est grisé, d'où une limite de 25 min / 1000 messages par passage).
// 1. Consomme la file "async" (e-mails de messages) puis s'arrête : un seul passage borné en temps et en nombre.
//    MESSENGER_TIME_LIMIT doit rester inférieur à l'intervalle du cron pour éviter deux workers simultanés.
//    Les échecs sont rejoués 3 fois puis rangés dans la file "failed" (voir /admin/messenger).
// 2. Lance app:messenger:alerte : mail aux super admins si des messages sont en échec ou si la file s'accumule.

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Runtime\RunnerInterface;

const MESSENGER_TIME_LIMIT = 1500;

const MESSENGER_MESSAGE_LIMIT = 1000;

const MESSENGER_MEMORY_LIMIT = '128M';

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
            $output = new ConsoleOutput();
            $consume = new ArrayInput([
                'command' => 'messenger:consume',
                'receivers' => ['async'],
                '--time-limit' => MESSENGER_TIME_LIMIT,
                '--limit' => MESSENGER_MESSAGE_LIMIT,
                '--memory-limit' => MESSENGER_MEMORY_LIMIT,
                '--no-interaction' => true,
            ]);
            $exitCode = $this->application->run($consume, $output);

            $alerte = new ArrayInput(['command' => 'app:messenger:alerte', '--no-interaction' => true]);

            return max($exitCode, $this->application->run($alerte, $output));
        }
    };
};
