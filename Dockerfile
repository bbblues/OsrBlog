# Imagem base: Apache + PHP 8.4.
FROM php:8.4-apache

# Instala dependências do sistema e extensões PHP necessárias:
# - libzip-dev + unzip + ext-zip: exigidos pelo Composer para extrair pacotes .zip;
# - ext-pdo_mysql: acesso ao banco MySQL 8.0.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        unzip \
    && docker-php-ext-install zip pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Habilita os módulos do Apache necessários:
# - rewrite: roteamento via .htaccess (Front Controller);
# - headers: headers de segurança (X-Content-Type-Options, X-Frame-Options, Referrer-Policy).
RUN a2enmod rewrite \
    && a2enmod headers

# Define a pasta public/ como DocumentRoot do Apache.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copia o binário do Composer a partir da imagem oficial.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Script de entrada: instala dependências quando necessário e inicia o Apache.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
