# Rotary Payphone Hacking Project

## todo

- [] config file for database location
- [] control bitrate and size of sound files
- [] target and ensure compatibility with debian stable
- [] learn how to use css and center things properly

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
