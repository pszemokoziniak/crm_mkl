dev:
	docker-compose up -d && npm run watch

build:
	npm install
	docker-compose run --rm app composer install
	docker-compose run --rm app php artisan key:generate
	sudo chmod -R 777 ./storage/
	make migrate
	make seed-dev

app-bash:
	docker-compose exec -u myuser app /bin/bash

db:
	docker exec -it crm-mkl-db mysql -u root -p'password'

bash:
	docker exec -it ${CONTAINER_NAME_APP} /bin/bash

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

