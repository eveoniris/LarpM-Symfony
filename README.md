# larpManager

Live action role-playing game (LARP) Manager

This tool was made for manage player subscription, player background and many other things on LARP event.

Gestionnaire de jeu de rôle grandeur nature

# Install

Vous aurez besoin de

- Git : https://git-scm.com/downloads
- Docker : https://docs.docker.com/engine/install/
- Docker compose : https://docs.docker.com/compose/install/

## 1- Source

```
git clone git@github.com:eveoniris/LarpM-Symfony.git larpmanager
```

## 2- compiler le projet

Normalement inutile car gérer par le point suivant. Il pourra être parfois requis de recompiler. Voici 
donc la commande.
```
docker compose build
```

## 3- Lancer le projet

```
docker compose up -d
```

## 3- Charger une première fois les librairies externe (vendor) 

```
docker compose run --rm composer install
```

## 4- Accéder au site
aller sur http://localhost:8080/

Vous pouvez utiliser un reverse proxy comme Caddy pour accéder plutot avec l'url http://larpmanager.test
Pour cela, il faudra modifier votre fichier "hosts" /etc/hosts et y ajouter

```
127.0.0.1 larpmanager.test
```

## 5- Connection d'un IDE à la base de donnée 

On utilise la même version que sur le serveur de production : Mysql 8.0+

```
- host : localhost
- port : 30202
- user : admin
- pass : password
- database : larpm
```

## 6- Voir les mails

Tous les mails sont catché par mailpit et consultable sur : http://localhost:8025/

## 7- commande Symfony
   
Les commandes symfony sont disponibles via :

```
   docker compose exec frankenphp symfony
```

Ou via 

```
docker compose exec frankenphp php bin/console
```

## Voir les logs 
```
docker compose logs
docker compose logs frankenphp
docker compose logs mailer
docker compose logs database
```

## Base de donnée

Export de la base de donnée
`docker exec -it larpmanager-database-1 mysqldump -uadmin -ppassword --opt larpm > backup.sql`

Pour importer, créer un fichier puis le copier dans le container
`docker cp backup.sql larpmanager-database-1:/tmp/backup.sql`

Import de la base de donnée (le fichier doit être dans le container)
`docker exec -it larpmanager-database-1 /bin/sh -c "mysql -uadmin -ppassword larpm < /tmp/backup.sql"`

# Export des données uniquement 
On utilise --no-create-info avant d'indiquer la base 

# Export de la structure uniquement
On utilise --no-data avant d'indiquer la base

Noter: --compact pour un fichier sans commentaire, et --no-create-db pour éviter la partie création 


# Divers

## Composer 
Commande pour maj aller sur le container (voir point 6) puis faire :

```
docker compose run --rm composer install
docker compose run --rm composer recipes:install
```

Au besoin pour mettre à jour le recipes : 
```
docker compose run --rm composer recipes:update
```

# Commande utile

## Vider le cache
Nettoyer et recharger le cache
```
docker compose exec frankenphp php bin/console cache:clear
```

```
docker compose exec frankenphp php bin/console cache:warmup
```

Si le clear:cache échoue :
```
php -d memory_limit=-1 bin/console cache:clear
``` 

## Exécuter les migrations Doctrine
```
docker compose exec frankenphp php bin/console doctrine:migrations:migrate
```

## Lancer un serveur de développement (si nécessaire)
```
docker compose exec frankenphp php bin/console server:run
```

## Lancer un worker Messenger
```
docker compose exec frankenphp php bin/console messenger:consume async -vv
```

## Compiler les assets
Voir https://symfony.com/doc/current/frontend/asset_mapper.html

```
docker compose exec frankenphp php bin/console asset-map:compile
```

## Debug les assets

```
docker compose exec frankenphp php bin/console debug:asset-map
```

## Maj ou install de l'importmap 

```
docker compose exec frankenphp php bin/console importmap:update
docker compose exec frankenphp php bin/console importmap:install
```

## Maj service du docker compose
Pour mettre à jour les service définis dans le docker-compose.yml si une nouvelle version est disponible. Il faudra jouer la commande

```
docker compose pull
```

# Soucis possible

Si vous avez un souci pour vous connecter

fair un `docker compose ps` voir si un container est en "restarting"

Si oui faire un `docker compose down -v` puis faire un `docker compose up -d` et vérifier les logs.

##  Exemple pour mettre à jour les librairies de composer
    Mettre à jour composer.json sur la version visé puis

- `docker compose run --rm composer update "doctrine/*" --with-all-dependencies`
- `docker compose run --rm composer update "symfony/*" --with-all-dependencies`

## Ajout de Imagine
Si manquant 

```
docker compose exec frankenphp symfony composer req "imagine/imagine:^1.2"
```

##  Ajout de Autocomplete
Si manquant

- `docker compose run --rm composer require symfony/stimulus-bundle`
- `docker compose run --rm composer require symfony/ux-autocomplete`


