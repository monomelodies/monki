# Advanced topics

## Handling access for different tables
Since the first method of every `Monki\Api::[action]` call is a regex, and
Reroute resolves routes in order, you can easily specify different routes and
actions with different privilege levels:

```php
<?php

$api = new Monki\Api($db, $router);
// foo is always allowed:
$api->browse("/(?'table'foo)/", function () {
    return null;
});
// bar is never allowed:
$api->browse("/(?'table'bar)/", function () {
    return 403;
});

```

## Extending and overriding
Sometimes, a CRUD operation will require some additional handling. This is most
common when dealing with files, but we're sure you can think of other examples.

There are two ways to handle this. For endpoints with a controller that is
compatible with `Monki\Endpoint\Item\Controller` (e.g. extending it), you can
pass the fully namespaced controller name as the third argument to either
`Api::browse` (for creation) or `Api::item` (for update/delete):

```php
<?php

$access = function () {
    // check permissions for files...
};
$api->item("/(?'table'file)/", $access, 'My\File\Controller');
$api->item("/(?'table'file)/(?'id'\d+)/", $access, 'My\File\Controller');

```

```php
<?php

namespace My\File;

use Monki\Endpoint\Item;

class Controller extends Item\Controller
{
    public function create($table)
    {
        // move_uploaded_file?
        return parent::create($table);
    }
}

```

In more complicated scenarios, you can also manually catch a route and handle it
without Monki even touching it:

```php
<?php

// Assuming $api is a `Monki\Api` object etc...
$router->state('my-file', new Flat('/file/', ['POST']), function () {
    // ... handle file upload...
    // Maybe use My\File\Controller?
});
$api->item("/(?'table'\w+)/");

```

Take care to perform access checking manually in these cases, since the state is
now no longer handled by Monki _at all_! E.g., in the above example the `POST`
to `/file/` should check if the current user has sufficient permissions to
upload files before calling any controller/view. Of course you could still use
the same `$access` function from the earlier example, just handle its return
value manually.

## Structuring API routes using Reroute
Reroute supports some additional methods you can utilise to easily structure
your API routes:

```php
<?php

// Set the URL for all our API routes:
$router->host('https://api.example.com', function ($router) {
    // Version 1.1!
    $router->under('/1.1', function ($router) {
        // The "group" can be used in our front controller during state
        // resolving. Perhaps we need to specify extra headers for our
        // API routes:
        $router->group('api', function ($router) {
            $api = new Monki\Api(new PDO(...));
            $api->browse("/(?'table'\w+)/"); // etc.
        });
    });
});

```

For table `foo`, the "browse" route would now be
`https://api.example.com/1.1/foo/`. [See the full Reroute documentation for
more info on its various options.](http://reroute.monomelodies.nl/docs/)

