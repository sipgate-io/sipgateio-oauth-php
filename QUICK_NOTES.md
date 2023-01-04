- kein XAMPP, stattdessen `php -S localhost:8000 -t src/ src/router.php`
- wir nutzen composer !!
- use XAMPP to run an Apache webserver

- dont put project in /users/ folder or sub folder, as it has specific permissions that XAMPP struggles with. Instead, put the project in /var/www and give yourself and Apache write access:
```shell
sudo chown $USER:_www /var/www/sipgateio-oauth-php
sudo chmod g+s /var/www/sipgateio-oauth-php
sudo chmod o-rwx /var/www/sipgateio-oauth-php
```
If your IDE is struggling with write permissions for the directory, you can brute force by executing
```shell
sudo chown -R $USER /var/www/sipgateio-oauth-php
```