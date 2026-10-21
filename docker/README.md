# Docker — LarpManager Symfony

Stack de développement basé sur [FrankenPHP](https://frankenphp.dev/) + Caddy + MySQL 8.4 + Mailpit.

## Prérequis

- Docker Desktop ≥ 4.x (Windows/macOS) ou Docker Engine ≥ 25 + Compose v2 (Linux)

## Démarrage rapide

```bash
docker compose up -d
```

Le premier démarrage exécute automatiquement (via marqueurs dans `var/`) :
1. `composer install`
2. `doctrine:schema:create`
3. `doctrine:fixtures:load`

## Services

| Service | URL / Port host | Description |
|---------|----------------|-------------|
| App (HTTP) | http://localhost | URL par défaut, sans aucun setup |
| App (HTTP, optionnel) | http://larpmanager.test | Si l'entrée `hosts` est ajoutée |
| App (HTTPS, optionnel) | https://larpmanager.test | Surcharge `.env.dev.local` + CA approuvée (voir ci-dessous) |
| Base de données | localhost:30202 | MySQL 8.4 — user `admin` / pass `password` |
| Mailpit | http://localhost:8025 | Interface emails de dev |

---

## Activer HTTPS (optionnel) — `https://larpmanager.test`

Par défaut le stack sert uniquement du HTTP. Pour activer le HTTPS :

```bash
cp docker/.env.dev.local.dist .env.dev.local
docker compose up -d
```

Caddy génère automatiquement une CA locale et émet un certificat pour `larpmanager.test`.
Il faut ajouter le domaine au fichier `hosts` de l'OS et faire approuver la CA par le navigateur.

### 1. Démarrer le stack et extraire la CA

```bash
docker compose up -d
docker compose exec webserver cat /data/caddy/pki/authorities/local/root.crt > caddy-root.crt
```

### 2. Entrée hosts + import CA selon votre OS

#### Windows

```powershell
# Ajouter le domaine (PowerShell admin requis pour le hosts file)
Add-Content C:\Windows\System32\drivers\etc\hosts "`n127.0.0.1 larpmanager.test"
```

Importer la CA — **sans droits admin** via .NET X509Store (recommandé) :
```powershell
$cert = New-Object System.Security.Cryptography.X509Certificates.X509Certificate2("caddy-root.crt")
$store = New-Object System.Security.Cryptography.X509Certificates.X509Store("Root", "CurrentUser")
$store.Open("ReadWrite")
$store.Add($cert)
$store.Close()
```

Ou via `Import-Certificate` **(admin requis)** :
```powershell
Import-Certificate -FilePath caddy-root.crt -CertStoreLocation Cert:\LocalMachine\Root
```

#### macOS (Terminal)

```bash
sudo sh -c 'echo "127.0.0.1 larpmanager.test" >> /etc/hosts'
sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain caddy-root.crt
```

#### Linux — Ubuntu/Debian

```bash
echo "127.0.0.1 larpmanager.test" | sudo tee -a /etc/hosts
sudo cp caddy-root.crt /usr/local/share/ca-certificates/caddy-larpmanager.crt
sudo update-ca-certificates
```

#### Linux — Fedora/RHEL/Arch

```bash
echo "127.0.0.1 larpmanager.test" | sudo tee -a /etc/hosts
sudo cp caddy-root.crt /etc/pki/ca-trust/source/anchors/caddy-larpmanager.crt
sudo update-ca-trust
```

#### Firefox (tous OS)

Firefox gère son propre store de certificats indépendamment du système.

Préférences → Vie privée & Sécurité → Voir les certificats → Autorités → Importer → sélectionner `caddy-root.crt` → cocher "Faire confiance à cette CA pour identifier les sites web".

### 3. Redémarrer le navigateur

Ouvrir **https://larpmanager.test** — aucune alerte de sécurité ne devrait apparaître.

> `caddy-root.crt` est local à votre poste et n'est pas commité dans le dépôt.

---

## Migration depuis l'ancien mode `:8080`

Le port `8080` n'est plus publié : `http://localhost` (port 80) le remplace. Si vous avez un ancien `.env.dev.local` issu de `.env.dev.local.dist` (`SERVER_NAME=":8080"`), supprimez-le (ou recopiez le nouveau `.dist` pour activer le HTTPS).

---

## Dépannage : erreur ou page blanche en https

Par défaut, Caddy ne sert **que du HTTP** (`auto_https off`). Une URL `https://localhost` ou `https://larpmanager.test` (favori, historique, onglet resté ouvert) ne reçoit alors aucune réponse, et la page de login semble en erreur.

1. Ouvrir l'URL en `http://localhost` (taper explicitement `http://`, dans un onglet neuf).
2. Si le navigateur repasse seul en https (HSTS mémorisé) :
   - Chrome / Edge : `chrome://net-internals/#hsts` (ou `edge://net-internals/#hsts`), puis "Delete domain security policies" pour `localhost` et `larpmanager.test`.
   - Firefox : Historique → Oublier ce site.
   - Ou utiliser une fenêtre privée.
3. Pour garder le https, activer la surcharge décrite plus haut (`cp docker/.env.dev.local.dist .env.dev.local`, puis `docker compose up -d --force-recreate webserver`).

---

## Commandes utiles

```bash
# Voir les logs du serveur web
docker compose logs -f webserver

# Ouvrir un shell dans le conteneur
docker compose exec webserver sh

# Forcer une réinstallation Composer (supprimer le marqueur)
docker compose exec webserver rm var/.composer-installed && docker compose restart webserver

# Recharger le schéma et les fixtures (reset complet)
docker compose exec webserver rm -f var/.schema-created var/.fixtures-loaded
docker compose restart webserver

# Vider le cache (var/ et vendor/ sont des volumes nommés, pas visibles côté Windows)
docker compose exec webserver php bin/console cache:clear

# Autocomplétion IDE : vendor/ n'est plus visible côté Windows, le recopier à la demande
# (copie locale en lecture seule pour l'IDE, ignorée par git ; à refaire après un composer update)
docker compose cp webserver:/app/vendor ./vendor

# Reset complet de var/ et vendor/ (recrée le cache et refait composer install)
docker compose down && docker volume rm larpm-symfony_app_var larpm-symfony_app_vendor

# Lancer les tests
docker compose exec webserver ./vendor/bin/phpunit

# Lancer une commande Symfony
docker compose exec webserver php bin/console <commande>

# Vérifier les certificats Caddy actifs
docker compose exec webserver caddy list
```

## Variables d'environnement

Le stack dev charge les fichiers dans cet ordre (le dernier a priorité) :

1. `.env` — valeurs par défaut communes
2. `.env.dev` — surcharges dev (commité)
3. `.env.dev.local` — surcharges locales (gitignored, optionnel)

Pour personnaliser votre setup sans modifier les fichiers commités, créez un `.env.dev.local`.

## Deux « prod » : OVH et Docker

| | Prod OVH (actuelle) | Prod Docker (`docker-compose.prod.yml`) |
|---|---|---|
| Déploiement | SSH via `.github/workflows/deploy.yml` | `docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d` |
| Fichiers d'environnement | `.env.prod` (commité, sans secret) + `.env.prod.local` (écrit par le workflow, gitignored), puis `composer dump-env prod` | `.env.prod-docker` (gitignored, à créer depuis `docker/.env.prod-docker.dist`) |
| Base de données | MySQL OVH (`DATABASE_URL` de `.env.prod`) | conteneur `database` |

`.env.prod` appartient donc uniquement à la prod OVH. La prod Docker ne le lit plus : si une variable manquait dans `.env.prod-docker`, l'image (qui embarque `.env.prod`) retomberait sur la base OVH, d'où le modèle complet dans `docker/.env.prod-docker.dist`.

Test local de l'image prod : `docker/scripts/prod-test.sh` génère un `.env.prod-docker` avec des valeurs de test (il écrase le fichier existant).
