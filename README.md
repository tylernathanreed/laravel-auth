# Multi-Auth for Laravel 5.1

[![Latest Stable Version](https://poser.pugx.org/reed/auth/version.png)](https://packagist.org/packages/reed/auth)
[![Total Downloads](https://poser.pugx.org/reed/auth/d/total.png)](https://packagist.org/packages/reed/auth)

This is a mostly drop-in replacement for Laravel 5.2's Multi-Auth for Laravel 5.1.
Most of the source core for this project comes from Laravel 5.2, and is just refactored to work with 5.1.

***Deprecation Notice:*** With the final end of Laravel 5.1 being behind us, I am marking this package as abandoned. By now, everyone should be using Laravel 5.5 or greater. While I may still provide periodic updates, I am no longer officially maintaining this package.

## Installation
### Composer
Require this package with composer:

```
composer require reed/auth
```

### Service Providers
After updating composer, add the Service Provider to the providers array in `config/app.php`.

```
Reed\Auth\AuthServiceProvider::class
```

To avoid conflicts, you should also remove Laravel's Auth Provider.

```
// Illuminate\Auth\AuthServiceProvider::class,
```

However, you should keep Laravel's Auth Facade, as this package just replaces the underlying singleton.

### Configuration

Go ahead and grab yourself a copy of [Laravel 5.2's configuration file](https://raw.githubusercontent.com/laravel/laravel/master/config/auth.php).
The old configuration file won't work, and you'll probably want to configure this one to match your settings.

### Replacement

Any references to Laravel 5.1's Authorization Layer isn't going to work anymore. You'll want to swap them out with the new components.
Here are the new class paths:

 - `Illuminate\Auth\*` => `Reed\Auth\*`
 - `Illuminate\Contracts\Auth\*` => `Reed\Auth\Contracts\*`
 - `Illuminate\Foundation\Auth\*` => `Reed\Auth\Foundation\*`

Common places to find these are:

 - `Authenticate` and `RedirectIfAuthenticated` Middlewares
 - The `User` Model

## Usage

It's exactly the same as Multi-Auth in Laravel 5.2, so I'll refer you to the [documentation](https://laravel.com/docs/5.2/authentication).
