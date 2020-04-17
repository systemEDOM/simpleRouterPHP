# SimpleRouterPHP
___

## .htaccess

```php
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

## Start with package

```php
require_once "../vendor/autoload.php";

use EDOM\SimpleRouterPHP\Router;

$router = new Router("EDOM\\");
```
**Router receive as parameter the namespace where you manage your controllers**

## GET

```php
$router->get('/', function () {
    echo "get";
});

$router->get('/users/[i:id]', 'UsersController@show');
```

## POST

```php
$router->post('/', function () {
    echo "post";
});

$router->post('/users/[i:id]', 'UsersController@store');
```

## PUT

```php
$router->put('/', function () {
    echo "put";
});

$router->put('/users/[i:id]', 'UsersController@update');
```

## DELETE

```php
$router->delete('/', function () {
    echo "delete";
});

$router->delete('/users/[i:id]', 'UsersController@delete');
```
**The structure to pass a controller is Class@method**

## Finally call this method

```php
$router->match();
```