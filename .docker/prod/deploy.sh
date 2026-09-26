#!/usr/bin/env bash
# Wdrożenie HRM (hrm.mkl.pl) w kontenerze hrm-app. Uruchamiać na serwerze
# jako root:  /var/www/mkl/.docker/prod/deploy.sh
# Kolejność jak przed kontenerem: kod, migracje, front, czyszczenie cache.
set -euo pipefail
cd /var/www/mkl

PRZED=$(git rev-parse HEAD)
git pull --ff-only
PO=$(git rev-parse HEAD)

# Obraz przebuduje się tylko, gdy zmieni się .docker/prod (cache warstw).
docker compose -f docker-compose.prod.yml up -d --build

# Zależności PHP tylko wtedy, gdy zmienił się composer.lock. vendor/ należy
# do www-data; bez pakietów deweloperskich (phpunit itp.).
if [ "$PRZED" != "$PO" ] && git diff --name-only "$PRZED" "$PO" -- composer.lock | grep -q .; then
    echo "Zmienił się composer.lock — composer install."
    docker exec -u www-data -e COMPOSER_HOME=/tmp/composer hrm-app \
        composer install --no-dev --optimize-autoloader --no-interaction
fi

# Jako www-data, żeby logi i cache w storage miały właściciela, pod którym
# działa PHP-FPM.
docker exec -u www-data hrm-app php artisan migrate --force

# Front w kontenerze Node 24 LTS: bez zależności od Node na hoście. Jako
# www-data (uid 33), żeby node_modules i zbudowane pliki miały jednego właściciela.
# npm ci tylko wtedy, gdy zmienił się package-lock.json — inaczej front budowałby
# się ze starych node_modules, niezgodnych z lockiem.
NODE="docker run --rm -u 33:33 -e HOME=/tmp -v /var/www/mkl:/var/www/mkl -w /var/www/mkl node:24-slim"
if [ "$PRZED" != "$PO" ] && git diff --name-only "$PRZED" "$PO" -- package-lock.json | grep -q .; then
    echo "Zmienił się package-lock.json — npm ci."
    $NODE npm ci --no-audit --no-fund
fi
$NODE npm run prod

docker exec -u www-data hrm-app php artisan optimize:clear
echo "Wdrożone."
