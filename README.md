# Monki
Tool to easily bootstrap and generate APIs.

Monki is a library that allows you to quickly add a basic API to your project.
Providing an API is becoming more and more important for several reasons:

- It allows your library or project to easily become part of the "internet of
  things" by allowing other libraries or apps to access it;
- For SPAs using e.g. AngularJS offering an API is a must;
- Even when writing traditional PHP apps, using an API can abstract away much
  of the workings of a database for instance.

## Installation

### Composer (recommended)

```composer require monomelodies/monki```

### Manual
Download or clone the repo, and add `/path/to/monki/src` to your autoloader for
the `Monki\\` namespace.

## Basic usage
Monki relies on the [Reroute](http://reroute.monomelodies.nl) router:

```php
<?php

use Monki\Api;
use Reroute\Router;

$db = new PDO($dsn, $user, $pass, $options);
$router = new Router;
$monki = new Monki($router, $db);
// e.g. $monki->browse('/url/');

```

Your front controller (e.g. `index.php`) should match this:

```php
<?php

// Get `$router`...

if ($state = $router->resolve(
    "http://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}",
    $_SERVER['REQUEST_METHOD']
)) {
    $state->run();
}

```

If your project itself uses a different router, just let it do its stuff _after_
Monki attempted to resolve. `Router::resolve` returns `null` if nothing could be
matched.

Monki defines default states for `browse` (list of items), `item` (a single
item) and `count` (a count of items matching conditions).

