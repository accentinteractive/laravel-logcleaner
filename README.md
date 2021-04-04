# Keep your Laravel logs small and tidy

[![Latest Version on Packagist](https://img.shields.io/packagist/v/accentinteractive/laravel-logcleaner.svg?style=flat-square)](https://packagist.org/packages/accentinteractive/laravel-logcleaner)
[![Build Status](https://img.shields.io/travis/accentinteractive/laravel-logcleaner/master.svg?style=flat-square)](https://travis-ci.org/accentinteractive/laravel-logcleaner)
[![Quality Score](https://img.shields.io/scrutinizer/g/accentinteractive/laravel-logcleaner.svg?style=flat-square)](https://scrutinizer-ci.com/g/accentinteractive/laravel-logcleaner)
[![Total Downloads](https://img.shields.io/packagist/dt/accentinteractive/laravel-logcleaner.svg?style=flat-square)](https://packagist.org/packages/accentinteractive/laravel-logcleaner)

Logs can get quite out of hand. This package helps save server space and keep your logs files small.
1. Trim your daily log to a given number of lines do it doen not grow huge.
2. Delete old daily logs, only keeping a given number of the latest log files. 

You can pass config settings to modify the behaviour.
- `log_files_to_keep` : the number of log files to keep when deleting old log files.  
- `log_lines_to_keep` : the number of lines to leave intact when trimming log files. 
- `exclude` : an array of filenames to exclude from processing, using wildcards. 
- `trimming_enabled` : enables log file trimming. `true` by default. 
- `deleting_enabled` : enables old log file deletions. `true` by default. 

## Installation

You can install the package via composer:

```bash
composer require accentinteractive/laravel-logcleaner
```

Optionally you can publish the config file with:
```
php artisan vendor:publish --provider="Accentinteractive\LaravelLogcleaner\LaravelLogcleanerServiceProvider" --tag="config"
```

## Usage

``` php
// Trim big log files and delete old log files
php artisan logcleaner:run

// Run without actually cleaning any logs
php artisan logcleaner:run --dry-run

// Clean your log files by cron job
// Add to App\Console\Kernel
protected function schedule(Schedule $schedule)
{
    $schedule->command('logcleaner:run')->daily()->at('01:00');
}
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email joost@accentinteractive.nl instead of using the issue tracker.

## Credits

- [Joost van Veen](https://github.com/accentinteractive)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
