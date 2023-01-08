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
                        required=True, metavar='entry',
                        help='phone_number path/to/sound description(optional)')

    args = parser.parse_args()
    entries = []

    # validate and parse files
    for entry in args.entries:
        if 3 < len(entry) < 2:
            print(f'Invalid arguments {entry}')
            sys.exit(1)
        entries.append(
            (int(entry[0]), entry[1], '' if len(entry) == 2 else entry[2])
        )

    # set up database
    connection = sqlite3.connect(DB_LOCATION)
    cursor = connection.cursor()

    # initialize database
    if not database.table_exists(cursor, 'numbers'):
        print('Initializing table numbers')
        cursor.execute('''CREATE TABLE numbers (
                            id INTEGER PRIMARY KEY, 
                            number INTEGER UNIQUE, 
                            sound BLOB,
                            filename TEXT,
                            description TEXT
                            )''')

        connection.commit()
    else:
        print('Database already initialized!')

    # add entries
    for entry in entries:
        print(f'Adding entry {entry}')
        try:
            database.create_entry(cursor, entry[0], entry[1], entry[2])
        except sqlite3.IntegrityError as e:
            print(f'Failed to add entry!  Does the number already exist?  {e}')
    connection.commit()

    # clean up
    cursor.close()
    connection.close()
    print('Cleaned up; database initialized!')
