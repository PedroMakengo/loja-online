#!/bin/sh
# Arranque do contentor: prepara pastas (volumes) e aplica migrações.
set -e
cd /var/www/html

mkdir -p storage/logs storage/comprovativos assets/img/produtos/uploads assets/img/blog/uploads
chown -R www-data:www-data storage assets/img/produtos/uploads assets/img/blog/uploads

if [ "${MIGRAR_NO_ARRANQUE:-1}" = "1" ]; then
    php database/migrar.php
fi

exec docker-php-entrypoint "$@"
