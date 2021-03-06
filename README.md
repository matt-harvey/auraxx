# auraxx

[![Github actions Build](https://github.com/matt-harvey/auraxx/workflows/tests/badge.svg)](https://github.com/matt-harvey/auraxx/actions/workflows/check.yml)
[![Latest Stable Version](http://poser.pugx.org/matt-harvey/auraxx/v)](https://packagist.org/packages/matt-harvey/auraxx)
[![Total Downloads](http://poser.pugx.org/matt-harvey/auraxx/downloads)](https://packagist.org/packages/matt-harvey/auraxx)
[![Latest Unstable Version](http://poser.pugx.org/matt-harvey/auraxx/v/unstable)](https://packagist.org/packages/matt-harvey/auraxx)
[![License](http://poser.pugx.org/matt-harvey/auraxx/license)](https://packagist.org/packages/matt-harvey/auraxx)
[![PHP Version Require](http://poser.pugx.org/matt-harvey/auraxx/require/php)](https://packagist.org/packages/matt-harvey/auraxx)

> a PSR-friendly, Aura-based, MVC-inclined PHP router

## Motivation

**Auraxx** is an extension of the [Aura.Router](https://github.com/auraphp/Aura.Router)
library, that provides certain additional functionality that I have found to be convenient for developing
MVC-style web applications.

It takes its place unashamedly among the 50,000,000 other PHP router packages. I have published it
mainly so I can easily reuse this code across my own projects, and I because I have no particular
reason to keep it private.

This is pre-alpha-stage, pre-v1 software. The package is very unstable with breaking changes happening at
any time. It's MIT licensed so you can use it if you want, but pleased be warned.

## Library overview

The `Auraxx\Router` class is designed to be extended by an application-specific router class
that defines the actual routes, and then used in conjunction with an `Auraxx\Application` instance for
actually handling a request.

`Auraxx\Router` adds certain functionality to the Aura router container:

* Allows default middleware to be defined globally, by overriding `::getDefaultMiddlewares`,
  such that middleware order can be configured independently of per-route middleware
  applicability
* A `::generateUri` method for generating a PSR `UriInterface` instance including optional
  query data and fragment
* A "convention-over-configuration" mechanism whereby the route name will, by default, determine the
  controller and method that will be called when that route is resolved (after the middleware layer
  has been traversed).
* Automatic injection of string or integer route parameters into the controller method based
  on matching method parameter names and types.
* Methods to configure middleware applicability on a per-route/-route-group basis
* Methods to configure permitted auth roles on a per-route/-route-group basis

## Detailed usage

The code has detailed documentation comment blocks. For example code that uses the library,
see the [test fixture](https://github.com/matt-harvey/auraxx/tree/main/testutil/Fixture) directory
of this repository.

## Installation

```
composer require matt-harvey/auraxx
```

## Contributing

If this project seems useful to you, but you'd like to improve or fix it in some way, feel free
to raise an Issue or PR.
