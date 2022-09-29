# MKL CRM

## Installation

Install PHP dependencies:

```sh
composer install
```

Install NPM dependencies:

```sh
npm ci
```

Build assets:

```sh
npm run dev
```

Setup configuration:

```sh
cp .env.example .env
```

Generate application key:

```sh
php artisan key:generate
```

Create an SQLite database. You can also use another database (MySQL, Postgres), simply update your configuration accordingly.

```sh
touch database/database.sqlite
```

Run database migrations:

```sh
php artisan migrate
```

Rollback last migration:
```sh
php artisan migrate:rollback --step=1
```

Run database seeder:

```sh
php artisan db:seed
```

Run the dev server (the output will give the address):

```sh
php artisan serve
```

- **Username:** test@mkl.com
- **Password:** secret

## Running tests

To run the MKL CRM tests, run:

```
phpunit
```

## Run with Docker and Docker-compose

Set DB settings and copy Env file
```shell
cp .env.example .env
```

Build with user and group
```shell
docker-compose build --build-arg userid=$(id -g) --build-arg groupid=$(id -u)
```

Run
```shell
docker-compose up -d
```

Install dependencies
```shell
docker-compose exec app composer install
```

npm ci && npm ci dev - executed on host (can be dockerized)

```shell
docker-compose exec app php artisan migrate
```

```shell
docker-compose exec app php artisan db:seed
```

## FrontEnd implementation
* Calendar implementation based on https://tailwindcomponents.com/component/calendar-ui-with-tailwindcss-and-alpinejs
* Installed axios for asynchronous responses
* Datetime picker for vue3: https://vue3datepicker.com/
