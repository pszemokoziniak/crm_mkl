#!/usr/bin/env bash
# Wdrożenie HRM (hrm.mkl.pl) w kontenerze hrm-app. Uruchamiać na serwerze
# jako root:  /var/www/mkl/.docker/prod/deploy.sh
# Kolejność jak przed kontenerem: kod, migracje, front, czyszczenie cache.
set -euo pipefail
cd /var/www/mkl

git pull --ff-only

# Obraz przebuduje się tylko, gdy zmieni się .docker/prod (cache warstw).
docker compose -f docker-compose.prod.yml up -d --build

# Jako www-data, żeby logi i cache w storage miały właściciela, pod którym
# działa PHP-FPM.
docker exec -u www-data hrm-app php artisan migrate --force

# Front w kontenerze Node: bez zależności od Node na hoście (po podniesieniu
# Ubuntu repozytorium nodesource się wyłącza).
docker run --rm -v /var/www/mkl:/var/www/mkl -w /var/www/mkl node:20-slim npm run prod

docker exec -u www-data hrm-app php artisan optimize:clear
echo "Wdrożone."
