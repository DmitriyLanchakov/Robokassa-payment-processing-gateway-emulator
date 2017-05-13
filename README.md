# Robokassa payment processing gateway emulator
## Symfony
### ./app/config/parameters.yml
```yml
Parameters:
    robokassa_login: merchant_id
    robokassa_pass1: pass1
    robokassa_pass2: pass2
    robokassa_url: 'http://robokassa.loc/'
```

## PHP-FPM
### /etc/php5/fpm/php-fpm.conf
```
listen = 127.0.0.1:9000
```

```bash
service php-fpm restart
```

## Nginx
### /etc/nginx/vhosts.d/robokassa.conf
```lua
server {
    server_name robokassa.loc;
    root /srv/www/htdocs/robokassa;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~.php$ {
        fastcgi_pass 127.0.0.1:9000;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_index index.php;
    }
}
```

```bash
service nginx restart
```

## Hosts

```bash
echo "127.0.0.1 robokassa.loc" >> /etc/hosts
```

```bash
nscd -i hosts
```
