![Coverage](../coverage/coverage.svg?raw=true)
![PHPStan](https://img.shields.io/badge/PHPStan-level%207-brightgreen.svg?style=flat)
![PHP](https://img.shields.io/badge/PHP-%3E=%208.1-brightgreen.svg?style=flat)

# API Tester

This project is aimed to improve the testing experience by providing
automatic tests based on your OpenAPI specification. It is based on PHPUnit so it support all of it's feature, like coverage for example.

It also provides special support for symonfy kernel, providing a mode where it uses directly the kernel to send requests instead of an http client, which is useful for transactions support for example.

## Installation

Require it locally to use on your current project
`composer require openclassrooms/api-tester`

Or require it globally
`composer require -g openclassrooms/api-tester`

## Usage

### Minimum configuration

To run tests you must create `api-tester.yaml` file at the root of your project and add this minimum configuration in the created file.

```yaml
# ./api-tester.yaml
suites:
  - name: 'all' #you can use any name you want to identify your suites
    definition:
      path: 'openapi.yml' #any path to get your OpenAPI specifications file
      format: 'openapi'
      requester: 'http-async'
```
In addition and if your api uses authentication to allow access to endpoints, you should also provide auth method like this example
```yaml
# ./api-tester.yaml
auth:
  - name: 'admin'
    body:
      username: 'test@mail.oc'
      password: 'password'
    headers:
      Accept: 'application/json'
      Content-Type: 'application/json'
```
So your minimal configuration file would looks like
```yaml
# ./api-tester.yaml
suites:
  - name: 'all'
    definition:
      path: 'openapi.yml'
      format: 'openapi'
      requester: 'http-async'

auth:
  - name: 'admin'
    body:
      username: 'admin@free.fr'
      password: 'password'
    headers:
      Accept: 'application/json'
      Content-Type: 'application/json'
```
Then you can add more configuration to suite your needs. See complete [configuration reference](./docs/configuration_reference.md) to learn more.

### Run tests
`./vendor/bin/api-tester`

#### Command options

```bash
  -c, --config[=CONFIG]                          config file [default: "api-tester.yaml"]
  -s, --suite[=SUITE]                            suite name to run
      --testdox                                  testdox print format
      --coverage-php[=COVERAGE-PHP]              coverage export to php format
      --coverage-clover[=COVERAGE-CLOVER]        coverage export to clover format
      --coverage-html[=COVERAGE-HTML]            coverage export to html format
      --coverage-text[=COVERAGE-TEXT]            coverage export to html format
      --coverage-cobertura[=COVERAGE-COBERTURA]  coverage export to html format
      --set-baseline                             if set it will create a baseline file that will register all errors so they become ignored on the next run
      --update-baseline                          update baseline with new errors to ignore
      --ignore-baseline                          ignore baseline file
      --filter[=FILTER]                          Filter which tests to run
      --log-junit[=LOG-JUNIT]                    Log test execution in JUnit XML format to file
      --log-teamcity[=LOG-TEAMCITY]              Log test execution in JUnit XML format to file
      --testdox-html[=TESTDOX-HTML]              Write agile documentation in HTML format to file
      --testdox-text[=TESTDOX-TEXT]              Write agile documentation in Text format to file
      --testdox-xml[=TESTDOX-XML]                Write agile documentation in XML format to file
      --part[=PART]                              Partition tests into groups and run only one of them, ex: --part=1/3
  -h, --help                                     Display help for the given command. When no command is given display help for the launch command
  -q, --quiet                                    Do not output any message
  -V, --version                                  Display this application version
      --ansi|--no-ansi                           Force (or disable --no-ansi) ANSI output
  -n, --no-interaction                           Do not ask any interactive question
  -v|vv|vvv, --verbose                           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```
