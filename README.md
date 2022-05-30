![Coverage](../coverage/coverage.svg?raw=true)
![PHPStan](https://img.shields.io/badge/PHPStan-level%207-brightgreen.svg?style=flat)
![PHP](https://img.shields.io/badge/PHP-%3E=%207.4-brightgreen.svg?style=flat)

# API Tester

This is project is aimed, to improve the testing experience by providing
automatic tests based on your OpenAPI document.

## Installation

require into an existing project
`composer require openclassrooms/api-tester`

or require globally
`composer require -g openclassrooms/api-tester`

## Usage

### Run tests
`./bin/api-tester -c api-tester.yaml`

### Help 
`./bin/api-tester --help`

## Configuration Reference

### Name

```yaml
required: true
type: string
```

name is useful for selecting which

### definition

#### path

```yaml
required: true
type: string
```

#### format

```yaml
required: true
type: [ openapi ] # for now we support only the openapi format
```

### requester

```yaml
required: false
default: http-async
type: [ symfony-kernel, http-async ]
```

#### symfony-kernel

Forwards http requests directly to symfony kernel, has the advantage of being
compatible with transactions (in case it's
used in test cases callbacks)

#### http-async

Sends http requests asynchronously through the network.

### preparators

```yaml
required: false
default: all
```

Theses are used to produce test cases, each one based on it's own logic and
configuration.
Following the list of supported preparators.

#### examples

#### error400

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

#### error401

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

#### error403

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

#### error404

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

#### error405

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

#### error406

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

#### error413

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

#### error416

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

### filters

```yaml
required: false
```

#### include

```yaml
required: false
type: array<object>
```

List of operations to include depending on the selected criteria.

#### exclude

```yaml
required: false
type: array<object>
```

List of operations to exclude depending on the selected criteria.

### authentication

```yaml
required: false
type: array<object>
```

Handles authentication, which produces a list of tokens that are

#### name

```yaml
required: yes
type: string
```

#### type

```yaml
required: yes
type: [ oauth2_password, oauth2_implicit ] #other auth methods still need support
```

### Full example

```yaml
suites:
    -   name: 'all' # name of suite, can be used to select which suite to launch
        definition:
            path: 'src/OC/ApiBundle/Resources/schema/openclassrooms-api.yml' # path/url of the definition document
            format: 'openapi' # type of the definition
        requester: 'symfony-kernel' # how requests are executed, default: http-async
        preparators: # are responsible of preparing test cases, leaving them empty will load all prepartors with optional config
            -   name: error400 # which preparator
                excludedFields: [ 'body' ]
            -   name: error401
                excludedFields: [ 'body' ]
            -   name: error403
                excludedFields: [ 'body' ]
            -   name: error404
                excludedFields: [ 'body' ]
        filters: # select which operations to test, filters are on operations properties
            #please refer to the class APITester\Definition\Operation
            include: # we include operations with the following tags
                - { tags.*.name: Invitation }
                - { tags.*.name: Support }
                - { tags.*.name: Project }
                - { tags.*.name: Course }
            exclude:
                # we exclude an operation with it's id for the error404 preparator
                - { id: oc_api_learning_activity_course_chapter_complete_post, preparator: error404 }
                - { id: oc_api_invitation_invitations_get, preparator: error401 }
                - { id: oc_api_invitation_invitations_get, preparator: error403 }
        
        auth: # authentication requests configuration
          - name: 'user_1'
            body:
              grant_type: 'password'
              scope: 'scope 1'
              username: 'tech-tests+1111111@openclassrooms.com'
              password: 'test'
            headers:
              Authorization: 'Basic xxx'
              Accept: 'application/json'
              Content-Type: 'application/json'
          - name: 'user_2'
            body:
              grant_type: 'client_credentials'
              scope: 'scope2 scope3'
            headers:
              Authorization: 'Basic xxx'
              Accept: 'application/json'
              Content-Type: 'application/json'

```
