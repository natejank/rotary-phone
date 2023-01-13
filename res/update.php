<?php
/**
 * Update database entries (add/remove) with a POST request
 *
 * @author Nathan Jankowski (njj3397 [at] rit [dot] edu)
 */

include '../libweb/phone.php';

set_error_handler('error_handler');

/**
 * Check if a POST request was made to delete a database entry.
 */
function delete_entry()
{
    $notify = new Notifier();
    $result = false;
    // strip html tags to prevent XSS when we display to user
    $number = sanitize_html($_POST['delete']);
    try {
        // connect to database
        $db = get_db_connection(SQLITE3_OPEN_READWRITE);
        // create a prepared statement to avoid sql injection
        $stmt = $db->prepare('DELETE from numbers WHERE number = :num');
        $stmt->bindValue(':num', $number, SQLITE3_TEXT);
        // execute prepared statement
        $result = $stmt->execute();

        if ($result === false) {
            // handle errors if execution failed
            trigger_error("Query failed; did not delete entry $number.", E_USER_WARNING);
        } else {
            $result = true;
        }

        // close connection
        $db->close();
    } catch (Exception $e) {
        // catch exception on DB connection and notify user
        trigger_error($e->getMessage(), E_USER_ERROR);
    }
    return $result;
}

/**
 * Check if a POST request was made to create a database entry.
 */
function create_entry()
{
    $notify = new Notifier();
    $result = false;
    // we're creating a new entry
    $file = $_FILES['sound'];
    // make file size limit more visible
    if (!isset($file['tmp_name']) | $file['tmp_name'] === '') {
        trigger_error(
            'Failed to receive uploaded file.  '
            . 'Is your file larger than the upload size limit? '
            . 'Failed to create entry.',
            E_USER_WARNING
        );
        return $result;
    }
    // make sure filetype makes sense
    $types_or_error = get_supported_filetypes();
    $mime = $file['type'];
    $valid_mime = false;
    if ($types_or_error === false) {
        trigger_error(
            'Could not supported file types!',
            E_USER_ERROR
        );
        return $result;
    }

    foreach ($types_or_error as $t) {
        if ($mime == $t['mime']) {
            $valid_mime = true;
            break;
        }
    }

    if (!$valid_mime) {
        trigger_error(
            "Invalid filetype $mime!",
            E_USER_WARNING
        );
        return $result;
    }

    $sound_content_unsafe = get_blob_contents($file['tmp_name']);
    $number_unsafe = $_POST['number'];
    $description_unsafe = $_POST['description'];
    $filename_unsafe = $file['name'];

    // display-safe variables
    $number = sanitize_html($number_unsafe);
    $filename = sanitize_html($filename_unsafe);
    $description = sanitize_html($description_unsafe);

    // validate variables
    if (!ctype_digit($number_unsafe)) {
        trigger_error('Failed to create entry. '
            . "Phone number $number may only contain numeric symbols.",
            E_USER_NOTICE);
        return $result;
    } elseif (strlen($number_unsafe) > 10 | strlen($number_unsafe) < 1) {
        trigger_error('Failed to create entry.  '
            . 'Phone number length must be greater than 1 and less than 10.',
            E_USER_NOTICE);
        return $result;
    }

    try {
        // connect to database
        $db = get_db_connection(SQLITE3_OPEN_READWRITE);
        // create a prepared statement
        $stmt = $db->prepare('INSERT INTO numbers(number, sound, filename, description) '
            . 'VALUES (:num, :sound, :filename, :desc)');
        $stmt->bindValue(':num', $number_unsafe, SQLITE3_TEXT);
        $stmt->bindValue(':sound', $sound_content_unsafe, SQLITE3_BLOB);
        $stmt->bindValue(':filename', $filename_unsafe, SQLITE3_TEXT);
        $stmt->bindValue(':desc', $description_unsafe, SQLITE3_TEXT);
        //execute prepared statement
        $result = $stmt->execute();

        if ($result === false) {
            trigger_error(
                "Query failed; did not create entry $number.  "
                . 'Does the number already exist?',
                E_USER_WARNING);
        } else {
            $result = true;
        }

        // close connection
        $db->close();
    } catch (Exception $e) {
        trigger_error($e->getMessage(), E_USER_ERROR);
    }
    return $result;
}

$result = true;
$messages = new Notifier();

$protocol = is_using_https() ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$homepage = "$protocol://$host/index.php";

// alert for oversized POSTs
if ($_SERVER['REQUEST_METHOD'] == 'POST'
    && empty($_POST)
    && empty($_FILES)
    && $_SERVER['CONTENT_LENGTH'] > 0) {
    trigger_error(
        'Failed to receive uploaded data!  Upload was too large.',
        E_USER_WARNING
    );
    $result = false;
}
// form handling
if (isset($_POST['delete'])) {
    // we're deleting an entry
    $result = delete_entry();
} else if (isset($_POST['create'])) {
    // we're creating an entry
    $result = create_entry();
}

// if we succeeded, redirect to destination
if ($result !== false) {
    header('HTTP/1.1 303 See Other');
    header("Location: $homepage");
} else {
    // otherwise, show header and display error messages
    echo page_header('Payphone Dashboard Update');
    echo $messages->get();
    echo "<a href=\"$homepage\">Take me home!</a>";
}
