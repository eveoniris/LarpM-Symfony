#!/bin/sh
set -e

mkdir -p var

# Composer
COMPOSER_HASH=$(md5sum composer.lock 2>/dev/null | cut -d' ' -f1)
if [ ! -f "vendor/autoload.php" ] || [ "$(cat var/.composer.hash 2>/dev/null)" != "$COMPOSER_HASH" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction
    echo "$COMPOSER_HASH" > var/.composer.hash
fi

# AssetMapper
if [ -f "importmap.php" ]; then
    IMPORTMAP_HASH=$(md5sum importmap.php | cut -d' ' -f1)
    if [ "$(cat var/.importmap.hash 2>/dev/null)" != "$IMPORTMAP_HASH" ]; then
        echo "Updating importmap assets..."
        php bin/console importmap:install || echo "WARN: importmap:install failed"
        echo "$IMPORTMAP_HASH" > var/.importmap.hash
    fi
fi

# Migrations
## If no migrations are recorded, the DB was just initialized from SQL files.
## Mark all existing migrations as done so only future ones will run.
MIGRATION_COUNT=$(php bin/console dbal:run-sql "SELECT COUNT(*) FROM doctrine_migration_versions" 2>/dev/null | grep -oE '[0-9]+' | head -1 || echo "0")
if [ -z "$MIGRATION_COUNT" ] || [ "$MIGRATION_COUNT" = "0" ]; then
    echo "No migrations recorded: syncing metadata and marking all as applied."
    php bin/console doctrine:migrations:sync-metadata-storage --no-interaction
    php bin/console doctrine:migrations:version --add --all --no-interaction
fi

echo "Running pending migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec docker-php-entrypoint "$@"
