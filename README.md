# gpxcat's laravel_db_log

Add the environment variable to your `.env` file:

```
LOG_LEVEL=debug
DB_LOG=1
```

### Laravel 5.5+ Integration

Laravel's package discovery will take care of integration for you.


### Laravel 5.* Integration

Add the service provider to your `config/app.php` file:
```php

    'providers'     => array(

        //...
        Gpxcat\LaravelDbLog\DBLogServiceProvider::class,

    ),

```
