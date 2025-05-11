all: lint

lint: formatcheck phpstan rector

serve:
	@composer dump-autoload
	@php artisan serve

composer:
	@printf "\e[1;39;44mRun: composer update \e[0m\n"
	@composer update

phpstan:
	@vendor/bin/phpstan --ansi analyse --no-progress --memory-limit=-1 -c phpstan.neon --autoload-file=vendor/autoload.php

rector:
	@vendor/bin/rector --dry-run

formatcheck:
	@./vendor/bin/mago format --dry-run

format:
	@./vendor/bin/mago format

cache:
	@php artisan cache:clear
	@php artisan config:clear

dev_db_fresh:
	@php artisan migrate:fresh
	@php artisan db:seed --class=DevelopmentSeeder

ide_helper:
	@php artisan ide-helper:generate
	@php artisan ide-helper:models --write-mixin
	@php artisan ide-helper:meta

test:
	@XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html coverage/html
