# (JSON) object mapper #
A simple library to deserialize JSON into (nested) PHP  arrays / objects.

[![Build Status](https://travis-ci.org/DerManoMann/json-object-mapper.png)](https://travis-ci.org/DerManoMann/json-object-mapper)

## Requirements ##
* [PHP 7.1 or higher](http://www.php.net/)

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

## Basic usage ##
````
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

    
$objectMapper = new \Radebatz\ObjectMapper\ObjectMapper();

$json = '{"foo":"bar"}';

/** @var \MyData $myData */
$myData = $objectMapper->map($json, \MyData::class);

echo $myData->getFoo(); // echo's 'bar'
````

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

Default: `true`

**`OPTION_UNKNOWN_PROPERTY_HANDLER`**
---
Optional callable to handle unknown/unmappable properties.
Signature:
```php
funtion ($obj, $key, $value) {}
```
The return value is epected to be either a property name or `null`.

Default: `null`


## Testing ##
This package is inspired by the excellent [jsonmapper](https://github.com/cweiske/jsonmapper) package.
In order to evaluate its features the tests folder contains an adapter that lets you run the ````jsonmapper```` test suite agaist the ````json-object-manager```` codebase.

For this you run:

````
rm -rf vendor/netresearch/jsonmapper && composer install --prefer-source
./vendor/bin/phpunit -c phpunit.xml.jsonmapper
```` 
