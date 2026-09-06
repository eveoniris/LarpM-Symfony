#!/bin/bash
# This script is used to build and run the production version of the LarpM-Symfony application using Docker.
# For local testing purposes only.
docker build --target prod -t larpm-symfony:local .

cat << EOF > .env.prod.local
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=b0248926cd06d6ba2cc16e42014d2152
DEFAULT_URI=http://localhost
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1|larpmanager\.test)(:[0-9]+)?$'
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=
SERVER_NAME=":80, larpmanager.test"
MYSQL_ROOT_PASSWORD=password
MYSQL_DATABASE=larpm
MYSQL_USER=admin
MYSQL_PASSWORD=password
DATABASE_URL="mysql://admin:password@database:3306/larpm?serverVersion=8.4&charset=utf8mb4"
MAILER_DSN=smtp://mailer:1025
EOF

export WEBSERVER_IMAGE=larpm-symfony:local
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
