# Rotary Payphone Hacking Project

## todo

[] initialization file
  - [] config file for database location
  - [] seed with entries
  - [] create cli api

## Table schema

### Phone number entries

Name: `numbers`

| keys    | type                  | related to |
| :-----  | :-------------------- | :--------- |
| id      | `INTEGER PRIMARY KEY` |            |
| number  | `INTEGER`             |            |

### Phone number sounds

Name: `sound`

| keys    | type                  | related to |
| :-----  | :-------------------- | :--------- |
| id      | `INTEGER PRIMARY KEY` | entries.id |
| sound   | `BLOB`                |            |

### Phone number descriptions

Name: `descriptions`

| keys         | type                  | related to |
| :-----       | :-------------------- | :--------- |
| id           | `INTEGER PRIMARY KEY` | entries.id |
| description  | `TEXT`                |            |
