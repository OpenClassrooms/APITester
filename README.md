![Coverage](../coverage/coverage.svg?raw=true)
![PHPStan](https://img.shields.io/badge/PHPStan-level%207-brightgreen.svg?style=flat)
![PHP](https://img.shields.io/badge/PHP-%3E=%208.1-brightgreen.svg?style=flat)

# API Tester

This is project is aimed, to improve the testing experience by providing
automatic tests based on your OpenAPI document. It is based on PHPUnit so it support all of it's feature, like coverage for example.

It also provides special support for symonfy kernel, as it provides a mode where it uses directly the kernel to send requests instead of an http client, which is useful for transactions support.

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
[link](./docs/configuration_reference.md)
