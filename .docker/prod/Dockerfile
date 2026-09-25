# Produkcyjny obraz HRM: samo PHP-FPM 8.4, bez xdebug i narzędzi dev.
# (8.1 do 25.09.2026; powrót = zmiana linii FROM i deploy.sh.)
# Kod NIE jest kopiowany do obrazu — /var/www/mkl z hosta jest podpięty pod
# tą samą ścieżką, więc wdrożenie to dalej git pull, a pliki w storage/app
# (skany, zdjęcia, faktury) żyją na hoście i obejmuje je nocny backup.
# Po co kontener: PHP przestaje zależeć od wersji Ubuntu na serwerze.
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
        libzip-dev libxml2-dev libxslt1-dev libonig-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath calendar exif gd gettext mysqli pcntl pdo_mysql \
        shmop sockets sysvmsg sysvsem sysvshm xsl zip opcache \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

# Composer na wypadek zmian w composer.lock (docker exec hrm-app composer install).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/zz-hrm.ini
# "zz-hrm" ładuje się po "zz-docker.conf" z obrazu, więc wygrywa jego listen.
COPY www.conf /usr/local/etc/php-fpm.d/zz-hrm.conf

WORKDIR /var/www/mkl
