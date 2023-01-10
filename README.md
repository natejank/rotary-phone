# Rotary Payphone Hacking Project

## Build/Development Environment

This project targets Debian Stable for ease of deployment.
Because we target specific tool versions, we use the
[asdf version manager](https://asdf-vm.com/) to help with building and
installing tools for development.  Once you have the required
language extensions and build dependencies for asdf, simply run `asdf install`
within the project folder to install the currently targeted versions.

### PHP

This project uses [PHP 7.4.33](https://packages.debian.org/stable/php).
To install, use the [asdf php](https://github.com/asdf-community/asdf-php)
extension.  On macOS, this also involved installing the following build
dependencies (in addition to the xcode command line tools):
`brew install libsodium bison re2c gd libiconv libzip`

### Python

This project uses [Python 3.9.2](https://packages.debian.org/stable/python3).
To install, use the [asdf python](https://github.com/asdf-community/asdf-python)
extension.  This potentially has required
[build dependencies](https://github.com/pyenv/pyenv/wiki#suggested-build-environment).

### Initialize database

All tools work under the assumption that a database already exists.
To create one, use `initialize.py` and either pre-seed entries with the `-e` argument
or create an empty database using `python initialize.py`.

### Running Control Panel (development)

Once all tools are installed, the development server can be run with
`php --php-ini=php.ini --server=localhost:8000`

## todo

- [] config file for database location
- [] control bitrate and size of sound files

## Database Table Schema

### Phone number entries

Name: `numbers`

| keys         | type                  | related to |
| :-----       | :-------------------- | :--------- |
| id           | `INTEGER PRIMARY KEY` |            |
| number       | `UNIQUE TEXT`         |            |
| sound        | `BLOB`                |            |
| filename     | `TEXT`                |            |
| description  | `TEXT`                |            |
