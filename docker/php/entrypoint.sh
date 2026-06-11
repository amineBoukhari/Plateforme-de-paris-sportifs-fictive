#!/bin/sh
set -e

# Run migrations automatically on startup
if [ "${APP_ENV}" = "prod" ]; then
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

exec "$@"
