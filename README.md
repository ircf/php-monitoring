# php-monitoring
A simple PHP script to monitor network services

## Requirements
PHP 5.x+ with PEAR Mail SMTP
```
apt-get install php5-cli php-pear && pear install Mail && pear install Net_SMTP
```

## Install
```
cd /opt
git clone https://github.com/ircf/php-monitoring.git
```

## Configure services and alert
```
cd /opt/php-monitoring
cp config.inc.php-dist config.inc.php
nano config.inc.php
# Enter services, alert and save file
```

## Configure cron
```
ln -s /opt/php-monitoring/cron /etc/cron.d/php-monitoring
```

## View services statuses
Services statuses can be viewed from CLI :
```
php /opt/php-monitoring/index.php
```
Or if you have a web server installed, from a web browser :
```
http://example.tld/php-monitoring/index.php
```
You can also have graphics :
```
http://example.tld/php-monitoring/graph.php
```
