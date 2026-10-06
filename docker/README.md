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
| App (HTTPS) | https://larpmanager.test | URL principale (setup one-time requis) |
| App (HTTP fallback) | http://localhost:8080 | Sans setup, sans admin |
| Base de données | localhost:30202 | MySQL 8.4 — user `admin` / pass `password` |
| Mailpit | http://localhost:8025 | Interface emails de dev |

---

## Setup one-time — `https://larpmanager.test`

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

## Fallback sans droits admin — `http://localhost:8080`

Si vous ne pouvez pas modifier le fichier `hosts` ou importer un certificat, utilisez le mode HTTP sur le port 8080 :

```bash
cp docker/.env.dev.local.dist .env.dev.local
docker compose up -d
```

L'app sera accessible sur **http://localhost:8080**.

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
