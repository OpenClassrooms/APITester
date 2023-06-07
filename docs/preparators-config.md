## examples

## error400

```yaml
excludedFields:
    type: array
    description: list of response fields to be excluded when checking
response:
    body:
        type: string
        default: null
        description: the exact body to be checked
    statusCode:
        type: int
        default: 400
        description: the exact statusCode to be checked
```

## error401

```yaml
excludedFields:
    type: array
    description: list of response fields to be excluded when checking
response:
    body:
        type: string
        default: null
        description: the exact body to be checked
    statusCode:
        type: int
        default: 401
        description: the exact statusCode to be checked
```

## error403

```yaml
excludedFields:
    type: array
    description: list of response fields to be excluded when checking
response:
    body:
        type: string
        default: null
        description: the exact body to be checked
    statusCode:
        type: int
        default: 403
        description: the exact statusCode to be checked
```

## error404

```yaml
excludedFields:
    type: array
    description: list of response fields to be excluded when checking
response:
    body:
        type: string
        default: null
        description: the exact body to be checked
    statusCode:
        type: int
        default: 404
        description: the exact statusCode to be checked
```

## error405

```yaml
methods:
    type: [ GET, POST, PATCH, PUT, DELETE, HEAD, OPTIONS, TRACE, CONNECT ]
    default: all
    description: methods to validate against
excludedFields:
    type: array
    description: list of response fields to be excluded when checking
response:
    body:
        type: string
        default: null
        description: the exact body to be checked
    statusCode:
        type: int
        default: 400
        description: the exact statusCode to be checked
```

## error406

```yaml
excludedFields:
    type: array
    description: list of response fields to be excluded when checking
response:
    body:
        type: string
        default: null
        description: the exact body to be checked
    statusCode:
        type: int
        default: 400
        description: the exact statusCode to be checked
```

## error413

```yaml
range:
    type: array<object>
    description: describes how pagination is handled by the api
excludedFields:
    type: array
    description: list of response fields to be excluded when checking
response:
    body:
        type: string
        default: null
        description: the exact body to be checked
    statusCode:
        type: int
        default: 400
        description: the exact statusCode to be checked
```

## error416

```yaml
range:
    type: array<object>
    description: describes how pagination is handled by the api
excludedFields:
    type: array
    description: list of response fields to be excluded when checking
response:
    body:
        type: string
        default: null
        description: the exact body to be checked
    statusCode:
        type: int
        default: 400
        description: the exact statusCode to be checked
```