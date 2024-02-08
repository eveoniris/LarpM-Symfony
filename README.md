# larpManager

Live Action Role Playing Manager

This tool was made for manage player subscription, player background and many other things on LARP event.

# Install
You need
- Docker : https://docs.docker.com/engine/install/
- Docker compose : https://docs.docker.com/compose/install/

1) Download the source:
```
git clone git@github.com:eveoniris/LarpM-Symfony.git
```

2) Go to your project folder and type:
```
docker compose build
```

3) After the build is done type:
```
docker compose up -d
```

4) Goto : http://localhost:8082/

5) Connection your Database IDE :

Use a mariadb as driver (prod use Mysql but Mysql Docker Image is only linux/x86_64) 
(You can choose another database by creating a local docker-compose file)

- host : localhost
- port : 30202
- user : admin
- pass : password
- database : larpm 

6) Symfony command
All symfony commands are availble inside the container. You can access to it by using Docker Desktop App or the commande :
`docker exec -it larpm-symfony-app-1 bash`

You can also install and use your local symfony as the whole project folder is mounted as a volume

7) Webpack command Js et CSS

`symfony composer req encore`

`npm install node-sass sass-loader --save-dev`

`npm install bootstrap @popperjs/core bs-custom-file-input --save-dev`

Récuppérer jquery
`npm install --save-dev jquery`

`npm install --save-dev bootstrap-select`
`npm install tinymce`
`npm install file-loader@^6.0.0`

Pour relancer le app.js et css
`symfony run npm run dev`

Pour ne pas avoir à recompiler en cas de changement
`symfony run -d npm run watch`

8) Tips
Si le clear:cache échoue :
   php -d memory_limit=-1 bin/console cache:clear

Export de la base de donnée
`docker exec -it larpm-symfony-database-1 mariadb-dump -uadmin -ppassword --opt larpm > backup.sql`

Copier le fichier dans le container 
`docker cp backup.sql larpm-symfony-database-1:/tmp/backup.sql`

Import de la base de donnée (le fichier doit être dans le container)
`docker exec -it larpm-symfony-database-1 /bin/sh -c "mariadb -uadmin -ppassword larpm < /tmp/backup.sql"`

9) mise à jour
Commande pour maj aller sur le container (voir point 6) puis faire :
- docker compose build
- docker compose pull
- composer install
- composer recipes:install
