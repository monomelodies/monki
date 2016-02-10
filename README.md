# Monki
Simple API bootstrapper.

Monki is a library that allows you to quickly add a basic API to your project.
Providing an API is becoming more and more important for several reasons:

- It allows your library or project to easily become part of the "internet of
  things" by allowing other libraries or apps to access it;
- For SPAs using e.g. AngularJS offering an API is a must;
- Even when writing traditional PHP apps, using an API can abstract away much
  of the workings of a database for instance.

## Installation

### Composer (recommended)

```bash
$ cd /path/to/project
$ composer require monomelodies/monki
```

### Manual
Download or clone the repo, and add `/path/to/monki/src` to your PSR-4
autoloader for the `Monki\\` namespace.

## Setting up
Monki implements a _pipeline_ using
[league/pipeline](https://packagist.org/packages/league/pipeline). It also needs
a `PDO` adapter on construction to query your database:

```php
<?php

use Monki\Api;

$db = new PDO($dsn, $user, $pass, $options);
$monki = new Monki($db, '/base/url/of/api/');
```

Your front controller (e.g. `index.php`) can then add it to its pipeline (see
the `league/pipeline` docs for more information):

```php
<?php

$pipeline = (new Pipeline)
    ->pipe($monki);
```

If you're not using a pipeline, you can also invoke the Monki object and
inspect its return value:

```php
<?php

use Psr\Http\Message\ResponseInterface;

$response = $monki();
if ($response instanceof ResponseInterface) {
    // Emit the response
} else {
    // No an API URL; use e.g. your router to determine further handling.
}
```

For emitting you could use e.g. `Zend\Diactoros\Response\SapiEmitter`. Diactoros
is a requirement of Monki, so you already have it. But you're free to use
something else.

## Adding states and responses
Monki defines default states for `browse` (list of items), `item` (a single
item) and `count` (a count of items matching conditions). A `POST` to `browse`
is equal to creating a new item, a `POST` to `item` is either an update or a
delete, depending on the value of the `action` parameter. Each method accepts
an optional `$validate` callable which will be added to Monki's internal
pipeline. This can be used to e.g. check access to a certain URL.

> Monki deliberately does not use HTTP `PUT` and `DELETE` verbs, as support for
> them is sketchy at best, especially when your API needs to be accessed via
> [CORS](http://enable-cors.org/).

URLs get appended to the `$url` you passed as the second argument to the
constructor (which may, by the way, optionally contain a scheme/domain). The
defaults are `/{table}/` for browse, `/{table}/{id}/` for items and
`/{table}/count/` for counting.

By default, nothing is exposed. You'll need to specify what your API should
support:

```php
<?php

use Zend\Diactoros\Response\EmptyResponse;

// Only authenticated users can access items:
$monki->item(function ($request) {
    if (!isset($_SESSION['auth'])) {
        return new EmptyResponse(403);
    }
// Anyone can browse.
// `browse` etc. calls handle the correct returning of a `ResponseInterface`
// object from the pipeline; you can use e.g.
// `Zend\Diactoros\Response\SapiEmitter` to emit that to the client.
$monki->browse();
});
```

Note that it is imperative that you call `item` before `browse` since Reroute
matches routes in the order they are defined; since `(\w+)` for the table would
also match `"/table/id/"` you would otherwise erroneously end up with browse
views when requesting a specific row.

## Custom states
To extend your API with extra calls, simply `extend` the `Monki\Api` object with
a more specific class of your own. Alternatively, Monki internally uses
[Reroute](http://reroute.monomelodies.nl) for routing, so you could also use
this and define custom calls before calling Monki:

```php
<?php

use Reroute\Router;
use Monki\Api;

$router->when('/some/custom/url/')->then('custom', function () {
    // ...handle, return ResponseInterface
});
$monki = new Api($myDatabase, '/some/other/url/');
// ...
```

Or you can pass pipe Monki to Reroute (or vice versa, they both implement
pipelines):

```php
<?php

// ...as above, only:
$router->pipe($monki);
// or, depending on your pipe logic:
$monki->pipe($router);
```

## Custom routes
The `Api` class offers a `setRoute` method. This allows you to override the
default route for `browse`, `item` or `count` (or custom actions you might have
defined, if you use `$this->routes` internally) for subsequent calls.

The first argument is the "type" of the route (i.e. the key). The second
argument is simply the regex for the route. Take care to use named matches
(e.g. `"/(?'table'\w+)/"` with the expected names.

The new route will be valid for all calls to e.g. `browse` _after_ the
`setRoute` definition. Any previous calls remain untouched.

## Internal workings
Monki is very "dumb" in its understanding of your database: it blindly assumes
the `table` passed exists, and whatever you `$_POST` to it can be inserted or
updated or deleted (the entire contents of `$_POST['data']` are treated as
key/value pairs of data). It does catch `PDOException`s, but makes no other
attempt to validate your data. If you need that, do it yourself.

A quick and dirty way to prevent access to certain tables is to simply make
their routes match _before_ Monki handles the request, and return a 400 resopnse
or something.

## Inserting server-side `$_POST` values
Monki recursively traverses each entry in `$_POST['data']` (if set) and checks
if any value is a callable. To prevent accidental matching to one of PHP's many
built-in functions, the name should be wrapped in `$(...)`.

If a function of that name is found (note that for namespaced functions you must
use the full namespace!), its return value is assigned instead.

> The `$(...)` wrapper is used to minimize the risk of accidentally passing a
> string that happens to resolve to one of the gazillion PHP functions out
> there. Otherwise Monki could never accept something like
> `['functionName': 'strpos']` as `$_POST` data!

A common use would be to get the current user id without having to pass it
around in the frontend, thus immedialy also forcing validation (e.g. with a
`NOT NULL` constraint in your schema:

```php
<?php

namespace Monki;

function userAdminId() {
    return isset($_SESSION['User']) && $_SESSION['User']['admin'] ?
        $_SESSION['User']['id'] :
        null;
};
```

You could now pass the following to ensure "admin level access":

```javascript
$.post('/path/to/endpoint/', {
    owner: '$(userAdminId)',
    foo: 'foo',
    bar: 'bar'
});
```

(The example is in jQuery for convenience, but you get the idea.)

## Passing parameters to API calls
Internally, Monki uses the [Dabble database
abstraction](http://dabble.monomelodies.nl). This means that you can pass
filters (`WHERE` clauses) and options (`LIMIT`, `OFFSET` etc.) as JSON-encoded
`$_GET` parameters.

Passing `filter` and/or `options` is mostly useful when doing `browse` and
`count` API queries. For single items, they rarely make sense.

```javascript
// Filter on foo = 'bar'
$.get('/path/to/endpoint?filter=' + JSON.stringify({foo: 'bar'}));
// Order by datecreated DESC:
$.get('/path/to/endpoint?options=' + JSON.stringify({order: 'datecreated DESC'}));
```

Dabble takes care of escaping filters. For options, this is not possible; but
PHP's `PDO` extension (which Dabble uses) _should_ prevent running multiple
queries in one `execute` call.

## Passing raw values in filters or options
Dabble offers a `Dabble\Query\Raw` object that tells the query builder to
treat it contents (the constructor argument) verbatim (i.e., do no
quoting/escaping).

Since passing raw values from the client in JSON is an obvious security risk,
you'll need to handle these yourself if you need them. An example would be
implementing a filter like `datecreated > NOW()`; `NOW()` is a "raw" parameter.

A simple strategy could be to examine `$_GET['filter']` and pre-parse it
accordingly in your front controller (taking care to validate data before you
pass it to `Raw`...). You could also extend the `Api` class and override the
`browse` method, or define a special route altogether (e.g.
`/browse/bydate/`).

> For the love of all that is beautiful, _please_ be _very_ careful about
> sanitizing your data if you accept "raw" input! It's supported, but that
> doesn't mean it's recommended...

## (CORS) headers
The Monki pipeline returns a `JsonResponse` object, which in turn extends
`Zend\Diactoros\Response`. Simply emit the response with the added headers
instead:

```php
<?php

$response = $monki();
// "emit" should do the emitting in this example:
emit($reponse->withHeader('Access-Control-Allow-Origin', '*'));
```

Of course, you'd want to inspect the response first before doing this, but you
get the idea.

