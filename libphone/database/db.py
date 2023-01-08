from os import PathLike
from typing import Union
from sqlite3 import Cursor

"""
Functions for working with the database

:author: Nathan Jankowski (njj3397 [at] rit [dot] edu)
"""


def table_exists(cursor: Cursor, name: str):
    """
    Checks if a table exists

    :param cursor: database cursor
    :param name: table name
    :return: true if table exists
    """
    cursor.execute(
        'SELECT name FROM sqlite_schema WHERE type="table" AND name=?',
        (name,))
    return len(cursor.fetchall()) != 0


def create_entry(cursor: Cursor, number: int, sound: Union[PathLike, str], description='') -> None:
    """
    Creates a database entry for a phone number

    :param cursor: database cursor
    :param number: telephone number
    :param sound: Path to sound file
    :param description: Entry description (optional)
    """
    # TODO control bitrate and size of sound files
    cursor.execute(
        'INSERT INTO numbers(number, sound, filename, description) VALUES (?, ?, ?, ?)',
        (number, file_to_blob(sound), str(sound), description))


def file_to_blob(path: Union[PathLike, str]) -> bytes:
    """
    Open a file as a binary blob, and get the contents

    :param path: string or path-like object to file
    :return: file as binary blob
    """
    with open(path, 'rb') as handle:
        return handle.read()


def fetch_audio(cursor: Cursor, number: int) -> bytes:
    """
    Get audio from a database entry

    :param cursor: database cursor
    :param number: entry phone number
    :return: audio as binary blob
    """
    cursor.execute('SELECT sound FROM numbers WHERE number=?', (number,))
    # there can only be one because number is a unique field
    return cursor.fetchone()[0]
