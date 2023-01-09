<!DOCTYPE html>

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <title>Payphone Dashboard</title>
    <?php
    function phone_entry($number, $filename, $description) {
        $sound_url = "/sound.php?" . $number;
        echo <<< HTML
        <tr>
            <td>$number</td>
            <td><a href="$sound_url">$filename</a></td>
            <td>$description</td>
            <td>
                <form><input type="submit" value="delete"></form>
            </td>
        </tr>
        HTML;
    }
    ?>
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
        <form action="/" method="POST" enctype="multipart/form-data" border="1">
            <input value="Phone Number" type="text">
            <input value="Sound" type="file">
            <input value="Description" type="text">
            <input value="submit" type="submit">
        </form>
    </div>
</body>