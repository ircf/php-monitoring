# php-monitoring
A simple PHP script to monitor network services

# Install
```
cd /opt
wget http://github.com/ircf/php-monitoring/archive/master.zip
gunzip master.zip
cd php-monitoring
ln -s cron /etc/cron.d/php-monitoring
```

# Configure
```
cd /opt/php-monitoring
cp config.inc.php-dist config.inc.php
nano config.inc.php
# Enter the service names, IP, alert method, and save file
```
