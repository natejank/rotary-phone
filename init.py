import sqlite3
import pathlib

"""
Create and initialize phone database with entries

:author: Nathan Jankowski (njj3397 [at-sign] rit [dot] edu)
"""

DB_LOCATION = 'phone.db'

def table_exists(cursor, name):
    """
    Checks if a table exists
    
    :param cursor: database cursor
    :param name: name of table
    :return: true if table exists
    """
    cursor.execute(
        'SELECT name FROM sqlite_schema WHERE type="table" AND name=?;', 
        (name,))
    return len(cursor.fetchall()) != 0


def create_table(cursor, name, description):
    """
    Creates a table from a given name and description.
    **This is not a safe operation; do not use this with user input!**
    
    :param cursor: database cursor
    :param name: database name
    :param description: database description
    """
    cursor.execute(f'CREATE TABLE {name} {description};')


def ensure_table(cursor, name, description):
    """
    Check if a table exists; create it if not.
    **This is not a safe operation, do not use this with user input!**
    
    :param cursor: database cursor
    :param name: database name
    :param description: database description
    """
    if not table_exists(cursor, name):
        print(f'{name} does not exist; creating!')
        create_table(cursor, name, description)

    
if __name__ == '__main__':
    connection = sqlite3.connect(DB_LOCATION)
    ensure_table(
        connection.cursor(), 
        'numbers', 
        '(id INTEGER PRIMARY KEY, number INTEGER)'
    )
    ensure_table(
        connection.cursor(),
        'sound',
        '(id INTEGER PRIMARY KEY, sound BLOB)'
    )
    ensure_table(
        connection.cursor(),
        'number_description',
        '(id INTEGER PRIMARY KEY, description TEXT)'
    )
    
    connection.close()
