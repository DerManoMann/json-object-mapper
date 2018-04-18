<?php

set_include_path(
    __DIR__ . '/../vendor/netresearch/jsonmapper/src/'
    . PATH_SEPARATOR . get_include_path()
);

use Radebatz\ObjectMapper\Naming\CamelCase;
use Radebatz\ObjectMapper\Naming\SnakeCase;
use Radebatz\ObjectMapper\ObjectMapper;
use Radebatz\ObjectMapper\ObjectMapperException;
use Radebatz\ObjectMapper\TypeMapperInterface;
use Radebatz\ObjectMapper\TypeReference\CollectionTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;

class JsonMapper
{
    protected $logger = null;
    public $bExceptionOnUndefinedProperty = false;
    public $bExceptionOnMissingData = false;
    public $bEnforceMapType = true;
    public $bStrictObjectTypeChecking = false;
    public $bStrictNullTypes = true;
    public $bIgnoreVisibility = false;
    public $classMap = array();
    public $undefinedPropertyHandler = null;

    private $exceptionMap = [
        'Unmappable null value; name=nonNullableObject, class=JsonMapperTest_PHP7_Object'
        => 'JSON property "nonNullableObject" in class "JsonMapperTest_PHP7_Object" must not be NULL',
        'Missing required property; name=pMissingData, class=JsonMapperTest_Broken'
        => 'Required property "pMissingData" of class JsonMapperTest_Broken is missing in JSON data',
        'Could not determine access type for property "privatePropertyPrivateSetter" in class "PrivateWithSetter".'
        => 'JSON property "privatePropertyPrivateSetter" has no public setter method in object of type PrivateWithSetter',
        'Cannot set property; name=privateNoSetter, class=PrivateWithSetter'
        => 'JSON property "privateNoSetter" has no public setter method in object of type PrivateWithSetter',
        'Expecting json to resolve to either array or object; json=, actual='
        => 'JsonMapper::map() requires first argument to be an object, NULL given.',
        'Unmapped property; name=undefinedProperty, class=JsonMapperTest_Broken'
        => 'JSON property "undefinedProperty" does not exist in object of type JsonMapperTest_Broken',
        'Unmappable null value; name=pValueObject, class=JsonMapperTest_Object'
        => 'JSON property "pValueObject" in class "JsonMapperTest_Object" must not be NULL',
        'Unmappable null value; name=pArrayObject, class=JsonMapperTest_Array'
        => 'JSON property "pArrayObject" in class "JsonMapperTest_Array" must not be NULL',
        'Unmapped property; name=privateNoSetter, class=PrivateWithSetter'
        => 'JSON property "privateNoSetter" has no public setter method in object of type PrivateWithSetter',
        'Type must not be null'
        => 'JsonMapper::map() requires second argument to be an object, NULL given.',
        'Incompatible data type; class=ArrayObject, json=double'
        => 'JSON property "pArrayObject" must be an array, double given',
        'Incompatible data type; name=flArray, class=JsonMapperTest_Array, value=integer'
        => 'JSON property "flArray" must be an array, integer given',
    ];

    public function setLogger($logger)
    {
        //$this->logger = $logger;
    }

    protected function getJsonMapperException($e, $class = JsonMapper_Exception::class)
    {
        $message = $e->getMessage();

        if (array_key_exists($message, $this->exceptionMap)) {
            $message = $this->exceptionMap[$message];
        }

        return new $class($message);
    }

    protected function getObjectMapper()
    {
        $objectMapper = new ObjectMapper([
            ObjectMapper::OPTION_IGNORE_UNKNOWN => !$this->bExceptionOnUndefinedProperty,
            ObjectMapper::OPTION_VERIFY_REQUIRED => $this->bExceptionOnMissingData,
            ObjectMapper::OPTION_UNKNOWN_PROPRTY_HANDLER => null,
            ObjectMapper::OPTION_STRICT_TYPES => false,
            ObjectMapper::OPTION_STRICT_COLLECTIONS => $this->bEnforceMapType,
        ]);

        $objectMapper->addNamingMapper(new CamelCase(['_', '-']));
        $objectMapper->addNamingMapper(new SnakeCase());

        foreach ($this->classMap as $class => $mapped) {
            $mapper = new class() implements TypeMapperInterface
            {
                public $class;
                public $mapped = null;
                public $resolver = null;

                public function resolve($className, $json)
                {
                    if ($this->mapped) {
                        return $this->mapped;
                    }

                    if ($this->resolver) {
                        return call_user_func($this->resolver, $className, $json);
                    }

                    return null;
                }
            };

            $mapper->class = $class;
            $mapper->mapped = is_callable($mapped) ? null : $mapped;
            $mapper->resolver = is_callable($mapped) ? $mapped : null;
            $objectMapper->addTypeMapper($mapper);
        }

        return $objectMapper;
    }

    public function map($json, $object)
    {
        try {
            return $this->getObjectMapper()->map($json, $object);
        } catch (ObjectMapperException $e) {
            throw $this->getJsonMapperException($e);
        } catch (\InvalidArgumentException $e) {
            throw $this->getJsonMapperException($e, \InvalidArgumentException::class);
        }
    }

    public function mapArray($json, $array, $class = null)
    {
        try {
            return $this->getObjectMapper()->map($json, ($class ? new CollectionTypeReference($class) : new ObjectTypeReference(new \ArrayObject())))->getArrayCopy();
        } catch (ObjectMapperException $e) {
            throw $this->getJsonMapperException($e);
        } catch (\InvalidArgumentException $e) {
            throw $this->getJsonMapperException($e, \InvalidArgumentException::class);
        }
    }
}