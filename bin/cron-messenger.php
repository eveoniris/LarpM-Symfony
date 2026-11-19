<?php

declare(strict_types=1);

// Cron OVH : le formulaire n'accepte qu'un fichier PHP sans argument, on fixe donc la commande ici.
// Planification : langage PHP 8.5, fichier larpmanager/bin/cron-messenger.php, à la fréquence la plus courte
// autorisée par l'hébergement (toutes les heures sur OVH : le champ minute est grisé, d'où une limite de 25 min / 1000 messages par passage).
// Consomme la file "async" (e-mails de messages) puis s'arrête : un seul passage borné en temps et en nombre.
// MESSENGER_TIME_LIMIT doit rester inférieur à l'intervalle du cron pour éviter deux workers simultanés.
// Les échecs sont rejoués 3 fois puis rangés dans la file "failed" (voir messenger:failed:show).

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

const MESSENGER_TIME_LIMIT = 1500;

const MESSENGER_MESSAGE_LIMIT = 1000;

const MESSENGER_MEMORY_LIMIT = '128M';

$_SERVER['argv'] = [
    __FILE__,
    'messenger:consume',
    'async',
    '--time-limit=' . MESSENGER_TIME_LIMIT,
    '--limit=' . MESSENGER_MESSAGE_LIMIT,
    '--memory-limit=' . MESSENGER_MEMORY_LIMIT,
    '--no-interaction',
];
$_SERVER['argc'] = count($_SERVER['argv']);

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new Application($kernel);
};
