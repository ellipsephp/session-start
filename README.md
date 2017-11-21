# Session start

This package provides a Psr-15 middleware allowing to use session with Psr-7 request and response.

**Require** php >= 7.1

**Installation** `composer require ellipse/session-start`

**Run tests** `./vendor/bin/kahlan`

- [Using the start session middleware](#using-the-start-session-middleware)

# Using the start session middleware

This middleware use the default php session mechanism adapted to Psr-7 request and response flow. The default php session cookie is disabled and the session id is manually stored in a cookie readed from the Psr-7 request and attached to the Psr-7 response.

By default values returned by `session_name()` and `session_get_cookie_params` are used to build the session cookie. An optional array of options can be given to the middleware in order to overwrite those default values:

- (string) **name**: the session cookie name
- (string) **path**: the session cookie path
- (string) **domain**: the session cookie domain
- (int) **lifetime**: the session cookie lifetime in second
- (bool) **secure**: whether the session cookie should only be sent over secure connections
- (bool) **httponly**: whether the session cookie can only be accessed through the HTTP protocol

The middleware can of course be used after the `SessionHandlerMiddleware` from [ellipse/session-handler](https://github.com/ellipsephp/session-handler) in order to use a custom session handler.

```php
<?php

namespace App;

use Ellipse\Session\StartSessionMiddleware;

// All middleware processed after this one will have acces to the $_SESSION data.
// The session cookie name will by 'my_session_cookie'. See above for other options.
$middleware = new StartSessionMiddleware([
    'name' => 'my_session_cookie',
]);
```
