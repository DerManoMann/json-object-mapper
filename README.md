# (JSON) object mapper #
A simple library to deserialize JSON into (nested) PHP  arrays / objects.

[![Build Status](https://github.com/DerManoMann/json-object-mapper/workflows/build/badge.svg)](https://github.com/DerManoMann/json-object-mapper/actions?query=workflow:build)
[![Coverage Status](https://coveralls.io/repos/github/DerManoMann/json-object-mapper/badge.svg)](https://coveralls.io/github/DerManoMann/json-object-mapper)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## Requirements ##
* [PHP 7.2 or higher](http://www.php.net/)

## Installation ##

You can use **Composer** or simply **Download the Release**

### Composer ###

The preferred method is via [composer](https://getcomposer.org). Follow the
[installation instructions](https://getcomposer.org/doc/00-intro.md) if you do not already have
composer installed.

Once composer is installed, execute the following command in your project root to install this library:

```sh
composer require radebatz/object-mapper
```

## Usage ##
### Simple model using getXXX()/setXXX() ###
```php
use Radebatz\ObjectMapper\ObjectMapper;

class MyClass 
{
    protected $foo;
    
    public function getFoo()
    {
        return $this->foo;
    }
    
    public function setFoo($foo) 
    {
        $this->foo = $foo;
    }
}

    
$objectMapper = new ObjectMapper();

$json = '{"foo":"bar"}';

/** @var \MyClass $obj */
$obj = $objectMapper->map($json, \MyClass::class);

echo $obj->getFoo(); // 'bar'
```

### Value union using interface ###
```php
use Radebatz\ObjectMapper\ObjectMapper;
use Radebatz\ObjectMapper\ValueTypeResolverInterface;

interface ValueInterface
{
    public function getType(): string;
}

class Tree implements ValueInterface
{
    public function getType(): string
    {
        return "tree";
    }
}

class Flower implements ValueInterface
{
    public function getType(): string
    {
        return "flower";
    }
}

$objectMapper = new ObjectMapper();
$objectMapper->addValueTypeResolver(
    new class() implements ValueTypeResolverInterface {
        public function resolve($className, $json): ?string
        {
            if (is_object($json) && \ValueInterface::class == $className) {
                if (property_exists($json, 'type')) {
                    switch ($json->type) {
                        case 'tree':
                            return \Tree::class;
                        case 'flower':
                            return \Flower::class;
                    }
                }
            }

            return null;
        }
    }
);

$json = '{"type": "flower"}';

/** @var \ValueInterface $obj */
$obj = $objectMapper->map($json, \ValueInterface::class);

echo get_class($obj); // '\Flower'

```
        $objectMapper->addValueTypeResolver(
            new class() implements ValueTypeResolverInterface {
                public function resolve($className, $json): ?string
                {
                    if (is_object($json) && PopoInterface::class == $className) {
                        if (property_exists($json, 'foo')) {
                            return AnotherPopo::class;
                        }

                        return SimplePopo::class;
                    }

                    return null;
                }
            }
        );


## Configuration ##
The `ObjectMapper` class takes an array (map) as first constructor argument which allows to customise the behaviour when mapping data.
All option names (keys) are defined as class constants.

**`OPTION_STRICT_TYPES`**
---
Enforces strict type checking on build in data types (excluding `array`).

Default: `true`

**`OPTION_STRICT_COLLECTIONS`**
---
Enforces strict type checking on arrays.

Default: `true`

**`OPTION_STRICT_NULL`**
---
Enforces strict check on `null` value assignments.

Default: `true`

**`OPTION_IGNORE_UNKNOWN`**
---
Enable/disable reporting unmapped properties (will throw `ObjectMapperException`).

Default: `true`

**`OPTION_VERIFY_REQUIRED`**
---
If enabled check if all properties with a `@required` annotation have been mapped (will throw `ObjectMapperException`).

Default: `false`

**`OPTION_INSTANTIATE_REQUIRE_CTOR`**
---
If disabled, object instantiation will fall back to `ReflectionClass::newInstanceWithoutConstructor()` if a regular `new $class()` fails.
Futhermore, if set it will enforce a constructor argument check in case a `scalar` is deserialized into an object.

Default: `true`

**`OPTION_UNKNOWN_PROPERTY_HANDLER`**
---
Optional callable to handle unknown/unmappable properties.
Signature:
```php
function ($obj, $key, $value) {}
```
The return value is epected to be either a property name or `null`.

Default: `null`

**`OPTION_VARIADIC_SETTER`**
---
If enabled, setting list values will allow variadic parameters on models (requires `5.2 >= PropertyAccess`).
```php
class Model {
  private $list = [];
  public function setList(ListModel ... $listModels) {
    $this->list = $listModels;  
  }
}
```
The return value is expected to be either a property name or `null`.

Default: `false`


## Testing ##
This package is inspired by the excellent [jsonmapper](https://github.com/cweiske/jsonmapper) package.
In order to evaluate its features, the tests folder contains an adapter that lets you run the ```jsonmapper``` test suite agaist the ```json-object-manager``` codebase.

For this you run:

```sh
rm -rf vendor/netresearch/jsonmapper && composer install --prefer-source
./vendor/bin/phpunit -c phpunit.xml.jsonmapper
``` 

Not all tests pass as this library support also, for example, mapping scalar values. As it stands the result of running the tests is:

```sh
Tests: 104, Assertions: 249, Errors: 2, Failures: 14.  (jsonmapper 4.x)
```

The tests use a custom [`JsonMapper`](tests/JsonMapper/JsonMapper.php) class that internally uses the `ObjectMapper`.
