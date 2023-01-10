<!DOCTYPE html>
<?php
    // TODO acquire db location from config file
    // TODO find a better way of globally tracking notifications
    // TODO implement error handler to streamline error reporting

    /**
     * Global notification variable
     */
    $notify = "";

    /**
     * Escapes any "special" html characters to combat cross-site scripting.
     * 
     * @param String $content content to escape
     * @return String escaped content
     */
    function sanitize_html($content) {
        // sanitize user input to prevent XSS
        return htmlentities($content, ENT_QUOTES);
    }

    /**
     * Converts a string into an "error" tag with proper tags and styling
     * 
     * @param String $message message enclosed in error tag
     * @return String message in error tag
     */
    function error_msg($message) {
        return '<h2 class="error">'
                . $message
                . '</h2>';
    }

    /**
     * Gets file contents as a binary blob
     */
    function get_blob_contents($path) {
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
    function phone_entry($number, $filename, $description) {
        $sound_url = "sound.php?number=$number";
        return <<< HTML
        <tr>
            <td>$number</td>
            <td><a href="$sound_url">$filename</a></td>
            <td>$description</td>
            <td>
                <form action="" method="POST"><button type="submit" name="delete" value="$number">Delete</button></form>
            </td>
        </tr>
        HTML;
    }

    /**
     * Check if a POST request was made to delete a database entry.
     */
    function delete_entry() {
        $notify = '';
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
                $notify .= error_msg("Failed to delete entry $number!  Query failed.");
            } else {
                // provide user feedback
                $notify .= "<h3>Deleted entry $number!</h3>";
            }

            // close connection
            $db->close();
        } catch (Exception $e) {
            // catch exception on DB connection and notify user
            $notify .= error_msg(
                "Failed to delete entry $number!  "
                . 'Could not connect to database.  '
                . 'Please contact your System Adminstrator.');
        }
        return $notify;
    }

    /**
     * Check if a POST request was made to create a database entry.
     */
    function create_entry() {
        $notify = '';
        // we're creating a new entry
        // TODO size constraint for files; get from config?
        // TODO file type constraints; get from config?
        $file = $_FILES['sound'];
        // make file size limit more visible
        if (! isset($file['tmp_name']) | $file['tmp_name'] === '') {
            $notify .= error_msg('Server error!  '
                                . 'Did not recieve the uploaded file.  '
                                . 'Is your file larger than the upload size limit? '
                                . 'Failed to create entry.');
            return $notify;
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
            $notify .= error_msg('Failed to create entry.  '
                                . "Phone number $number may only contain numeric symbols.");
            return $notify;
        } elseif (strlen($number_unsafe) > 10 | strlen($number_unsafe) < 1) {
            $notify .= error_msg("Failed to create entry.  "
                                . 'Length must be greater than 1 and less than 10.');
            return $notify;
        }

        try {
            // connect to database
            $db = new SQLite3('phone.db', SQLITE3_OPEN_READWRITE);
            // create a prepared statement
            $stmt = $db->prepare('INSERT INTO numbers(number, sound, filename, description) '
                                . 'VALUES (:num, :sound, :filename, :desc)');
            $stmt->bindValue(':num', $number_unsafe, SQLITE3_INTEGER);
            $stmt->bindValue(':sound', $sound_content_unsafe, SQLITE3_BLOB);
            $stmt->bindValue(':filename', $filename_unsafe, SQLITE3_TEXT);
            $stmt->bindValue(':desc', $description_unsafe, SQLITE3_TEXT);
            //execute prepared statement
            $result = $stmt->execute();

            if ($result === false) {
                $notify .= error_msg('Failed to create entry.  Query failed.');
            } else {
                $notify .= "<h3>Created entry $number</h3>";
            }

            // close connection
            $db->close();
        } catch (Exception $e) {
            $notify .= error_msg('Failed to create database entry.  '
                                . 'Could not connect to database.  '
                                . 'Please contact your System Administrator.');
            return $notify;
        }
        return $notify;
    }

    // form handling
    if (isset($_POST['delete'])) {
        // we're deleting an entry
        delete_entry();

    } else if (isset($_POST['create'])) {
        $notify .= create_entry();
    }
?>

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <title>Payphone Dashboard</title>
</head>

<body>
    <div id="title-block" class="title-block">
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
                                        . 'FROM numbers ORDER BY number ASC');
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
                    // handle error connecting to db; notify user
                    $notify .= error_msg('Failed to query numbers!  '
                                        . 'Could not connect to database.  '
                                        . 'Please contact your System Administrator.');
                }
                ?>
            </tbody>
        </table>
        <h3>Add Entry</h3>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="text" name="number" placeholder="Phone Number" pattern="^[0-9]+$" minlength=0 maxlength=10>
            <input type="file" name="sound">
            <input type="text" name="description" placeholder="Description">
            <button type="submit" name="create">Submit</button>
        </form>
    </div>
    <div id="notify-block">
        <?php 
        // block to show user notifications
        if ($notify !== '') {
            // show header for context if we have notices to show
            echo "<h2>Notices</h2>";
            echo $notify;
        }
        ?>
    </div>
    <footer>
        <?php
        $filesize_limit = ini_get('upload_max_filesize');
        echo "<p><i>File uploads are capped at $filesize_limit.</i></p>";
        ?>
    </footer>
</body>