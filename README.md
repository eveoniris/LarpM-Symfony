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

