# Exceptions:
`SessionException` error codes.

## Notes:
- You should pass error number to `SessionException` once you throw it, don't pass a string message to `SessionException` as it will not work and will not construct the exception.
- Passing number other than the listed will make exception return string with explainations, avoid it please and implement your exception class inherited from `SessionException` instead.

## Error Codes:

### `SessionException`:
| Code | Description                            |
|------|----------------------------------------|
| 1    | 'login_email' is missing in session    |
| 2    | 'login_password' is missing in session |
| 15   | 'login_token' is missing in session    |


	


### `InvalidArgumentException`:
| Code | Description                                                           |
|------|-----------------------------------------------------------------------|
| 3    | email argument is not valid (either not an email string, or is empty) |
| 4    | password argument is not good (empty string)                          |
| 5    | user name argument is not valid (empty string)                        |
| 6    | the given log type is empty string                                    |
| 7    | the given log value is empty string                                   |



### `Exception`
| Code | Description                                    |
|------|------------------------------------------------|
| 14   | the given api key id doesn't exist in database |


### `DBException`
| Code | Description                                                                         |
|------|-------------------------------------------------------------------------------------|
| 8    | wrong database connection data  (hostname, username & password)                     |
| 9    | general error in a select query                                                     |
| 10   | general error in an insert query                                                    |
| 11   | general error in an update query                                                    |
| 12   | general error in a delete query                                                     |
| 13   | unknown error in the database process (you should set the message, otherwise no way |

     
### API error codes:

| Code | Description                                            |
|------|--------------------------------------------------------|
| 15   | login credentials are not correct                      |
| 16   | firstname (or lastname) is not provided in api request |
| 17   | current operation required to be logged in             |







