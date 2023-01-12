<?php
/**
 * Serve sound file from database by phone number.
 * Opens database, reads file name and content, then serves it as a document.
 * Uses query strings; e.g. sound.php?number=100
 *
 * @author Nathan Jankowski (njj3397 [at] rit [dot] edu)
 */

include '../libweb/phone.php';

if (!isset($_GET['number'])) {
    // we don't have a valid query; just quit.
    // user doesn't really need feedback
    die();
}
$number = $_GET['number'];

// we're requesting a sound file
try {
    $db = get_db_connection(SQLITE3_OPEN_READONLY);
} catch (Exception $e) {
    die('Could not access database!  Contact your system adminstrator.');
}

// use a prepared statement because the world is evil
$stmt = $db->prepare('SELECT filename, sound FROM numbers WHERE number = :num');
$stmt->bindValue(':num', $number, SQLITE3_TEXT);

//query db
$exec = $stmt->execute();
if ($exec === false) {
    die('Query failed!');
}

$result = $exec->fetchArray();
if ($result === false) {
    die('Query was empty! Number does not exist.');
}
// close connection
$db->close();

// content is binary
header('Content-type: application/octet-stream');
// set filename to what is stored in db
header("Content-Disposition: attachment; filename=\"{$result['filename']}\"");
// serve file!
echo $result['sound'];
?>