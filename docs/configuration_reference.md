# APITester Configuration Reference

This document provides a comprehensive reference for configuring APITester. The configuration is structured into **Root Configuration** and **Suite Configuration**.

## Root Configuration

The top-level configuration defines global settings and the list of test suites.

```yaml
bootstrap: 'tests/bootstrap.php' # Optional: Path to a bootstrap file to include before running tests
suites: # Required: List of test suites
    - ...
```

---

## Suite Configuration

Each suite in the `suites` list can be configured with the following options:

### Name

```yaml
name: 'my-suite' # Required
```
The name is used to select which suite to run via the command line (e.g., `--suite=my-suite`).

### Definition

Defines the source of the API specification (e.g., OpenAPI / Swagger).

```yaml
definition:
    path: 'path/to/openapi.yaml' # Required: Path or URL to the definition file
    format: 'openapi'            # Required: Format of the definition (currently only 'openapi' is supported)
```

### Base URL

Overrides the host defined in the OpenAPI specification.

```yaml
baseUrl: 'http://localhost:8080' # Optional
```

### Requester

Determines how HTTP requests are executed.

```yaml
requester: 'http-async' # Optional, default: 'http-async'
```

Available values:
- `http-async`: Sends real HTTP requests over the network asynchronously.
- `symfony-kernel`: Forwards requests directly to the Symfony Kernel (requires `symfonyKernelClass`). Useful for testing within a Symfony application context (e.g., handling transactions).

#### Symfony Kernel Configuration

If `requester` is set to `symfony-kernel`, you must specify the Kernel class.

```yaml
symfonyKernelClass: '\App\Kernel'
```

### Test Case Class

Specifies the PHPUnit TestCase class that generated tests will extend.

```yaml
testCaseClass: '\OC\IntegrationTestCase' # Optional, default: '\PHPUnit\Framework\TestCase'
```

### PHPUnit Config

Path to a specific PHPUnit XML configuration file for this suite.

```yaml
phpunitConfig: 'phpunit.xml' # Optional
```

---

## Filters

Filters allow you to select which API operations to test based on their properties (tags, operationId, etc.).

```yaml
filters:
    include: ...
    exclude: ...
    baseline: 'api-tester.baseline.yaml'
    schemaValidationBaseline: 'api-tester.schema-baseline.yaml'
```

### Include / Exclude

Include or exclude operations. Rules are matched against operation properties.

```yaml
include:
    - { tags.*.name: 'User' } # Include operations with 'User' tag
exclude:
    - { id: 'delete_user' }   # Exclude operation with id 'delete_user'
```

### Baselines

- `baseline`: Path to a file containing a list of failed tests to ignore in future runs. Generated via `--set-baseline` or `--update-baseline`.
- `schemaValidationBaseline`: Path to a file containing schema validation errors to ignore.

---

## Preparators

Preparators are responsible for generating test cases for each API operation. You can configure them to customize the generated tests.

```yaml
preparators:
    - name: 'error400'
      # ... config ...
```

Available preparators: `examples`, `error400`, `error401`, `error403`, `error404`, `error405`, `error406`, `error413`, `error416`, `random`, `pagination_error`, `security_error`.

### Common Configuration

All preparators support the following configuration:

```yaml
- name: 'error400'
  excludedFields: ['email', 'password'] # Fields to exclude/ignore during generation
  schemaValidation: false               # Disable response schema validation for this preparator
  headers:                              # Custom headers to inject
      X-Custom-Header: 'value'
  response:                             # Assertions on the response
      statusCode: 400                   # Expected status code (can use regex or 'NOT' tag)
      body: '...'                       # Expected body content (regex support)
      headers:                          # Expected response headers
         Content-Type: 'application/json'
```

---

## Auth

Handles authentication for the requests. You can define multiple authentication strategies.

```yaml
auth:
    - name: 'admin_user'
      type: 'oauth2_password' # Currently supported: oauth2_password, oauth2_implicit
      # ... type specific config ...
      body:
          username: 'admin'
          password: 'password'
          grant_type: 'password'
          client_id: '...'
      headers:
          Authorization: 'Basic ...'
      filters: # Optional: Apply this auth only to specific operations
          include:
              - { tags.*.name: 'Admin' }
```

---

## Full Example

```yaml
bootstrap: 'tests/bootstrap.php'

suites:
    -   name: 'main'
        definition:
            path: 'openapi.yaml'
            format: 'openapi'
        baseUrl: 'http://localhost:8000'
        requester: 'symfony-kernel'
        symfonyKernelClass: '\App\Kernel'
        testCaseClass: '\App\Tests\ApiTestCase'
        
        filters:
            baseline: 'tests/api-tester.baseline.yaml'
            include:
                - { tags.*.name: 'Public' }
                - { tags.*.name: 'Private' }
            exclude:
                - { id: 'deprecated_operation' }

        auth:
            -   name: 'user'
                type: 'oauth2_password'
                body:
                    username: '%env(TEST_USER)%'
                    password: '%env(TEST_PASSWORD)%'
                    grant_type: 'password'
                    client_id: 'app_client'
                headers:
                    Authorization: 'Basic Y2xpZW50OnNlY3JZXdA=='
        
        preparators:
            -   name: 'examples'
                schemaValidation: true
            -   name: 'error400'
                excludedFields: ['id', 'created_at']
            -   name: 'error403'
                response:
                    statusCode: 403
```
