## INITIAL SETUP

docker run --rm \
-u "$(id -u):$(id -g)" \
-v "$(pwd):/var/www/html" \
-w /var/www/html \
laravelsail/php83-composer:latest \
composer install --ignore-platform-reqs

docker compose up -d

docker compose exec pgsql psql -U sail -d iplanitrite -c 'CREATE DATABASE iplanitrite_test;'