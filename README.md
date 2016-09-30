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
[league/pipeline](https://packagist.org/packages/league/pipeline):

```php
<?php

use Monki\Api;

$monki = new Monki('/base/url/of/api/');
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
use Zend\Diactoros\ServerRequestFactory;

$response = $monki(ServerRequestFactory::fromGlobals());
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
Internally Monki's pipeline utilises the
[`Monolyth\Reroute`](http://monolyth.monomelodies.nl/reroute/) router. Reroute
lets you specify the desired handling of HTTP requests using `when('url')` and
`then('statename', callable)`. Additionally you can specify callables for
`post`, `put` and `delete` and can add pipeline stages to e.g. check user access
to certain functions. You can specify all of these manually since the `when`
method on Monki returns the actual underlying router.

However, _normally_ any well-structured API will follow a pattern more similar
to this:

```
GET /api/user/ <- browse all users
POST /api/user/ <- create a new user
GET /api/user/:id/ <- retrieve a specific user
POST /api/user/:id/ <- update a specific user
DELETE /api/user/:id/ <- delete a specific user
```

This is exactly the sort of "default" API behaviour Monki aims to make easy to
bootstrap! It uses the `crud` method for this:

```php
<?php

use Monomelodies\Monki\Handler\Crud;

class MyHandler extends Crud
{
}

$monki->crud('/api/user/:id?/', new MyHandler);

```

In the above example, we simply pass it an instance of the `Crud` handler
interface. The return value of the `crud` method can again be pipelined, e.g.
for access checks. The question mark after the `":id"` parameter tells Reroute
it is an optional parameter.

If you try to access the URL `/api/user/`, you won't get a list of users yet but
rather a `Zend\Diactoros\Response\EmptyResponse` with a status code of 501 (not
implemented). This makes sense, since our handler doesn't actually specify any
handling yet! Hey, come on, Monki isn't clairvoyant...

Let's first make it respond to `browse`-style requests:

```php
<?php

use Monomelodies\Monki\Handler\Crud;

class MyHandler extends Crud
{
    public function browse($id = null)
    {
        // This should e.g. do a database query in real life:
        $users = ['Marijn', 'Linus', 'Bill'];
        return $this->jsonResponse($users);
    }
}

```

The list of default supported handlers is as follows:

- `Handler\Crud::browse`
- `Handler\Crud::create`
- `Handler\Crud::retrieve`
- `Handler\Crud::update`
- `Handler\Crud::delete`

To reuse your handler for multiple tables, you could e.g. pass the table name in
the constructor and store it privately.

## Full example
The following is a complete, working example using `Quibble\Query` for database
querying (see [their documentation](http://quibble.monomelodies.nl/query/) for
more information on the Quibble query builder).

```php
<?php

use Quibble\Dabble\Adapter;
use Zend\Diactoros\Response\EmptyResponse;

class MyHandler extends Crud implements Browse, Create, Retrieve, Update, Delete
{
    private $db;
    private $table;

    public function __construct(Adapter $db, $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    public function browse($id = null)
    {
        $items = $this->db->selectFrom($this->table)
            ->fetchAll(PDO::FETCH_ASSOC);
        return $this->jsonResponse($items);
    }

    public function create($id = null)
    {
        $this->db->insertInto($this->table)
            ->execute($_POST);
        $id = $this->db->lastInsertId($this->table);
        return $this->retrieve($id, 201);
    }

    public function retrieve($id, $status = 200)
    {
        $item = $this->db->selectFrom($this->table)
            ->where('id = ?', $id)
            ->fetch(PDO::FETCH_ASSOC);
        return $this->jsonResponse($item, $status);
    }

    public function update($id)
    {
        $this->db->updateTable($this->table)
            ->where('id = ?', $id)
            ->execute($_POST);
        return $this->retrieve($id);
    }

    public function delete($id)
    {
        $this->db->deleteFrom($this->table)
            ->where('id = ?', $id)
            ->execute();
        return $this->emptyResponse(204);
    }
}

// Assuming $user contains the currently logged in user...
$monki->crud('/api/user/:id/', new MyHandler($db, 'user'))
    ->pipe(function ($payload) use ($user) {
        if ($user->name != 'Marijn') {
            // Bad user! No access.
            return new EmptyResponse(403);
        }
        return $payload;
    });

```

Obviously this is rather bare bones as it does zero error checking, but you get
the idea. Using this handler class, you can quickly setup handling for a handful
of database tables:

```php
<?php

$check = function ($payload) use ($user) {
    if ($user->name != 'Marijn') {
        // Bad user! No access.
        return new EmptyResponse(403);
    }
    return $payload;
};
$monki->crud('/api/user/:id/', new MyHandler($db, 'user'))->pipe($check);
$monki->crud('/api/message/:id/', new MyHandler($db, 'message'))->pipe($check);
$monki->crud('/api/foo/:id/', new MyHandler($db, 'foo'))->pipe($check);
$monki->crud('/api/bar/:id/', new MyHandler($db, 'bar'))->pipe($check);
// This endpoint is open for the world (usually a bad idea ;)):
$monki->crud('/api/baz/:id/', new MyHandler($db, 'baz'));
// ...

```

For convenience, any CRUD method throwing an exception by default automatically
results in a 400 Bad Request response. You can always catch these errors in your
handler itself and return something more appropriate if you wish.

## Transforming responses
Monki comes with `League\Fractal` bundled as a dependency. Using Fractal, you
can add "transformers" to your responses. To do so, pass a transformer object
extending `League\Fractal\TransformerAbstract` as a third argument to the `crud`
method. See their documentation for more information on this. Any response that
isn't Json will ignore the transformer.

Using a transformer is very handy for massaging your data prior to emitting a
response. E.g., in our user example the table would likely also contain a `pass`
column which we don't want to expose. Or we could use it to cast the user id to
an actual integer instead of a string.

## Adding custom methods
Let's say we also need to add endpoints to the API for counting the total number
of items in a collection (e.g. for pagination). This is possible using
_annotations_:

```php
<?php

class MyHandler extends Crud
{
    /**
     * @Method GET
     * @Url {base}/count/
     */
    public function countIt()
    {
        return $this->jsonResponse(3);
    }
}

```

To define a custom method for an endpoint with a resource, simply require the
associated parameters in the method:

```php
<?php

class MyHandler extends Crud
{
    /**
     * @Method PUT
     * @Url {base}
     */
    public function thisIsSomethingCustom($id)
    {
        // ...
    }
}

```

Internally, the Crud handler's default methods are always available using the
specified HTTP methods and URL. You can annotate them, too, if you for whatever
reason need to override either. The `"{base}"` placeholder is simply replaced
verbatim with the base URL you gave the `Api` constructor.

## What's with that name, Monki?
I'm bilingual, and in Dutch "API" is pronounced like the word for "little
monkey". So that's why.

