import sys
import argparse
import sqlite3

from libphone import database

"""
Create and initialize database with entries

:author: Nathan Jankowski (njj3397 [at] rit [dot] edu)
"""

# TODO find a more central location for this
DB_LOCATION = 'phone.db'

if __name__ == '__main__':
    # argument parsing
    parser = argparse.ArgumentParser(
        prog='initialize.py',
        description='Initialize the database with values'
    )
    parser.add_argument('-e', '--entry',
                        nargs='+', action='append', dest='entries',
                        required=False, metavar='entry',
                        help='phone_number path/to/sound description(optional)')

    args = parser.parse_args()
    entries = []

    # validate and parse files
    if args.entries is not None:
        for entry in args.entries:
            phone_number = entry[0]
            file_name = entry[1]
            description = '' if len(entry) == 2 else entry[2]

            # make sure we have proper number of arguments
            if 3 < len(entry) < 2:
                print(f'Invalid arguments {entry}')
                sys.exit(1)

            entries.append((phone_number, file_name, description))

    # set up database
    connection = sqlite3.connect(DB_LOCATION)
    cursor = connection.cursor()

    # initialize database
    if not database.table_exists(cursor, 'numbers'):
        print('Initializing table numbers')
        cursor.execute('''CREATE TABLE numbers (
                          id          INTEGER PRIMARY KEY,
                          number      TEXT    UNIQUE CHECK(number GLOB "[0-9]*" AND length(number) <= 10),
                          sound       BLOB,
                          filename    TEXT,
                          description TEXT
                          )''')

        connection.commit()
    else:
        print('Database already initialized!')

    # add entries
    for entry in entries:
        print(f'Adding entry {entry}')
        # ensure phone number is valid
        # TODO configure phone number length in config
        phone_number = entry[0]
        if not phone_number.isnumeric():
            print(f'Invalid phone number {phone_number}')
            continue

        l = len(phone_number)
        if l < 1 or l > 10:
            print('Phone number has invalid length!')
            continue
        try:
            database.create_entry(cursor, entry[0], entry[1], entry[2])
        except sqlite3.IntegrityError as e:
            print(f'Failed to add entry!  Does the number already exist?  {e}')
    connection.commit()

    # clean up
    cursor.close()
    connection.close()
    print('Cleaned up; database initialized!')
