# Changelog

All notable changes to `laravel-logcleaner` will be documented in this file

## 1.6.0 - 2025-02-21
### Added
- Added support for Laravel 12

## 1.5.0 - 2024-08-22
### Added
- Added support for the log path in `ENV('LOGCLEANER_LOG_PATH')`. This should be set as a path relative to the app root path, like `storage/custom_logpath`.
- Added `LOGCLEANER_LOG_LINES_TO_KEEP` to be set in env file.
- Added `LOGCLEANER_LOG_FILES_TO_KEEP` to be set in env file.
- Added available env variables to README. 

## 1.4.0 - 2024-05-24
### Added
- Added support to handle logfiles in subfolders. Can be set in config `logcleaner.process_subfolders`. Is set to `true` by default.

## 1.3.0 - 2024-05-23
### Added
- Added Laravel 11 support. Courtesy of Shift

## 1.2.0 - 2023-04-26
### Added
- Added Laravel 10 support. Courtesy of https://github.com/niveshsaharan

## 1.1.0 - 2022-02-10
### Added
- Added Laravel 9 support. Courtesy of https://github.com/usernotnull

## 1.0.1 - 2021-04-26
### Changes
- Removed dependency illuminate/collections, because it is not present in Laravel < 8

## 1.0.0 - 2021-04-04
- initial release
