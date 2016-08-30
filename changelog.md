# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [1.1.2] - 2016-08-30
### Added
- PHP 7 Error Exception handling as well as keeping PHP 5.6 support

## [1.1.1] - 2015-12-21
### Added
- Added the complete exception to the logger to get better error messages in for example Sentry

## [1.1] - 2015-10-23
### Added
- Option to by pass/disable logging for specific response status codes. Comes in handy when you don't want to get a lot of entries about 404 NotFound exceptions.

## [1.0.1] - 2015-07-07
### Added
- Added HTTP Status code to the error message as statusCode. This is a preparation for the coming JSONP serializer.

## [1.0.0] - 2015-07-02
- Initial release!
