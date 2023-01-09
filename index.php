<!DOCTYPE html>

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <title>Payphone Dashboard</title>
    <?php
    function phone_entry($number, $filename, $description) {
        $sound_url = "sound.php?number=$number";
        echo <<< HTML
        <tr>
            <td>$number</td>
            <td><a href="$sound_url">$filename</a></td>
            <td>$description</td>
            <td>
                <form action="" method="POST"><input type="submit" name="$number" value="delete"></form>
            </td>
        </tr>
        HTML;
    }
    ?>
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
                // TODO get location from configuration
                $db = new SQLite3('phone.db', SQLITE3_OPEN_READONLY);
                $entries = $db->query('SELECT number, filename, description FROM numbers ORDER BY number ASC');
                while ($row = $entries->fetchArray()) {
                    phone_entry($row['number'], $row['filename'], $row['description']);
                }
                $db->close();
                ?>
            </tbody>
        </table>
        <h3>Add Entry</h3>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="text" name="number" placeholder="Phone Number" pattern="^[0-9]+$" minlength=0 maxlength=10>
            <input type="file" name="sound">
            <input type="text" name="description" placeholder="Description">
            <input type="submit" name="submit" value="submit">
        </form>
    </div>
</body>