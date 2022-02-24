![Coverage](../coverage/coverage.svg?raw=true)
![PHPStan](https://img.shields.io/badge/PHPStan-level%207-brightgreen.svg?style=flat)
![PHP](https://img.shields.io/badge/PHP-%3E=%207.4-brightgreen.svg?style=flat)

# Auto API Testing

This is project is aimed, to improve the testing experience by providing automatic tests based on your OpenAPI document.

## Installation

require into an existing project
`composer require openclassrooms/open-api-testing`

or require globally
`composer require -g openclassrooms/open-api-testing`

## Usage

`./bin/api-tester launch -vvv -c tests/Fixtures/Config/api-tester.yaml`

## Advanced Usage

An example of an integration with symfony kernel and phpunit

```php
    public function test(): void
    {
        //need to create a plan
        $testPlan = new Plan();
        
        //we init the symfony kernel request passing our app kernel 
        $testPlan->addRequester(new SymfonyKernelRequester($this->getKernel()))
        
        //init the logger
        $testPlan->setLogger(
            new ConsoleLogger(new ConsoleOutput(OutputInterface::VERBOSITY_VERY_VERBOSE))
        );
       
        //init the plan config
        $config = PlanConfigLoader::load(__DIR__ . '/api-tester.yaml');
        
        //we can pass some callbacks to be executed before/after each test
        $config->addBeforeTestCaseCallback([$this, 'init']);
        $config->addAfterTestCaseCallback([$this, 'shutdown']);
        
        //we execute the test plan with the loaded config
        $testPlan->execute($config);
        
        //we call assertions which use phpunit assertions to check that all tests are passing
        $testPlan->assert();
    }
```

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
type: [ 'openapi' ] # for now we support only the openapi format
```

### requester

```yaml
required: false
default: 'http-async'
type: [ 'symfony-kernel', 'http-async' ]
```

#### symfony-kernel

Forwards http requests directly to symfony kernel, has the advantage of being compatible with transactions (in case it's
used in test cases callbacks)

#### http-async

Sends http requests asynchronously through the network.

### preparators

```yaml
required: false
default: all
```

Theses are used to produce test cases, each one based on it's own logic and configuration.
Following the list of supported preparators.

#### examples

#### error400

#### error401

#### error403

#### error404

#### error405

#### error406

#### error413

#### error416

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
type: [ 'oauth2_password', 'oauth2_implicit' ] #other auth methods still need support
```

### Full example

```yaml
suites:
    -   name: 'all' # name of suite, can be used to select which suite to launch
        definition:
            path: 'src/OC/ApiBundle/Resources/schema/openclassrooms-api.yml' # path/url of the definition document
            format: 'openapi' # type of the definition
        requester: 'symfony-kernel' # how requests are executed, default: http-async
        preparators: # are responsible of preparing test cases
            -   name: error400 # which preparator
                excludedFields: [ 'stream' ]
            -   name: error401
                excludedFields: [ 'stream' ]
            -   name: error403
                excludedFields: [ 'stream' ]
            -   name: error404
                excludedFields: [ 'stream' ]
        filters: # select which operations to test, filters are on operations properties
            #please refer to the class OpenAPITesting\Definition\Operation
            include: # we include operations with the following tags
                - {tags.*.name: Invitation}
                - {tags.*.name: Support}
                - {tags.*.name: Project}
                - {tags.*.name: Course}
            exclude: 
                # we exclude an operation with it's id for the error404 preparator
                - {id: oc_api_learning_activity_course_chapter_complete_post, preparator: error404}
                - {id: oc_api_invitation_invitations_get, preparator: error401}
                - {id: oc_api_invitation_invitations_get, preparator: error403}

        auth: # authentication configuration
            -   name: 'user_with_all_roles'
                type: 'oauth2_password'
                username: 'tech-tests+1111111@openclassrooms.com'
                password: 'test'
                scopes: [ 'openclassrooms_client', 'learning_content', 'user_learning_activity' ]
                headers:
                    Authorization: 'Basic b2ZBbDhoVXh2UEx5Mzh5Z0RRMXN6QU9SOmdQbFd0YWlIZ245RDZleUhyOTBLbTBsaWpiVlM2bQ=='
                    Accept: 'application/json'
                    Content-Type: 'application/json'
            -   name: 'user_without_any_scope'
                type: 'oauth2_password'
                username: 'tech-tests+1111111@openclassrooms.com'
                password: 'test'
                scopes: [ 'learning_content' ]
                headers:
                    Authorization: 'Basic b2ZBbDhoVXh2UEx5Mzh5Z0RRMXN6QU9SOmdQbFd0YWlIZ245RDZleUhyOTBLbTBsaWpiVlM2bQ=='
                    Accept: 'application/json'
                    Content-Type: 'application/json'

```

## Contribution
