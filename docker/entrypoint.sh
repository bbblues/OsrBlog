#!/bin/sh
set -e

# Instala as dependências do Composer na primeira execução (ou se o vendor for removido).
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    composer install --no-interaction --no-progress --optimize-autoloader
fi

# Repassa o controle para o entrypoint padrão da imagem e inicia o Apache em foreground.
exec docker-php-entrypoint "$@"
