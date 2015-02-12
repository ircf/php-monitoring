# php-monitoring
A simple PHP script to monitor network services

# Install
```
cd /opt
wget http://github.com/ircf/php-monitoring/archive/master.zip
gunzip master.zip
cd php-monitoring
```

# Configure services and alert
```
cd /opt/php-monitoring
cp config.inc.php-dist config.inc.php
nano config.inc.php
# Enter the service names, IP, alert method, and save file
```

# Configure cron
```
ln -s cron /etc/cron.d/php-monitoring
```

# View services statuses
Services statuses can be viewed from CLI :
```
php /opt/php-monitoring/index.php
```
Or if you have a web server installed, from a web browser :
```
http://example.tld/php-monitoring/index.php
```
