# Keep your Laravel logs small and tidy

[![Latest Version on Packagist](https://img.shields.io/packagist/v/accentinteractive/laravel-logcleaner.svg?style=flat-square)](https://packagist.org/packages/accentinteractive/laravel-logcleaner)
[![Build Status](https://img.shields.io/travis/accentinteractive/laravel-logcleaner/master.svg?style=flat-square)](https://travis-ci.org/accentinteractive/laravel-logcleaner)
[![Quality Score](https://img.shields.io/scrutinizer/g/accentinteractive/laravel-logcleaner.svg?style=flat-square)](https://scrutinizer-ci.com/g/accentinteractive/laravel-logcleaner)
[![Total Downloads](https://img.shields.io/packagist/dt/accentinteractive/laravel-logcleaner.svg?style=flat-square)](https://packagist.org/packages/accentinteractive/laravel-logcleaner)

Logs can get quite out of hand. This package helps save server space and keep your Laravel log files small.
1. Trim your daily log to a given number of lines do it does not grow huge.
2. Delete old daily logs, only keeping a given number of the latest log files.

- Laravel 12 support as of 1.6.0.
- Laravel 11 support as of 1.3.0.
- Laravel 10 support as of 1.2.0.
- Laravel 9 support as of 1.1.0. 
- Versions before that support Laravel 6, 7, 8.

- [Installation](#installation) 
- [Examples](#usage) 
- [Config settings](#config-settings)

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
You can use `logcleaner:run` from the command line or set it as a cron job.

Command line usage;
``` php
// Get info about the command and options
php artisan logcleaner:run --help

// Trim big log files and delete old log files
php artisan logcleaner:run

// Pass the number of lines to keep when trimming log files. Overrides the config setting.
// This overrides the default set in config
php artisan logcleaner:run --keeplines=10000

// Pass the number of files to keep when deleting old log files. Overrides the config setting.
// This overrides the default set in config
php artisan logcleaner:run --keepfiles=7

// Run without actually cleaning any logs
php artisan logcleaner:run --dry-run
```

Cron job usage, add this to `App\Console\Kernel`:
``` php
protected function schedule(Schedule $schedule)
{
    $schedule->command('logcleaner:run')->daily()->at('01:00');
}
```

Of course, you can also pass options when defining a cron job.
``` php
protected function schedule(Schedule $schedule)
{
    $schedule->command('logcleaner:run', ['--keeplines' => 5000, '--keepfiles' => 14])->daily()->at('01:00');
}
```

## Subfolder handling
From version 1.4.0, files in subfolders are processed as well.

- Trimming: all files in subfolder are trimmed.
- Deleting: in each subfolder, all files except the N most recent ones are deleted. Where N equals config(`logcleaner.log_files_to_keep`).
- Handling of subfolders is set to true by default, but can be overridden by `env('LOGCLEANER_PROCESS_SUBFOLDERS')`

## ENV variables

You can set the following ENV variables in your .env file:

- `LOGCLEANER_LOG_PATH` : The path to your logfile, relative from the root path of your application. If you do not supply `LOGCLEANER_LOG_PATH`, the default Laravel log path will be used. Example value: `storage/custom_logs`. 
- `LOGCLEANER_TRIMMING_ENABLED` : Whether to trim log files to a certain number of lines or not. Defaults to `true` if not set in .env.
- `LOGCLEANER_LOG_LINES_TO_KEEP` : The number of lines to keep when trimming files. Defaults to `20000` if not set in .env.
- `LOGCLEANER_DELETING_ENABLED` : Whether to delete older log files or not. Defaults to `true` if not set in .env.
- `LOGCLEANER_LOG_FILES_TO_KEEP` : The number of files to keep when deleting older log files. Defaults to `30` if not set in .env.
- `LOGCLEANER_PROCESS_SUBFOLDERS` : Whether or not to process files in subfolders from the log path. Defaults to `true` if not set in .env.

## Config settings
You can pass config settings to modify the behaviour.
- `logcleaner.log_files_to_keep` : the number of log files to keep when deleting old log files. This config setting is overridden by option `--keepfiles` 
- `logcleaner.log_lines_to_keep` : the number of lines to leave intact when trimming log files. This config setting is overridden by option `--keeplines`
- `logcleaner.exclude` : an array of filenames to exclude from processing, using wildcards. 
- `logcleaner.trimming_enabled` : enables log file trimming. `true` by default. 
- `logcleaner.deleting_enabled` : enables old log file deletions. `true` by default. 
- `logcleaner.process_subfolders` : whether to include files in subfolders. `true` by default. 

You can also pass options directly. 
- `--keeplines=2000`
- `--keepfiles=7`
- `--dry-run`

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
