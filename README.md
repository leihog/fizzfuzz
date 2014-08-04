# FizzFuzz

FizzFuzz is a work in progress RESTful API behavioural and fuzzing testing tool.

You express your API as a simple YAML schema and then run the tool. FizzFuzz will ensure that your API responds as expected for both successful and unsuccessful requests.

## Installation

Include the library in your `composer.json` file:

```
"require-dev": {
    "alexbilbie/fizzfuzz": "dev-develop"
}
```

## Running FizzFuzz

From the root of your project run `vendor/bin/FizzFuzz run /path/to/tests`

To see the generated requests and the API responses you can pass the `-v` flag.

## Creating API descriptions in YAML

This is an example of a really basic request:

```yaml
url: 'http://api.example.com/users/123'
request:
    method: GET
response:
    statusCode: 200
```

It will send a `GET` request to `http://api.example.com/users/123` and expect a `200` response status code.

You can specify expected response headers and body items. You can set the exact expected value or match on value type or with a regular expression:

```yaml
url: 'http://api.example.com/users/123'
request:
    method: GET
response:
    statusCode: 200
    headers:
        -
            key: Content-type
            value: application/json
    body:
        -
            key: id
            value: 123
        -
            key: username
            valueType: string
        -
            key: email
            valueType: string
        -
            key: colour
            valueRegex: /^#?([0-9a-f]{3}){1,2}$/i 
```

You can pass headers and body items to the request:

```yaml
url: 'http://api.example.com/users'
request:
    method: POST
    headers:
        -
            key: Content-type
            value: application/json
        -  
            key: Authorization
            value: Bearer ABC123EFG456
    body:
        -
            key: username
            value: alexbilbie
        -   
            key: password
            value: whisky
response:
    statusCode: 201
    headers:
        -
            key: Content-type
            value: application/json
    body:
        -
            key: id
            valueType: integer
        -
            key: username:
            value: alexbilbie
```

This will send nine POST requests to the endpoint:

1. The request as defined in the YAML
2. The request as defined but without the `Content-type` header
3. The request as defined but the `Content-type` header will have a different value
4. The request as defined but without the `Authorization` header
5. The request as defined but the `Authorization` header will have a different value
6. The request as defined but without the `username` body item
7. The request as defined but the `username` body item will have a different value
8. The request as defined but without the `password` body item
9. The request as defined but the `password` body item will have a different value

You can set expected conditions if values are missing or invalid:

```yaml
url: 'http://api.example.com/users'
request:
    method: POST
    headers:
        -
            key: Content-type
            value: application/json
        -  
            key: Authorization
            value: Bearer ABC123EFG456
            missing:
                response.statusCode: 401
                headers.content-type: application/json
                body.error_message: "Missing access token"
            missing:
                response.statusCode: 401
                headers.content-type: application/json
                body.error_message: "Invalid access token"
    body:
        -
            key: username
            value: alexbilbie
        -   
            key: password
            value: whisky
response:
    statusCode: 201
    headers:
        -
            key: Content-type
            value: application/json
    body:
        -
            key: id
            valueType: integer
        -
            key: username:
            value: alexbilbie
```
