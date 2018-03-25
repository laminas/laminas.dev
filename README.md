# xtreamlabs.com

## Deploy

```bash
cd /var/www/xtreamlabs.com
sudo -u deploy make deploy target=master
sudo systemctl restart xtreamlabs-queue-default.service
sudo systemctl status xtreamlabs-queue-default.service
```

## Docker CLI

docker exec -ti xtreamlabs-php /bin/sh

## Development tooling

Create a factory class file for the named class. The class file is created in the same directory as the class specified.
```bash
vendor/bin/expressive factory:create App\Path\To\Class
```

Create a PSR-15 request handler class file. Also generates a factory for the generated class, and, if a template renderer is registered with the application container, generates a template and modifies the class to render it into a zend-diactoros HtmlResponse.
```bash
vendor/bin/expressive handler:create App\Handler\MyHandler
```

Create a PSR-15 middleware class file.
```bash
vendor/bin/expressive middleware:create App\Middleware\MyMiddleware
```

## Setup background queue processing

```bash
sudo nano /etc/systemd/system/xtreamlabs-queue-default.service
```

```
[Unit]
Description=xtreamlabs.com messenger.transport.xtreamlabs
After=network.target
Requires=mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/xtreamlabs.com
ExecStart=/var/www/xtreamlabs.com/vendor/bin/expressive-console messenger:consume messenger.transport.xtreamlabs
TimeoutStopSec=20
KillMode=process
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Commands:
```bash
sudo systemctl daemon-reload
sudo systemctl enable xtreamlabs-queue-default.service
sudo systemctl start xtreamlabs-queue-default.service
sudo systemctl status xtreamlabs-queue-default.service
sudo journalctl -u xtreamlabs-queue-default
```

## Slack ChatOps app

https://api.slack.com/apps
https://api.slack.com/apps/A9Z8BTAP3/general
