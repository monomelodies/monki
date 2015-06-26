# Creating a simple API

## Setup
The `Monki\Api` object is your central entry point:

```php
<?php

$api = new Monki\Api(PDO $adapter, Reroute\Router $router);

```

`$adapter` is a `PDO` object or an object that extends it (e.g. a
[Dabble](http://dabble.monomelodies.nl) adapter). `$router` is an instance
of a [Reroute router](http://reroute.monomelodies.nl).

> If your project uses a different router, that's fine. Just make sure that
> wherever you resolve your routes, you add a call to `Router::resolve` first.

## Adding the endpoints
Simply call one of the "endpoint methods" on the `$api` object:

```
<?php

$api->browse("/(?'table'\w+)/");
$api->count("/(?'table'\w+)/");
$api->item("/(?'table'\w+)/(?'id'\d+)/");

```

This defines any route `/\w+/` as point to the database table with that name.
Monki handles the default actions for it.

The `browse` and `count` actions expect a regex containing at least a `table`
parameter, or that parameter as its first match. The `item` action also expects
an `id` parameter, or that parameter as its second match. This follows best
practices for creating APIs:

```
GET /foo/ <- list items in 'foo'
POST /foo/ <- create somethig in 'foo'
GET /foo/id/ <- get item with id 'id' in 'foo'
POST /foo/id/ <- modify or delete item with id 'id' in 'foo'
```

The `count` method is supplied for pagination purposes. If you don't require
that, by all means omit it.

## Internal workings
Monki is very "dumb" in its understanding of your database: it blindly assumes
the `table` passed exists, and whatever you `$_POST` to it can be inserted or
updated or deleted (the entire contents of `$_POST['data']` are treated as
key/value pairs of data). It does catch `PDOException`s, but makes no other
attempt to validate your data. If you need that, do it yourself.

