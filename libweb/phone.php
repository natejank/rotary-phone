<?php
/*
 * Copyright (C) 2023 Nathan Jankowski
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

/**
 * Common PHP functions and classes for the telephone web interface
 *
 * @author Nathan Jankowski (njj3397 [at] rit [dot] edu)
 */

/**
 * Global notification tracker
 */
class Notifier
{
    static $notifications = '';

    public function store($message)
    {
        $this::$notifications = $message . $this::$notifications;
    }

    public function get()
    {
        return $this::$notifications;
    }

    public function clear()
    {
        $this::$notifications = '';
    }

    public function has_notifications()
    {
        return $this::$notifications !== '';
    }
}

/**
 * Error handler
 * @param $err_level
 * @param $msg
 */
function error_handler($errno, $msg, $file, $line)
{
    error_log("Processed error $errno in $file on line $line.  $msg");
    $notify = new Notifier();
    if (!(error_reporting() & $errno)) {
        // Error code is not included in error_reporting;
        // fall back to built-in handler
        return false;
    }
    $msg = htmlspecialchars($msg);
    switch ($errno) {
        case E_USER_ERROR:
            $log_m = "Uncaught fatal error $errno on line $line of $file!  $msg.";

            syslog(LOG_CRIT, $log_m);
            echo error_msg("$log_m Please contact your System Administrator.");
            die(1);

        case E_WARNING:
        case E_USER_WARNING:
            syslog(LOG_WARNING, $msg);
            $notify->store(warning_msg($msg));
            break;

        case E_NOTICE:
        case E_USER_NOTICE:
            syslog(LOG_NOTICE, $msg);
            $notify->store(notice_msg($msg));
            break;
        default:
            // unknown error type
            $notify->store(error_msg("Unknown error [$errno] $msg"));
            break;
    }

    // don't use internal error handler
    return true;
}

/**
 * Escapes any "special" html characters to combat cross-site scripting.
 *
 * @param String $content content to escape
 * @return String escaped content
 */
function sanitize_html($content)
{
    // sanitize user input to prevent XSS
    return htmlentities($content, ENT_QUOTES);
}

/**
 * Gets an html page header, with doctype and <head>
 *
 * @param String $title page title
 * @return String page header
 */
function page_header($title)
{
    return <<<HTML
<!DOCTYPE html>
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <title>$title</title>
</head>
HTML;
}

/**
 * Converts a string into an "error" tag with styling
 *
 * @param String $message message to display
 * @return String styled message
 */
function error_msg($message)
{
    return '<h2 class="error">'
        . $message
        . '</h2>';
}

/**
 * Converts a string into a "notice" tag with proper styling
 *
 * @param String $message message to display
 * @return String styled message
 */
function warning_msg($message)
{
    return '<h3 class="warning">'
        . $message
        . '</h3>';
}

/**
 * Converts a string into a "notice" tag with proper styling
 *
 * @param String $message message to display
 * @return String styled message
 */
function notice_msg($message)
{
    return '<h3 class="notice">'
        . $message
        . '</h3>';
}

/**
 * Gets file contents as a binary blob
 * @param String $path path to file
 * @return String containing binary file contents
 */
function get_blob_contents($path)
{
    $handle = fopen($path, 'rb');
    $content = fread($handle, filesize($path));
    fclose($handle);
    return $content;
}

/**
 * Checks if the caller's environment is using https or not.
 * @return Boolean true if environment is https; false if http
 */
function is_using_https()
{
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
}

/**
 * Gets the location of the configuration file
 *
 * @return String|Boolean location of config file; false if no file exists.
 */
function get_cfg_location()
{
    $CONFIG_LOCATIONS = array('/etc/payphone/config.json', 'config.json', '../config.json');
    foreach ($CONFIG_LOCATIONS as $cfg) {
        if (file_exists($cfg)) {
            return $cfg;
        }

    }
    return false;
}

/**
 * Gets key from config
 *
 * @param String $key key to fetch
 * @return mixed returns key value
 * @throws Exception if file doesn't exist
 */
function get_config_key($key)
{
    $path_or_error = get_cfg_location();
    if ($path_or_error === false) {
        throw new Exception('Could not get config file!');
    }

    $f = file_get_contents($path_or_error);
    $j = json_decode($f, true);
    return $j[$key];
}

/**
 * Gets the location of the database
 *
 * @return String location of database
 * @throws Exception if file doesn't exist
 */
function get_db_location()
{
    return get_config_key('database_location');
}

/**
 * Gets supported filetypes
 *
 * @return Array supported file extensions and mime types
 * @throws Exception if file doesn't exist
 */
function get_supported_filetypes()
{
    return get_config_key('supported_filetypes');
}

/**
 * Gets a database connection
 *
 * @param Integer $mode mode to open database in; use sqlite constants
 * @return SQLite3 connection to sqlite3 database
 * @throws Exception if file does not exist
 */
function get_db_connection($mode = SQLITE3_OPEN_READONLY)
{
    $db_location = get_db_location();
    return new SQLite3($db_location, $mode);
}
