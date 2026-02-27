# Common Configuration

All preparators share the following common configuration options:

```yaml
- name: 'preparator_name'
  excludedFields: ['field1', 'field2'] # Optional: Fields to exclude from checks
  schemaValidation: true               # Optional: Enable/Disable schema validation (default: true)
  headers:                             # Optional: Custom headers to inject in requests
      X-Custom-Header: 'value'
  response:                            # Optional: Assertions on the response
      statusCode: 400                  # Expected status code (int or regex)
      body: '...'                      # Expected body content
      headers:                         # Expected response headers
          Content-Type: 'application/json'
```

---

# Specific Configuration

## examples

Generates test cases based on examples found in your OpenAPI definition.

```yaml
name: examples
extensionPath: 'path/to/more/examples.yaml' # Optional: Path to external examples file
autoComplete: false                         # Optional: Auto-complete missing fields (default: false)
autoCreateWhenMissing: false                # Optional: Create examples if missing (default: false)
```

## error400

Generates requests with invalid data to trigger 400 Bad Request.

```yaml
name: error400
# (Uses only common configuration)
```

## error401

Generates requests without authentication to trigger 401 Unauthorized.

```yaml
name: error401
# (Uses only common configuration)
```

## error403

Generates requests with insufficient permissions to trigger 403 Forbidden.

```yaml
name: error403
excludedTokens: ['token_name_to_skip'] # Optional: List of tokens to exclude from testing
```

## error404

Generates requests to non-existent resources/IDs to trigger 404 Not Found.

```yaml
name: error404
# (Uses only common configuration)
```

## error405

Generates requests with invalid HTTP methods to trigger 405 Method Not Allowed.

```yaml
name: error405
methods: # Optional: List of methods to test (defaults to all standard methods)
    - 'GET'
    - 'POST'
    # ...
```

## error406

Generates requests with invalid `Accept` headers to trigger 406 Not Acceptable.

```yaml
name: error406
mediaTypes: [] # Optional: List of invalid media types to test
casesCount: 3  # Optional: Number of test cases to generate (default: 3)
```

## error413

Generates requests with content/parameters that are too large to trigger 413 Payload Too Large.

```yaml
name: error413
range: # Optional: Define ranges for pagination/size testing
    - type: query       # or 'header'
      name: 'limit'     # name of the parameter/header
      lower: 0
      upper: 100
```

## error416

Generates requests with invalid ranges to trigger 416 Range Not Satisfiable.

```yaml
name: error416
range: # Optional: Define ranges
    - type: header
      name: 'Range'
      unit: 'bytes'
      lower: 0
      upper: 100
```

## random

Generates requests with random valid data.

```yaml
name: random
casesCount: 3 # Optional: Number of random variations to generate (default: 3)
```

## pagination_error

Generic preparator for pagination errors. Used as base for 413/416 but can be used directly.

```yaml
name: pagination_error
range: ... # Same as error413/error416
```

## security_error

Generic preparator for security errors.

```yaml
name: security_error
# (Uses only common configuration)
```