dev:
	docker-compose up -d && npm run watch

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
load-bk:
	docker cp filename.sql crm-mkl-db:filename.sql \
	&& docker-compose exec db bash -c "mysql -uuser -ppassword pingcrm < filename.sql"


