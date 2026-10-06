#!/bin/sh
set -e

COMPOSER_MARKER="var/.composer-installed"

echo "Waiting for database..."
until php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; do
    sleep 2
done
echo "Database is ready."

if [ ! -f "$COMPOSER_MARKER" ]; then
    echo "No composer marker found: installing dependencies."
    composer install --no-interaction --optimize-autoloader
    touch "$COMPOSER_MARKER"
else
    echo "Composer marker found: skipping composer install."
fi

# If no migrations are recorded, the DB was just initialized from SQL files.
# Mark all existing migrations as done so only future ones will run.
MIGRATION_COUNT=$(php bin/console dbal:run-sql "SELECT COUNT(*) FROM doctrine_migration_versions" 2>/dev/null | grep -oE '[0-9]+' | head -1 || echo "0")
if [ -z "$MIGRATION_COUNT" ] || [ "$MIGRATION_COUNT" = "0" ]; then
    echo "No migrations recorded: syncing metadata and marking all as applied."
    php bin/console doctrine:migrations:sync-metadata-storage --no-interaction
    php bin/console doctrine:migrations:version --add --all --no-interaction
fi

echo "Running pending migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Warming up Symfony cache..."
php bin/console cache:warmup --no-debug

exec docker-php-entrypoint "$@"
