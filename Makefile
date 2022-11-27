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
