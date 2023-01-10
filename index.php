<!DOCTYPE html>
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <title>Payphone Dashboard</title>
</head>
<?php
// TODO acquire db location from config file
// TODO filter for specific filetypes; get from config file?

/**
 * Global notifications
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
 * Create an table row for a phone number
 *
 * @param Int $number Phone number to use
 * @param String $filename name of sound file
 * @param String $description description of phone number
 */
function phone_entry($number, $filename, $description)
{
    $sound_url = "sound.php?number=$number";
    return <<< HTML
        <tr>
            <td>$number</td>
            <td><a href="$sound_url">$filename</a></td>
            <td>$description</td>
            <td>
                <form action="" method="POST">
                    <button type="submit" name="delete" value="$number">
                        Delete
                    </button>
                </form>
            </td>
        </tr>
        HTML;
}

/**
 * Check if a POST request was made to delete a database entry.
 */
function delete_entry()
{
    $notify = new Notifier();
    // strip html tags to prevent XSS when we display to user
    $number = sanitize_html($_POST['delete']);
    try {
        // connect to database
        $db = new SQLite3('phone.db', SQLITE3_OPEN_READWRITE);
        // create a prepared statement to avoid sql injection
        $stmt = $db->prepare('DELETE from numbers WHERE number = :num');
        $stmt->bindValue(':num', $number, SQLITE3_INTEGER);
        // execute prepared statement
        $result = $stmt->execute();

        if ($result === false) {
            // handle errors if execution failed
            trigger_error("Query failed; did not delete entry $number.", E_USER_WARNING);
        } else {
            // provide user feedback
            $notify->store("<h3>Deleted entry $number!</h3>");
        }

        // close connection
        $db->close();
    } catch (Exception $e) {
        // catch exception on DB connection and notify user
        trigger_error($e->getMessage(), E_USER_ERROR);
    }
}

/**
 * Check if a POST request was made to create a database entry.
 */
function create_entry()
{
    $notify = new Notifier();
    // we're creating a new entry
    // TODO size constraint for files; get from config?
    // TODO file type constraints; get from config?
    $file = $_FILES['sound'];
    // make file size limit more visible
    if (!isset($file['tmp_name']) | $file['tmp_name'] === '') {
        trigger_error(
            'Failed to receive uploaded file.  '
            . 'Is your file larger than the upload size limit? '
            . 'Failed to create entry.',
            E_USER_WARNING
        );
        return;
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
        return;
    } elseif (strlen($number_unsafe) > 10 | strlen($number_unsafe) < 1) {
        trigger_error('Failed to create entry.  '
            . 'Phone number length must be greater than 1 and less than 10.',
            E_USER_NOTICE);
        return;
    }

    try {
        // connect to database
        $db = new SQLite3('phone.db', SQLITE3_OPEN_READWRITE);
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
            $notify->store("<h3>Created entry $number</h3>");
        }

        // close connection
        $db->close();
    } catch (Exception $e) {
        trigger_error($e->getMessage(), E_USER_ERROR);
    }
}

/**
 * Error handler
 * @param $err_level
 * @param $msg
 */
function err($errno, $msg, $file, $line)
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

set_error_handler('err');

// alert for oversized POSTs
if ($_SERVER['REQUEST_METHOD'] == 'POST'
    && empty($_POST)
    && empty($_FILES)
    && $_SERVER['CONTENT_LENGTH'] > 0) {
    trigger_error(
        'Failed to receive uploaded data!  Upload was too large.',
        E_USER_WARNING
    );
}

// form handling
if (isset($_POST['delete'])) {
    // we're deleting an entry
    delete_entry();

} else if (isset($_POST['create'])) {
    create_entry();
}
?>

<body>
<div class="title-block">
    <h1>Payphone Dashboard</h1>
</div>
<div id="list-block">
    <table id="numbers-table">
        <thead class="title-block">
        <tr>
            <td>Phone Number</td>
            <td>Sound</td>
            <td>Description</td>
            <td></td>
        </tr>
        </thead>
        <tbody>
<?php
try {
    // connect to db (read only)
    $db = new SQLite3('phone.db', SQLITE3_OPEN_READONLY);

    // get all phone numbers, file names, and descriptions
    $entries = $db->query('SELECT number, filename, description '
        . 'FROM numbers ORDER BY substr(number, 0, 2), length(number), number ASC');
    // loop until we have no more query results
    while ($row = $entries->fetchArray()) {
        // create a table row for each phone number
        echo phone_entry(sanitize_html($row['number']),
            sanitize_html($row['filename']),
            sanitize_html($row['description']));
    }

    // close db connection
    $db->close();
} catch (Exception $e) {
    trigger_error($e->getMessage(), E_USER_ERROR);
}
?>
        </tbody>
    </table>
</div>
<div id="new-entry-block">
    <h3>Add Entry</h3>
    <form action="" method="POST" enctype="multipart/form-data">
    <input type="text" name="number" placeholder="Phone Number" pattern="^[0-9]+$" minlength=0 maxlength=10>
    <input type="file" name="sound">
    <input type="text" name="description" placeholder="Description">
    <button type="submit" name="create">Submit</button>
    </form>
    <?php
$filesize_limit = ini_get('upload_max_filesize');
echo "<p><i>File uploads are capped at $filesize_limit.</i></p>";
?>
</div>
<div id="notify-block">
<?php
$notify = new Notifier();
// block to show user notifications
if ($notify->has_notifications()) {
    // show header for context if we have notices to show
    echo "<h2>Notices</h2>";
    echo $notify->get();
}
?>
</div>
</body>
