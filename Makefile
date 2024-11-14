all: lint

lint: pint phpstan

serve:
	@composer dump-autoload
	@php artisan serve

composer:
	@printf "\e[1;39;44mRun: composer update \e[0m\n"
	@composer update

phpstan:
	@vendor/bin/phpstan --ansi analyse --no-progress --memory-limit=-1 -c phpstan.neon --autoload-file=vendor/autoload.php

pint:
	@./vendor/bin/pint --test -v

format:
	@./vendor/bin/pint

cache:
	@php artisan cache:clear
	@php artisan config:clear

db_fresh:
	@php artisan migrate:fresh
	@php artisan db:seed

ide_helper:
	@php artisan ide-helper:generate
	@php artisan ide-helper:models
	@php artisan ide-helper:meta