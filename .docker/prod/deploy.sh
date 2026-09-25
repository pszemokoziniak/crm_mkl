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

# Front w kontenerze Node: bez zależności od Node na hoście (po podniesieniu
# Ubuntu repozytorium nodesource się wyłącza).
docker run --rm -v /var/www/mkl:/var/www/mkl -w /var/www/mkl node:20-slim npm run prod

docker exec -u www-data hrm-app php artisan optimize:clear
echo "Wdrożone."
