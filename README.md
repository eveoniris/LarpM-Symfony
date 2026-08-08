# larpManager

Live action role-playing game (LARP) Manager

This tool was made for manage player subscription, player background and many other things on LARP event.

Gestionnaire de jeu de rôle grandeur nature.

## Installation

Vous aurez besoin de:

- Git : https://git-scm.com/downloads
- Docker : https://docs.docker.com/engine/install/
- Docker compose : https://docs.docker.com/compose/install/

### 1- Source

```bash
git clone git@github.com:eveoniris/LarpM-Symfony.git larpmanager
```

### 2- Compiler le projet

Normalement inutile car gérer par le point suivant. Il pourra être parfois requis de recompiler. Voici
donc la commande.

```bash
docker compose build
```

### 3- Lancer le projet

```bash
docker compose up -d
```

### 4- Charger une première fois les librairies externe (vendor)

```bash
docker compose exec webserver composer install
```

### 5- Accéder au site

Se rendre sur [localhost/](http://localhost/)

Un reverse proxy sur [larpmanager.test](http://larpmanager.test) est aussi disponible si vous avez configuré votre /etc/hosts:

```text
127.0.0.1 larpmanager.test
```

### 6- Connection d'un IDE à la base de donnée

On utilise la même version que sur le serveur de production : Mysql 8.0+

```
- host : localhost
- port : 3306
- user : admin
- pass : password
- database : larpm
```

### 7- Voir les mails

Tous les mails sont catché par mailpit et consultable sur : http://localhost:8025/

### 8- Commandes Symfony CLI

Les commandes symfony sont disponibles via:

```bash
   docker compose exec webserver symfony
```

Ou via:

```bash
docker compose exec webserver php bin/console
```

## Voir les logs

```bash
docker compose logs
docker compose logs webserver
docker compose logs mailer
docker compose logs database
```

## Base de donnée

Lors du docker compose up -d, est installé pour la première fois (tant que /docker/db/data est vide) les fichiers
contenus dans docker/db/initData par ordre alphabetique

Export de la base de donnée

```bash
docker exec -it larpmanager-database-1 mysqldump -uadmin -ppassword --opt larpm > backup.sql
```

Pour importer, créer un fichier puis le copier dans le container

```bash
docker cp backup.sql larpmanager-database-1:/tmp/backup.sql
```

Import de la base de donnée (le fichier doit être dans le container)

```bash
docker exec -it larpmanager-database-1 /bin/sh -c "mysql -uadmin -ppassword larpm < /tmp/backup.sql"
```

### Export des données uniquement

On utilise `--no-create-info` avant d'indiquer la base

### Export de la structure uniquement

On utilise `--no-data` avant d'indiquer la base

Noter: `--compact` pour un fichier sans commentaire, et `--no-create-db` pour éviter la partie création

## Tests

### Lancer les tests

```bash
# Tous les tests
docker compose exec webserver ./vendor/bin/phpunit

# Un fichier précis
docker compose exec webserver ./vendor/bin/phpunit tests/Unit/Service/ConditionsServiceTest.php

# Un groupe (unit ou integration)
docker compose exec webserver ./vendor/bin/phpunit --group unit
docker compose exec webserver ./vendor/bin/phpunit --group integration

# Avec sortie lisible (noms des tests)
docker compose exec webserver ./vendor/bin/phpunit --testdox
```

### Structure des tests

```text
tests/
├── bootstrap.php                        # Initialisation PHPUnit (charge .env.test)
├── Factory/                             # Foundry factories (données de test)
│   ├── PersonnageFactory.php
│   ├── GroupeFactory.php
│   ├── CompetenceFactory.php
│   └── ...                              # une factory par entité Doctrine
├── Unit/                                # Tests unitaires (pas de DB, pas de Kernel)
│   ├── Entity/
│   ├── Enum/
│   └── Service/
└── Integration/                         # Tests d'intégration (Kernel + DB réelle)
    └── Service/
        ├── CompetenceServiceTest.php
        └── SanctuaireEffectTest.php
```

### Outils utilisés

| Outil                       | Rôle                                                                                              |
| --------------------------- | ------------------------------------------------------------------------------------------------- |
| **PHPUnit 11**              | Framework de test                                                                                 |
| **DAMA DoctrineTestBundle** | Enveloppe chaque test dans une transaction → rollback automatique, isolation sans reset de schéma |
| **zenstruck/foundry**       | Factories pour créer des entités Doctrine en test                                                 |

### Écrire un test unitaire

Les tests unitaires n'ont pas besoin de la base de données ni du Kernel Symfony.
Ils héritent de `PHPUnit\Framework\TestCase` et utilisent des stubs/mocks.

```php
// tests/Unit/Service/MonServiceTest.php
namespace App\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
class MonServiceTest extends TestCase
{
    public function testQuelqueChose(): void
    {
        $service = new MonService();
        self::assertTrue($service->maMethode());
    }
}
```

### Écrire un test d'intégration

Les tests d'intégration démarrent le Kernel Symfony et accèdent à la vraie base de données.
Ils héritent de `Symfony\Bundle\FrameworkBundle\Test\KernelTestCase` et utilisent le trait `Factories`.

Le **DAMA bundle** encapsule chaque test dans une transaction Doctrine qui est rollbackée
automatiquement à la fin — pas besoin de vider/recréer le schéma entre les tests.

```php
// tests/Integration/Service/MonServiceTest.php
namespace App\Tests\Integration\Service;

use App\Service\MonService;
use App\Tests\Factory\PersonnageFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group integration
 */
class MonServiceTest extends KernelTestCase
{
    use Factories; // PAS ResetDatabase — DAMA gère le rollback

    private MonService $service;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->service = $container->get(MonService::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
    }

    public function testAvecDonneesReelles(): void
    {
        $personnage = PersonnageFactory::createOne(['xp' => 50]);

        $result = $this->service->maMethode($personnage->_real());

        self::assertTrue($result);
    }
}
```

### Créer une Factory Foundry

Les factories se trouvent dans `tests/Factory/`. Chaque factory correspond à une entité Doctrine.

```php
// tests/Factory/MonEntiteFactory.php
namespace App\Tests\Factory;

use App\Entity\MonEntite;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/** @extends PersistentProxyObjectFactory<MonEntite> */
final class MonEntiteFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return MonEntite::class;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'nom'    => self::faker()->words(2, true),
            'actif'  => true,
            // les relations vers d'autres entités s'expriment avec leur factory :
            'groupe' => GroupeFactory::new(),
        ];
    }
}
```

Utilisation dans un test :

```php
// Créer avec les valeurs par défaut
$entite = MonEntiteFactory::createOne();

// Surcharger certaines valeurs
$entite = MonEntiteFactory::createOne(['nom' => 'Valeur fixe', 'actif' => false]);

// Accéder à l'entité réelle (unwrap le proxy Foundry)
$entite->_real()->getNom();

// Créer plusieurs entités
$entites = MonEntiteFactory::createMany(5);
```

## Notes importantes

- **`APP_ENV=test`** est forcé par `phpunit.dist.xml` et `.env.test` — la base de données utilisée
  est `larpm_test` (suffixe `_test` ajouté automatiquement par Doctrine).
- Les **dépréciations** (marqueur `D` dans la sortie) sont des avertissements PHP 8.5 / Doctrine
  vendor-level, non bloquants.
- Des exemples de tests API via fichiers HTTP se trouvent dans `tests/rest/` (ex: `tests/rest/stats.http`)
  et peuvent être exécutés depuis un IDE compatible.

## Commande utile

### Composer

Commande pour maj aller sur le container (voir point 6) puis faire :

```
docker compose run --rm composer install
docker compose run --rm composer recipes:install
```

Au besoin pour mettre à jour le recipes :

```
docker compose run --rm composer recipes:update
```

### Vider le cache

Nettoyer et recharger le cache

```bash
docker compose exec webserver php bin/console cache:clear
```

```bash
docker compose exec webserver php bin/console cache:warmup
```

Si le clear:cache échoue :

```bash
php -d memory_limit=-1 bin/console cache:clear
```

### Exécuter les migrations Doctrine

```bash
docker compose exec webserver php bin/console doctrine:migrations:migrate
```

### Lancer un serveur de développement (si nécessaire)

```bash
docker compose exec webserver php bin/console server:run
```

### Lancer un worker Messenger

```bash
docker compose exec webserver php bin/console messenger:consume async -vv
```

### Compiler les assets

Voir https://symfony.com/doc/current/frontend/asset_mapper.html

```bash
docker compose exec webserver php bin/console asset-map:compile
```

### Debug les assets

```bash
docker compose exec webserver php bin/console debug:asset-map
```

### Maj ou install de l'importmap

```bash
docker compose exec webserver php bin/console importmap:update
docker compose exec webserver php bin/console importmap:install
```

### Maj service du docker compose

Pour mettre à jour les service définis dans le docker-compose.yml si une nouvelle version est disponible. Il faudra
jouer la commande

```bash
docker compose pull
```

## Soucis possible

Si vous avez un souci pour vous connecter

fair un `docker compose ps` voir si un container est en "restarting"

Si oui faire un `docker compose down -v` puis faire un `docker compose up -d` et vérifier les logs.

### Exemple pour mettre à jour les librairies de composer

Mettre à jour composer.json sur la version visé puis:

```bash
docker compose run --rm composer update "doctrine/*" --with-all-dependencies
docker compose run --rm composer update "symfony/*" --with-all-dependencies
```

### Ajout de Imagine

Si manquant:

```bash
docker compose exec webserver symfony composer req "imagine/imagine:^1.2"
```

### Ajout de Autocomplete

Si manquant:

```bash
docker compose run --rm composer require symfony/stimulus-bundle
docker compose run --rm composer require symfony/ux-autocomplete
```

## phpCsFixer

```bash
PHP_CS_FIXER_IGNORE_ENV=1 ./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --verbose --path-mode=intersection *
docker compose exec webserver ./vendor/bin/php-cs-fixer fix --config .php-cs-fixer.dist.php
```

## Mago

@see https://mago.carthage.software/tools/overview

```bash
mago lint
mago fmt
```
