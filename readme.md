# Mistake Middleware

[![Build status](https://img.shields.io/travis/phapi/middleware-mistake.svg?style=flat-square)](https://travis-ci.org/phapi/middleware-mistake)
[![Code Climate](https://img.shields.io/codeclimate/github/phapi/middleware-mistake.svg?style=flat-square)](https://codeclimate.com/github/phapi/middleware-mistake)
[![Test Coverage](https://img.shields.io/codeclimate/coverage/github/phapi/middleware-mistake.svg?style=flat-square)](https://codeclimate.com/github/phapi/middleware-mistake/coverage)

The Mistake Middleware handles errors and exceptions by registering custom shutdown function, error handler and exception handler. When an error or exception is caught the middleware creates a log entry and prepares an error message that will be sent to the client before interacting with the pipeline by reseting the queue and telling the pipeline to only call middleware registered before the Mistake Middleware (usually only serializers and middleware responsible for sending the response to the client).

## Installation
This middleware is by default included in the [Phapi Framework](https://github.com/phapi/phapi-framework) but if you need to install it it's available to install via [Packagist](https://packagist.org) and [Composer](https://getcomposer.org).

```shell
$ php composer.phar require phapi/middleware-mistake:1.*
```

## Configuration
There are two configuration options available for the Mistake Middleware; if error messages should be shown. This is handy to have enabled during development since it gives a more detailed error message. It should however **be turned off in production** since an error message will be serialized and return to the client. (Default: off).

The second option gives the opportunity to disable logging for specific status codes. This comes in handy if you, for example, don't want to flood your logs with 404 NotFound exceptions.

```php
<?php

// For development
$pipeline->pipe(new \Phapi\Middleware\Mistake\Mistake($displayErrors = false, $doNotLog = [ 404 ]));

```

See the [configuration documentation](http://phapi.github.io/docs/started/configuration/) for more information about how to configure the integration with the Phapi Framework.

## Phapi
This middleware is a Phapi package used by the [Phapi Framework](https://github.com/phapi/phapi-framework). The middleware are also [PSR-7](https://github.com/php-fig/http-message) compliant and implements the [Phapi Middleware Contract](https://github.com/phapi/contract).

## License
The Mistake Middleware is licensed under the MIT License - see the [license.md](https://github.com/phapi/middleware-mistake/blob/master/license.md) file for details

## Contribute
Contribution, bug fixes etc are [always welcome](https://github.com/phapi/middleware-mistake/issues/new).
