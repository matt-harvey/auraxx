# Changelog

### v0.3.1

* Allow customizability of fallback route
* Document fallback route
* Some more tests added

### v0.3.0

* BREAKING CHANGE: Now depends on PSR-11 (ContainerInterface) >= v1.1 but < v2. This is
  to enable support for the PHP-DI package, until such time as the latter catches up
  with PSR-11 v2.

### v0.2.0

* BREAKING CHANGE: `Application::handle` now normalizes the HTTP method before further processing
* Documentation improvements
* PHPStan added to CI

### v0.1.2

* Add missing PSR interface dependencies to composer.json
* Add PHPStan static analysis to CI

### v0.1.1

Fix namespace errors.

### v0.1.0

Initial release.
