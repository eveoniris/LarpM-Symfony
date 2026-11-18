<?php

declare(strict_types=1);

// Cron OVH : le formulaire n'accepte qu'un fichier PHP sans argument, on fixe donc la commande ici.
// Planification : langage PHP 8.5, fichier larpmanager/bin/cron-gn-timeline.php, une fois par jour.
// Exécute les étapes automatiques échues de la timeline des GN. Tant que la timeline n'est pas codée, la commande ne fait rien.
// Pour une simulation (aucune écriture), mettre GN_TIMELINE_APPLY à false.

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

const GN_TIMELINE_APPLY = true;

$_SERVER['argv'] = array_values(array_filter([__FILE__, 'app:gn-timeline:run', GN_TIMELINE_APPLY ? '--apply' : null, '--no-interaction']));
$_SERVER['argc'] = count($_SERVER['argv']);

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new Application($kernel);
};
