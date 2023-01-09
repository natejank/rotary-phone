# Rotary Payphone Hacking Project

## todo

[] config file for database location
[] control bitrate and size of sound files

## Table schema

### Phone number entries

Name: `numbers`

| keys         | type                  | related to |
| :-----       | :-------------------- | :--------- |
| id           | `INTEGER PRIMARY KEY` |            |
| number       | `UNIQUE INTEGER`      |            |
| sound        | `BLOB`                |            |
| filename     | `TEXT`                |            |
| description  | `TEXT`                |            |
