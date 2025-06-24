.PHONY: install setup start stop restart logs test

install:
	composer install

setup:
	php scripts/setup.php

create-filter:
	php bin/console filter:create \
		--name="$(NAME)" \
		--category="$(CATEGORY)" \
		--subcategory="$(SUBCATEGORY)" \
		--type="$(TYPE)" \
		--price="$(PRICE)" \
		--region-id="$(REGION_ID)" \
		--city-id="$(CITY_ID)" \
		--apartment-type="$(APPARTMENT_TYPE)" \
		--area="$(AREA)"

list-filters:
	php bin/console filter:list

monitor:
	php bin/console monitor:run

daemon:
	php bin/monitor-daemon

docker-build:
	docker-compose build

docker-up:
	docker-compose up -d

docker-down:
	docker-compose down

docker-logs:
	docker-compose logs -f

install-service:
	sudo cp systemd/olx-monitor.service /etc/systemd/system/
	sudo systemctl daemon-reload
	sudo systemctl enable olx-monitor
	sudo systemctl start olx-monitor

install-cron:
	sudo cp cron/olx-monitor-cron /etc/cron.d/olx-monitor
	sudo chmod 0644 /etc/cron.d/olx-monitor

status:
	sudo systemctl status olx-monitor

service-logs:
	sudo journalctl -u olx-monitor -f

test:
	./vendor/bin/phpunit
	./vendor/bin/phpstan analyse src
	./vendor/bin/phpcs

fix:
	./vendor/bin/phpcbf
