# EmailOctopus Driver for Spatie Laravel Newsletter

## Installation

```bash
composer require adamcmoore/laravel-newsletter-emailoctopus
```

## Usage
This package is a driver for [Spatie Newsletter](https://github.com/spatie/laravel-newsletter).

To use this driver set your `config/newsletter.php` to include the below:
```php
<?php

return [
    'driver' => AcMoore\LaravelNewsletter\Drivers\EmailOctopusDriver::class,
    'driver_arguments' => [
        'api_key' => env('EMAIL_OCTOPUS_API_KEY'),
    ],
    'default_list_name' =>  'default',
    'lists' => [
        'default' => [
            'id' => env('EMAIL_OCTOPUS_LIST_ID'),
        ],
    ],
];
```

Documentation for full usage can be found on the [Spatie Newsletter](https://github.com/spatie/laravel-newsletter) GitHub page.