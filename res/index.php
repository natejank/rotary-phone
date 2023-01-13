<?php
/**
 * Main dashboard page; list entries and allow adding/removing more
 *
 * @author Nathan Jankowski (njj3397 [at] rit [dot] edu)
 */

include '../libweb/phone.php';
set_error_handler('error_handler');

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
                <form action="update.php" method="POST">
                    <button type="submit" name="delete" value="$number">
                        Delete
                    </button>
                </form>
            </td>
        </tr>
        HTML;
}

/**
 * Gets a list of valid filetypes for <input type="file">
 * @return String list of valid filetypes
 */
function get_valid_filetypes()
{
    $fts = get_supported_filetypes();
    $res = '';
    foreach ($fts as $ft) {
        $res .= ".{$ft['extension']},{$ft['mime']},";
    }

    return $res;
}

echo page_header('Payphone Dashboard');
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
    $db = get_db_connection(SQLITE3_OPEN_READONLY);

    // get all phone numbers, file names, and descriptions
    $entries = $db->query('SELECT number, filename, description '
        . 'FROM numbers ORDER BY substr(number, 0, 2), length(number), number ASC');
    $has_rows = 0;
    // loop until we have no more query results
    while ($row = $entries->fetchArray()) {
        // create a table row for each phone number
        $has_rows = 1;
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
<?php
if ($has_rows === 0) {
    echo '<p class="note">No entries.</p>';
}
?>
</div>
<div id="new-entry-block">
    <h3>Add Entry</h3>
    <form action="update.php" method="POST" enctype="multipart/form-data">
    <input type="text" name="number" placeholder="Phone Number" pattern="^[0-9]+$" minlength=0 maxlength=10>
    <input type="file" name="sound" accept="<?php echo get_valid_filetypes() ?>">
    <input type="text" name="description" placeholder="Description">
    <button type="submit" name="create">Submit</button>
    </form>
<?php
$filesize_limit = ini_get('upload_max_filesize');
echo "<p class=\"note\">File uploads are capped at $filesize_limit.</p>";
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
