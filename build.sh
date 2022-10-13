!#/bin/bash

npm install

composer install

php artisan migrate

php artisan cache:clear
