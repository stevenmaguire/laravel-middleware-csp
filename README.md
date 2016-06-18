# Content Security Policy Middleware

[![Latest Version](https://img.shields.io/github/release/stevenmaguire/laravel-middleware-csp.svg?style=flat-square)](https://github.com/stevenmaguire/laravel-middleware-csp/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/stevenmaguire/laravel-middleware-csp/master.svg?style=flat-square)](https://travis-ci.org/stevenmaguire/laravel-middleware-csp)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/stevenmaguire/laravel-middleware-csp.svg?style=flat-square)](https://scrutinizer-ci.com/g/stevenmaguire/laravel-middleware-csp/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/stevenmaguire/laravel-middleware-csp.svg?style=flat-square)](https://scrutinizer-ci.com/g/stevenmaguire/laravel-middleware-csp)
[![Total Downloads](https://img.shields.io/packagist/dt/stevenmaguire/laravel-middleware-csp.svg?style=flat-square)](https://packagist.org/packages/stevenmaguire/laravel-middleware-csp)

Provides support for enforcing Content Security Policy with headers in Laravel responses.

## Install

Via Composer

``` bash
$ composer require stevenmaguire/laravel-middleware-csp
```

## Usage

### Register as route middleware

``` php
// within app/Http/Kernal.php

protected $routeMiddleware = [
    //
    'secure.content' => \Stevenmaguire\Laravel\Http\Middleware\EnforceContentSecurity::class,
    //
];
```

### Apply content security policy to routes

The following will apply all default profiles to the `gallery` route.

``` php
// within app/Http/routes.php

Route::get('gallery', ['middleware' => 'secure.content'], function () {
    return 'pictures!';
});
```

The following will apply all default profiles and a specific `flickr` profile to the `gallery` route.

``` php
// within app/Http/routes.php

Route::get('gallery', ['middleware' => 'secure.content:flickr'], function () {
    return 'pictures!';
});
```


### Apply content security policy to controllers

The following will apply all default profiles to all methods within the `GalleryController`.

``` php
// within app/Http/Controllers/GalleryController.php

public function __construct()
{
    $this->middleware('secure.content');
}
```
The following will apply all default profiles and a specific `google` profile to all methods within the `GalleryController`.

``` php
// within app/Http/Controllers/GalleryController.php

public function __construct()
{
    $this->middleware('secure.content:google');
}
```
You can include any number of specific profiles to any middleware decoration. For instance, the following will apply default, `google`, `flickr`, and `my_custom` profiles to all methods within the `GalleryController`.

``` php
// within app/Http/Controllers/GalleryController.php

public function __construct()
{
    $this->middleware('secure.content:google,flickr,my_custom');
}
```

### Create content security profiles

The default location for content security profiles is `security.content`. If you wish to use this default configuration, ensure your project includes the appropriate configuration files.

The structure of this configuration array is important. The middleware expects to find a `default` key with a string value and a `profiles` key with an array value.

``` php
// within config/security.php

return [
    'content' => [
        'default' => '',
        'profiles' => [],
    ],
];

```
The `profiles` array contains the security profiles for your application. Each profile name must be unique and is expected to have a value of an array.

``` php
// within config/security.php

return [
    'content' => [
        'default' => '',
        'profiles' => [
            'profile_one' => [],
            'profile_two' => [],
            'profile_three' => [],
        ],
    ],
];

```
Each profile array should contain keys that correspond to Content Security Policy directives. The value of each of these directives can be a string, comma-separated string, or array of strings. Each string value should correspond to the domain associated with your directive and profile.

``` php
// within config/security.php

return [
    'content' => [
        'default' => '',
        'profiles' => [
            'profile_one' => [
                'base-uri' => 'https://domain.com,http://google.com',
            ],
            'profile_two' => [
                'font-src' => 'https://domain.com',
                'base-uri' => [
                    "'self'",
                    'http://google.com'
                ],
            ],
            'profile_three' => [
                'font-src' => [
                    "'self'"
                ],
            ],
        ],
    ],
];

```
The `default` key value should be a string, comma-separated string, or array of strings that correspond to the unique profile names that you would like to enforce on all responses with minimal content security applied.

``` php
// within config/security.php

return [
    'content' => [
        'default' => 'profile_one',
        'profiles' => [
            'profile_one' => [
                'base-uri' => 'https://domain.com,http://google.com',
            ],
            'profile_two' => [
                'font-src' => 'https://domain.com',
                'base-uri' => [
                    "'self'",
                    'http://google.com'
                ],
            ],
            'profile_three' => [
                'font-src' => [
                    "'self'"
                ],
            ],
        ],
    ],
];

```

Here is a real-world example:

``` php
// within config/security.php

return [
    'content' => [
        'default' => 'global',
        'profiles' => [
            'global' => [
                'base-uri' => "'self'",
                'font-src' => [
                    "'self'",
                    'fonts.gstatic.com'
                ],
                'img-src' => "'self'",
                'script-src' => "'self'",
                'style-src' => [
                    "'self'",
                    "'unsafe-inline'",
                    'fonts.googleapis.com'
                ],
            ],
            'flickr' => [
                'img-src' => [
                    'https://*.staticflickr.com',
                ],
            ],
        ],
    ],
];

```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/stevenmaguire/laravel-middleware-csp/blob/master/CONTRIBUTING.md) for details.

## Credits

- [Steven Maguire](https://github.com/stevenmaguire)
- [All Contributors](https://github.com/stevenmaguire/laravel-middleware-csp/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
