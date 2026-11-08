<?php

declare(strict_types=1);

// Cron OVH : le formulaire n'accepte qu'un fichier PHP sans argument, on fixe donc la commande ici.
// Planification : langage PHP 8.5, fichier larpmanager/bin/cron-purge-refresh-tokens.php (une fois par jour ou par semaine).
// Supprime les refresh tokens de l'API expirés (table refresh_tokens), sinon elle grossit sans fin.

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

$_SERVER['argv'] = [__FILE__, 'gesdinet:jwt:clear', '--no-interaction'];
$_SERVER['argc'] = count($_SERVER['argv']);

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    return new Application($kernel);
};
