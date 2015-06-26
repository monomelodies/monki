# Validating requests
A common scenario is for an API to define endpoints that are only accessible to
certain users, e.g. a `$_POST` to an `item` is only allowed for an authenticated
user. All `Monki\Api` methods accept an optional second argument which must be a
callable returning `null` (no error, thus allowed) or one of the following HTTP
stati corresponding to the situation at hand:

- 401: The current user is unauthorized, i.e. not logged in;
- 403: The current user _is_ authorized (logged in), but has insufficient rights
  to access the endpoint and/or perform the requested operation;
- 404: For `Item` calls, the item with the supplied `$id` was not found;
- 405: For `Item` post calls, the requested action is not available.

> Errors 404 and 405 are by default "thrown" if the item cannot be found or
> the controller does not offer the `action` method contained in `$_POST`. The
> custom validation is called first, so if you need to override this behaviour
> simply return a different error.

```php
<?php

$api = new Monki\Api($db, $router);
$api->browse("/(?'table'\w+)/", function () {
    return user_logged_in() ? null : 401;
});

```

The callable is passed both the `table` and, for `item`s, the found item as
parameters for fine-grained control:

```
<?php

$api = new Monki\Api($db, $router);
$api->browse("/(?'table'\w+)/", function ($table) {
    switch ($table) {
        // foo and bar have anonymous access:
        case 'foo':
        case 'bar':
            return null;
        default:
            // Fictional session checker:
            return user_logged_in() ? null : 401;
    }
});
$api->item("/(?'table'\w+)/(?'id'\d+)/", function ($table, $item, $VERB) {
    if ($item) {
        if (user_logged_in()) {
            // Only "owners" can do CRUD on items.
            if ($VERB == 'POST') {
                // Fictional method getting current user id:
                return $item['owner'] == user_get_id() ? null : 403;
            } else {
                return;
            }
        }
        return 401;
    }
});

```

The last argument passed is the HTTP verb (`GET`, `POST` etc.) for even more
control. Monki by design doesn't support `PUT` etc. since verbs other than
`GET` and `POST` work crappily with
[CORS](https://en.wikipedia.org/wiki/Cross-origin_resource_sharing) across
browsers. Instead, it posts an `action` parameter that must be the name of a
public method supplied in the controller. You usually don't need to worry about
this.

## Validation schemes
Use what you want, Monki only cares about the return value of the callable. Note
that it can be any valid PHP callable, including internal functions (passed as a
string, though that wouldn't make sense) and methods on objects or classes
(passed as an array, e.g. `[$obj, 'method']`).

