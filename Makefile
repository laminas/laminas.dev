# Makefile as a Deployment Tool
#
# Docs and examples:
#	http://www.gnu.org/software/make/manual/make.html
#	https://cbednarski.com/articles/makefiles-for-everyone/
#	https://github.com/njh/easyrdf/blob/master/Makefile
#

#
# cd /var/www/xtreamlabs.com
# sudo -u deploy make deploy target=master
#

.PHONY: cli init clear-cache update update-composer test queue-start deploy

cli:
	docker exec -ti xtreamlabs-php /bin/sh

init:
	composer install --no-interaction

clear-cache:
	rm -rf data/cache/*
	rm -f data/config-cache.php
	rm -f data/cache/config-cache.php
	rm -f data/cache/fastroute.php.cache

update: clear-cache update-composer

update-composer:
	composer update --no-interaction

test:
	composer test

queue-start:
	docker-compose run --rm php vendor/bin/expressive-console messenger:consume messenger.transport.default

deploy:
	touch public/.maintenance
	git fetch --all
	git reset --hard origin/$(target)
	composer install --no-ansi --no-dev --no-interaction --no-progress --optimize-autoloader
	rm -rf data/cache/*
	rm -f data/config-cache.php
	rm -f data/cache/config-cache.php
	rm -f data/cache/fastroute.php.cache
	rm -f public/.maintenance
