# APITester Configuration Reference
You will find below the whole configuration reference to make APITester more reliable, depending on your context and needs. This whole configuration is applicable to the suite defined in your `api-tester.yaml` file so you can have multiple suites with different configuration.

## Name

```yaml
required: true
type: string
```
name is useful for selecting which

---------------

## Definition
```yaml
definition:
    path: 'path/to/your/openapi/specification-file.yml'
    format: 'openapi'
```
Both `path` and `format` are required. For now we only support OpenAPI format but APITester is designed to support more format in future.

---------------

## Requester

```yaml
required: false
default: http-async
value: [ symfony-kernel, http-async ]
```
By default requests to the tested API will be performed by a `HTTP Client` but, in a Symfony app context, you could choose to perform requests through `Symfony Kernel` instead. It can be useful to have better control on code executed before and after each call, handling transactions for example.

**http-async**

Sends HTTP requests asynchronously through the network.

**symfony-kernel**

Forwards HTTP requests directly to `Symfony Kernel`. It has the advantage of being compatible with transactions (in case it's used in test cases callbacks)
When using `Symfony Kernel` as requester you can specify the Kernel class that would be load to execute requests:

---------------

## SymfonyKernelClass
```yaml
symfonyKernelClass: '\ApiKernel'
```

---------------

## TestCaseClass
APITester generate TestCases to be executed by PHPUnit. Each `TestCase` will inherit by default from `PHPUnit TestCase` class but you can specify your own TestCase class if needed
```yaml
testCaseClass: '\OC\IntegrationTestCase'
```

---------------

## Preparators
These are used to produce test cases, each one based on it's own logic and configuration.
Following the list of supported preparators. Configuration for preparators is optionnal but it can help you to have more control on what is generated and tested.
```yaml
preparators:
```
All preparators share common configuration capabilities. Some preparators have their own configuration too.

Available named Preparator are:
- examples
- error400
- error401
- error403
- error404
- error405
- error406
- error413
- error416
- random

## Common configuration
```yaml
preparators:
    - name: error401
      excludedFields: [ 'body', 'headers' ]
```
`name` is required.

`excludedFields` allow you to specify which fields should be check in TestCases. If you exclude both `body` and `header`, only HTTP Response code will be check.

## Custom configuration

- **ExamplesPreparator**
This preparator will generate TestCases based on example it found in your OpenAPI definition file. You can add more examples from a dedicated file by adding `extensionPath` in its configuration
```yaml
- name: example
  extensionPath: 'path/to/more/examples.yaml'
```
You can find an example of how this looks like here.

- **403Preparator**
This preparator will generate TestCases trying to have 403 error, based on security information found in your OpenAPI definition file like for example:

```yaml
security:
  -
    OAuth2:
      - openclassrooms_client
```

It generates TestCases withn tokens informations found in [`auth`](#authentication) section of your configuration.

```yaml
- name: error403
  excludedTokens: ['token1', 'token2']
```

- **405Preparator**

- **406Preparator**


[all preparators configuration](preparators-config.md)

## filters

```yaml
required: false
```

## include

```yaml
required: false
type: array<object>
```

List of operations to include depending on the selected criteria.

## exclude

```yaml
required: false
type: array<object>
```

List of operations to exclude depending on the selected criteria.

## authentication

```yaml
required: false
type: array<object>
```

Handles authentication, which produces a list of tokens that are

## name

```yaml
required: yes
type: string
```

## type

```yaml
required: yes
type: [ oauth2_password, oauth2_implicit ] #other auth methods still need support
```

## Full example

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
