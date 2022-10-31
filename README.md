# Exceptions:
`SessionException` error codes.

## Notes:
- You should pass error number to `SessionException` once you throw it, don't pass a string message to `SessionException` as it will not work and will not construct the exception.
- Passing number other than the listed will make exception return string with explainations, avoid it please and implement your exception class inherited from `SessionException` instead.

## Error Codes:

|  Code |  Description |
|---|---|
|  `ERR_CODE_DATA_NOT_FOUND`  |  this is used when you expect some fields in session and you can't find them  |