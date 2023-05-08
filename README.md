# CakePHP Webauthn Example Application

![Build Status](https://github.com/cakephp/app/actions/workflows/ci.yml/badge.svg?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/app.svg?style=flat-square)](https://packagist.org/packages/cakephp/app)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%207-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

An example application for using Webauthn and soon Passkeys with CakePHP 
and the CakePHP authentication plugin. If this proves useful, it could become
a plugin.

## Installation

1. Download [Composer](https://getcomposer.org/doc/00-intro.md) or update `composer self-update`.
2. Run `php composer.phar create-project --prefer-dist cakephp/app [app_name]`.

If Composer is installed globally, run

```bash
composer create-project --prefer-dist markstory/cakephp-webauthn-example
```

In case you want to use a custom app dir name (e.g. `/cake-webauth/`):

```bash
composer create-project --prefer-dist markstory/cakephp-webauthn-example cake-webauth
```

## Running the Application

:warning: Webauthn **requires** HTTPS. If you run this example on an HTTP only port it will not work.

If you have a SSL server running you can have it proxy the CakePHP dev server, 
or serve the application directly.

```bash
bin/cake server -p 8765
```

Or a PHP webserver & nodejs HTTPS server.

```bash
nodejs bin/server
```

Lastly you can use hosted services to create SSL tunnels.

## Configuration

Read and edit the environment specific `config/app_local.php` and set up the
`'Datasources'` and any other configuration relevant for your application.
Other environment agnostic settings can be changed in `config/app.php`.
