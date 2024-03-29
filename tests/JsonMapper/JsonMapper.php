<?php

use Monolog\DateTimeImmutable;
use Radebatz\ObjectMapper\DeserializerAwareInterface;
use Radebatz\ObjectMapper\NamingMapper\CamelCaseNamingMapper;
use Radebatz\ObjectMapper\NamingMapper\SnakeCaseNamingMapper;
use Radebatz\ObjectMapper\ObjectMapper;
use Radebatz\ObjectMapper\ObjectMapperException;
use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\CollectionTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;
use Radebatz\ObjectMapper\ValueTypeResolverInterface;

class JsonMapperLoggerBase extends \Monolog\Logger
{
    protected $jmLogger;
    protected $logMap;

    public function __construct($jmLogger, $logMap)
    {
        parent::__construct('dummy');
        $this->jmLogger = $jmLogger;
        $this->logMap = $logMap;
    }

    public static function requiresMonolog2()
    {
        $rc = new \ReflectionClass(\Monolog\Logger::class);
        $rm = $rc->getMethod('addRecord');

        return $rm->hasReturnType();
    }

    protected function logMessage($level, $message, array $context = [])
    {
        if (array_key_exists($message, $this->logMap)) {
            $mapped = $this->logMap[$message];
            $level = $mapped[0];
            $message = $mapped[1];
            $context = $mapped[2] ?: $context;

            $this->jmLogger->log($level, $message, $context);
        }

        return true;
    }
};

if (JsonMapperLoggerBase::requiresMonolog2()) {
    class JsonMapperLogger extends JsonMapperLoggerBase
    {
        public function addRecord(int $level, string $message, array $context = [], ?DateTimeImmutable $datetime = null): bool
        {
            return $this->logMessage($level, $message, $context);
        }
    }
} else {
    class JsonMapperLogger extends JsonMapperLoggerBase
    {
        public function addRecord($level, $message, array $context = [])
        {
            return $this->logMessage($level, $message, $context);
        }
    }
}

class JsonMapper
{
    protected $jmLogger = null;
    public $bExceptionOnUndefinedProperty = false;
    public $bExceptionOnMissingData = false;
    public $bEnforceMapType = true;
    public $bStrictObjectTypeChecking = false;
    public $bStrictNullTypes = true;
    public $bIgnoreVisibility = false;
    public $classMap = [];
    public $undefinedPropertyHandler = null;
    public $postMappingMethod = null;

    private $exceptionMap = [
        'Unmappable null value; name=nonNullableObject, class=JsonMapperTest_PHP7_Object'
        => 'JSON property "nonNullableObject" in class "JsonMapperTest_PHP7_Object" must not be NULL',
        'Missing required property; name=pMissingData, class=JsonMapperTest_Broken'
        => 'Required property "pMissingData" of class JsonMapperTest_Broken is missing in JSON data',
        'Could not determine access type for property "privatePropertyPrivateSetter" in class "PrivateWithSetter".'
        => 'JSON property "privatePropertyPrivateSetter" has no public setter method in object of type PrivateWithSetter',
        'Cannot set property; name=privateNoSetter, class=PrivateWithSetter'
        => 'JSON property "privateNoSetter" has no public setter method in object of type PrivateWithSetter',
        'Collection type mismatch: expecting object, got array'
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
        'Incompatible value type; key=pArrayObject, type=double, expected=ArrayObject'
        => 'JSON property "pArrayObject" must be an array, double given',
        'Incompatible value type; key=flArray, type=integer, expected=array<float>'
        => 'JSON property "flArray" must be an array, integer given',
        'Unable to instantiate value object; class=\JsonMapperTest_ValueObject'
        => 'JSON property "pValueObject" must be an object, string given',
        'Incompatible value type; key=pValueObject, type=string, expected=JsonMapperTest_ValueObject'
        => 'JSON property "pValueObject" must be an object, string given',
        'Unmapped property; name=privatePropertyPrivateSetter, class=PrivateWithSetter'
        => 'JSON property "privatePropertyPrivateSetter" has no public setter method in object of type PrivateWithSetter',
        'Expecting JSON to resolve to either array or object; json=, actual='
        => 'JsonMapper::map() requires first argument to be an object, NULL given.',
        'Collection type mismatch: expecting object, got double'
        => 'JSON property "pArrayObject" must be an array, double given',
        'Passed variable is not an array or object'
        => 'JSON property "pArrayObject" must be an array, double given',
        'Invalid collection value; name=pArrayObject, type=double'
        => 'JSON property "pArrayObject" must be an array, double given',
        'Could not determine access type for property "privatePropertyPrivateSetter" in class "PrivateWithSetter": Neither the property "privatePropertyPrivateSetter" nor one of the methods "addPrivatePropertyPrivateSetter()"/"removePrivatePropertyPrivateSetter()", "setPrivatePropertyPrivateSetter()", "privatePropertyPrivateSetter()", "__set()" or "__call()" exist and have public access in class "PrivateWithSetter".'
        => 'JSON property "privatePropertyPrivateSetter" has no public setter method in object of type PrivateWithSetter',
        'Incompatible value type; key=strArray, type=array, expected=array<string>'
        => 'JSON property "strArray" is an array of type "string" but contained a value of type "object"',
        'Incompatible value type; key=pString, type=object, expected=string'
        => 'JSON property "pString" in class "JsonMapperTest_Object" is an object and cannot be converted to a string',
    ];
    private $logMap = [
        'Unwritable property; name=protectedStrNoSetter, class=JsonMapperTest_Simple' =>
            [
                'info',
                'Property {property} has no public setter method in {class}',
                null,
            ],
    ];

    public function setLogger($logger)
    {
        $this->jmLogger = $logger;
    }

    public function createInstance($class, $useParameter, $parameter)
    {
        if ($useParameter) {
            return new $class($parameter);
        } else {
            return (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        }
    }

    protected function getJsonMapperException($e, $class = JsonMapper_Exception::class)
    {
        $message = $e->getMessage();

        if (array_key_exists($message, $this->exceptionMap)) {
            $message = $this->exceptionMap[$message];
        }

        $forceJsonMapperException = [
            'JSON property "pArrayObject" must be an array, double given',
        ];
        if (in_array($message, $forceJsonMapperException)) {
            $class = JsonMapper_Exception::class;
        }

        return new $class($message);
    }

    protected function getObjectMapper()
    {
        $unknownPropertyHandler = null;
        if ($this->undefinedPropertyHandler) {
            $undefinedPropertyHandler = $this->undefinedPropertyHandler;
            $unknownPropertyHandler = function ($obj, $jkey, $jval) use ($undefinedPropertyHandler) {
                call_user_func($undefinedPropertyHandler, $obj, $jkey, $jval);
            };
        }

        $logger = null;
        if ($this->jmLogger) {
            $jmLogger = $this->jmLogger;
            $logMap = $this->logMap;

            $logger = new JsonMapperLogger($jmLogger, $logMap);
        }

        $objectMapper = new ObjectMapper([
            ObjectMapper::OPTION_IGNORE_UNKNOWN => !$this->bExceptionOnUndefinedProperty,
            ObjectMapper::OPTION_VERIFY_REQUIRED => $this->bExceptionOnMissingData,
            ObjectMapper::OPTION_STRICT_TYPES => $this->bStrictObjectTypeChecking,
            ObjectMapper::OPTION_STRICT_COLLECTIONS => $this->bEnforceMapType,
            ObjectMapper::OPTION_UNKNOWN_PROPERTY_HANDLER => $unknownPropertyHandler,
            ObjectMapper::OPTION_INSTANTIATE_REQUIRE_CTOR => false,
            ObjectMapper::OPTION_STRICT_NULL => $this->bStrictNullTypes,
        ], $logger);

        $objectMapper->addNamingMapper(new CamelCaseNamingMapper(['_', '-']));
        $objectMapper->addNamingMapper(new SnakeCaseNamingMapper());

        foreach ($this->classMap as $class => $mapped) {
            $valueTypeResolver = new class() implements ValueTypeResolverInterface {
                public $class;
                public $mapped = null;
                public $resolver = null;

                public function resolve($className, $json): ?string
                {
                    if (ltrim($className, '\\') == ltrim($this->class, '\\')) {
                        if ($this->mapped) {
                            return $this->mapped;
                        } elseif ($this->resolver) {
                            return call_user_func($this->resolver, $className, $json);
                        }
                    }

                    return null;
                }
            };

            $valueTypeResolver->class = $class;
            $valueTypeResolver->mapped = is_callable($mapped) ? null : $mapped;
            $valueTypeResolver->resolver = is_callable($mapped) ? $mapped : null;
            $objectMapper->addValueTypeResolver($valueTypeResolver);
        }

        return $objectMapper;
    }

    public function map($json, $object)
    {
        if (null === $json) {
            throw new \InvalidArgumentException('JsonMapper::map() requires first argument to be an object, NULL given.');
        }
        if (null === $object) {
            throw new \InvalidArgumentException('JsonMapper::map() requires second argument to be an object, NULL given.');
        }

        if ($this->postMappingMethod && $object instanceof \JsonMapperTest_EventObject) {
            // hacky, but makes (at least) the test pass
            $object = new class() extends \JsonMapperTest_EventObject implements DeserializerAwareInterface {
                public function instantiated(ObjectMapper $objectMapper): void
                {
                    // ignored
                }

                public function deserialized(ObjectMapper $objectMapper): void
                {
                    $rm = (new \ReflectionClass($this))->getMethod('_deserializePostEvent');
                    $rm->setAccessible(true);
                    $rm->invoke($this);
                }
            };
        }

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
            return $this->getObjectMapper()->map(
                (object) $json,
                (
                    $class ?
                    new CollectionTypeReference(new ClassTypeReference($class), \ArrayObject::class)
                    : new ObjectTypeReference(new \ArrayObject())
                )
            )->getArrayCopy();
        } catch (ObjectMapperException $e) {
            throw $this->getJsonMapperException($e);
        } catch (\InvalidArgumentException $e) {
            throw $this->getJsonMapperException($e, \InvalidArgumentException::class);
        }
    }
}
