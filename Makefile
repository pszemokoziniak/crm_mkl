dev:
	docker-compose up -d && npm run watch

app-bash:
	docker-compose exec -u myuser app /bin/bash

stop:
	docker-compose stop

migrate:
	docker-compose exec app php artisan migrate

deploy:
	bash ./build.sh

seed:
	docker-compose exec app php artisan db:seed

seed-dev:
	docker-compose exec app php artisan db:seed --class=CountrySeeder
	docker-compose exec app php artisan db:seed --class=FunkcjaSeeder
	docker-compose exec app php artisan db:seed --class=DevUserSeeder

load-bk:
	docker cp backup.sql crm-mkl-db:backup.sql \
	&& docker-compose exec db bash -c "mysql -uuser -ppassword pingcrm < backup.sql"

