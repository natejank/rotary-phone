<!DOCTYPE html>
<?php
    // TODO acquire db location from config file
    
    // global notification variable
    $notify = "";

    function phone_entry($number, $filename, $description) {
        $sound_url = "sound.php?number=$number";
        echo <<< HTML
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

    // form handling
    if (isset($_POST['delete'])) {
        // we're deleting an entry
        $number = $_POST['delete'];
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
                $notify .= '<h2 class="error">'
                        . "Failed to delete entry $number!  "
                        . 'Query failed.</h2>';
            }

            // close connection
            $db->close();

            // provide user feedback
            $notify .= "<h3>Deleted entry $number!</h3>";
        } catch (Exception $e) {
            // catch exception on DB connection and notify user
            $notify .= '<h2 class="error">'
                    . "Failed to delete entry $number!  "
                    . 'Could not connect to database.  Please contact your System Adminstrator.</h2>';
        }

    } else if (isset($_POST['create'])) {
        // we're creating a new entry
    }
?>

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <title>Payphone Dashboard</title>
</head>

<body>
    <?php
    print_r($_POST);
    print_r($_GET);
    ?>
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
                    $entries = $db->query('SELECT number, filename, description FROM numbers ORDER BY number ASC');
                    // loop until we have no more query results
                    while ($row = $entries->fetchArray()) {
                        // create a table row for each phone number
                        phone_entry($row['number'], $row['filename'], $row['description']);
                    }

                    // close db connection
                    $db->close();
                } catch (Exception $e) {
                    // handle error connecting to db; notify user
                    $notify .= '<h2 class="error">Failed to query numbers!  '
                            . 'Could not connect to database.  '
                            . 'Please contact your System Adminstrator.</h2>';
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
</body>