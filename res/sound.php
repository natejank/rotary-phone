<?php
/*
 * Copyright (C) 2023 Nathan Jankowski
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

/**
 * Serve sound file from database by phone number.
 * Opens database, reads file name and content, then serves it as a document.
 * Uses query strings; e.g. sound.php?number=100
 *
 * @author Nathan Jankowski (njj3397 [at] rit [dot] edu)
 */

include '../libweb/phone.php';
set_error_handler('error_handler');

if (!isset($_GET['number'])) {
    // we don't have a valid query; just quit.
    // user doesn't really need feedback
    die();
}

/**
 * Get sound file for get request
 *
 * @return String|Boolean string of binary file; false if query failed
 */
function get_sound_file()
{
    $err = false;
    $number = $_GET['number'];

    // we're requesting a sound file
    try {
        $db = get_db_connection(SQLITE3_OPEN_READONLY);
    } catch (Exception $e) {
        trigger_error(
            'Could not access database!  Contact your system adminstrator.',
            E_USER_ERROR
        );
        return true;
    }

    // use a prepared statement because the world is evil
    $stmt = $db->prepare('SELECT filename, sound FROM numbers WHERE number = :num');
    $stmt->bindValue(':num', $number, SQLITE3_TEXT);

    //query db
    $exec = $stmt->execute();
    if ($exec === false) {
        trigger_error(
            'Query failed!',
            E_USER_ERROR
        );
        $err = true;
    }

    $result = $exec->fetchArray();
    if ($result === false) {
        trigger_error(
            'Query was empty! Number does not exist.',
            E_USER_WARNING
        );
        $err = true;
    }
    // close connection
    $db->close();

    return $err ? false : $result;
}

if (($res = get_sound_file()) !== false) {
    // content is binary
    header('Content-type: application/octet-stream');
    // set filename to what is stored in db
    header("Content-Disposition: attachment; filename=\"{$res['filename']}\"");
    // serve file!
    echo $res['sound'];
} else {
    // query failed; display to user
    $err = new Notifier();
    // make the page ✨stylilsh✨
    echo page_header('Payphone Dashboard Sound File');
    // print errors
    echo $err->get();
}
