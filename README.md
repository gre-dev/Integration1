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



### `InvalidArgumentException`:
| Code | Description                                                           |
|------|-----------------------------------------------------------------------|
| 3    | email argument is not valid (either not an email string, or is empty) |
| 4    | password argument is not good (empty string)                          |
| 5    | user name argument is not valid (empty string)                        |


### `Exception`
| Code | Description                                 |
|------|---------------------------------------------|
| 6    | the given api key doesn't exist in database |
| 7    | the given log type is empty string          |


