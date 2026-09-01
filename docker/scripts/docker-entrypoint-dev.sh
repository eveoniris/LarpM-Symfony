#!/bin/sh
set -e

COMPOSER_MARKER="var/.composer-installed"
SCHEMA_MARKER="var/.schema-created"
FIXTURES_MARKER="var/.fixtures-loaded"

if [ ! -f "$COMPOSER_MARKER" ]; then
    echo "No composer marker found: installing dependencies."
    composer install --no-interaction --optimize-autoloader
    touch "$COMPOSER_MARKER"
else
    echo "Composer marker found: skipping composer install."
fi

if [ ! -f "$SCHEMA_MARKER" ]; then
    echo "No schema marker found: creating database schema."
    php bin/console doctrine:schema:create --no-interaction
    touch "$SCHEMA_MARKER"
else
    echo "Schema marker found: skipping schema:create."
fi

if [ ! -f "$FIXTURES_MARKER" ]; then
    echo "No fixtures marker found: loading fixtures."
    php bin/console doctrine:fixtures:load --no-interaction
    touch "$FIXTURES_MARKER"
else
    echo "Fixtures marker found: skipping fixtures:load."
fi

if [ "$1" = 'prod' ]; then
    echo "Running in production mode."
else
    echo "Running in development mode."
    exec docker-php-entrypoint "$@"
fi
