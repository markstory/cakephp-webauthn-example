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

### mkcert & stunnel

Using a pair of CLI utilities you can generate an HTTPs proxy for the cakephp
dev server. I found this pretty simple to use on linux.

Generate certificates for your local machine using `mkcert`

```bash
mkcert localhost
cat localhost.pem localhost-key.pem > localhost-bundle.pem
chmod 0666 *.pem
```

This will generate certificate & key file. Create the bundled certificate
for `stunnel`

Then in one terminal, run: `bin/cake server` and then in another run 

```bash
sudo stunnel3 -f -d 443 -r 8765 -p ./localhost-bundle.pem
```

Lastly you can use hosted services to create SSL tunnels.

## Configuration

Read and edit the environment specific `config/app_local.php` and set up the
`'Datasources'` and any other configuration relevant for your application.
Other environment agnostic settings can be changed in `config/app.php`.

## What's included

A sample application that:

* A CakePHP Authentication plugin compatible Authenticator and high-level API
  for building passkey based flows.
* Allows new users to be created using U2F authenticators (also referred to as
  Passkeys)
* Allows users to login with their U2F device.
* Allows a user to register multiple devices.

Still to be built:

- Preventing duplicates passkeys to be added by the same device.
- Preventing deletion of a user's last passkey.
