<?php

declare(strict_types=1);

/*
* This file is part of the ObjectMapper library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ObjectMapper;

use Psr\Log\LoggerInterface;
use Radebatz\ObjectMapper\NamingMapper\NamingMapperInterface;
use Radebatz\ObjectMapper\NamingMapper\NoopNamingMapper;
use Radebatz\ObjectMapper\PropertyInfo\DocBlockCache;
use Radebatz\ObjectMapper\TypeMapper\TypeMapperInterface;
use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\CollectionTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;
use Radebatz\ObjectMapper\TypeReference\TypeReferenceInterface;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ObjectMapper
{
    const OPTION_STRICT_TYPES = 'strictTypes';
    const OPTION_STRICT_COLLECTIONS = 'strictCollections';
    const OPTION_STRICT_NULL = 'strictNull';
    const OPTION_IGNORE_UNKNOWN = 'ignoreUnknown';
    const OPTION_VERIFY_REQUIRED = 'verifyRequired';
    const OPTION_INSTANTIATE_REQUIRE_CTOR = 'instantiateRequireCtor';
    const OPTION_UNKNOWN_PROPERTY_HANDLER = 'unknownPropertyHandler';

    protected $options;
    /** @var LoggerInterface */
    protected $logger;
    /** @var DocBlockCache */
    protected $docBlockCache;
    /** @var PropertyInfoExtractor */
    protected $propertyInfoExtractor;
    /** @var PropertyAccessor */
    protected $propertyAccessor;
    /** @var array */
    protected $typeMappers;
    /** @var array */
    protected $namingMappers;
    /** @var array */
    protected $reflectionClasses;

    public function __construct(array $options = [], LoggerInterface $logger = null, DocBlockCache $docBlockCache = null, PropertyInfoExtractor $propertyInfoExtractor = null, PropertyAccess $propertyAccess = null)
    {
        $this->options = array_merge($this->getDefaultOptions(), $options);
        if ($this->options[self::OPTION_UNKNOWN_PROPERTY_HANDLER] && !is_callable($this->options[self::OPTION_UNKNOWN_PROPERTY_HANDLER])) {
            throw new ObjectMapperException('Option "unknownPropertyHandler" must be callable');
        }

        $this->logger = $logger;
        $this->docBlockCache = $docBlockCache ?: new DocBlockCache();
        $this->propertyInfoExtractor = $propertyInfoExtractor ?: $this->getDefaultPropertyInfoExtractor();
        $this->propertyAccessor = $propertyAccess ?: PropertyAccess::createPropertyAccessor();
        $this->typeMappers = [];
        $this->namingMappers = [
            new NoopNamingMapper(),
        ];
        $this->reflectionClasses = [];
    }

    /**
     * Set type mappers.
     */
    public function setTypeMappers(array $typeMappers): void
    {
        $this->typeMappers = $typeMappers;
    }

    /**
     * Add a type mapper.
     */
    public function addTypeMapper(TypeMapperInterface $typeMapper): void
    {
        $this->typeMappers[] = $typeMapper;
    }

    /**
     * Set naming mappers.
     */
    public function setNamingMappers(array $namingMappers): void
    {
        $this->namingMappers = $namingMappers;
    }

    /**
     * Add a naming mapper.
     */
    public function addNamingMapper(NamingMapperInterface $namingMapper): void
    {
        // default is always last
        array_unshift($this->namingMappers, $namingMapper);
    }

    /**
     * Get configured options.
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * The default options.
     */
    protected function getDefaultOptions(): array
    {
        return [
            self::OPTION_STRICT_TYPES => true,
            self::OPTION_STRICT_COLLECTIONS => true,
            self::OPTION_STRICT_NULL => true,
            self::OPTION_IGNORE_UNKNOWN => true,
            self::OPTION_VERIFY_REQUIRED => false,
            self::OPTION_INSTANTIATE_REQUIRE_CTOR => true,
            self::OPTION_UNKNOWN_PROPERTY_HANDLER => null,
        ];
    }

    /**
     * Get (and cache) a `\ReflectionClass` instance for the given class name.
     */
    protected function getReflectionClass($className): \ReflectionClass
    {
        if (!array_key_exists($className, $this->reflectionClasses)) {
            try {
                $this->reflectionClasses[$className] = new \ReflectionClass($className);
            } catch (\ReflectionException $e) {
                throw new ObjectMapperException(sprintf('Unable to instantiate ReflectionClass for class: %s', $className), 0, $e);
            }
        }

        return $this->reflectionClasses[$className];
    }

    /**
     * The default property extractor.
     */
    protected function getDefaultPropertyInfoExtractor(): PropertyInfoExtractor
    {
        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();

        $listExtractors = [
            $reflectionExtractor,
        ];

        $typeExtractors = [
            $phpDocExtractor,
            $reflectionExtractor,
        ];

        $descriptionExtractors = [
            $phpDocExtractor,
        ];

        $accessExtractors = [
            $reflectionExtractor,
        ];

        return new PropertyInfoExtractor(
            $listExtractors,
            $typeExtractors,
            $descriptionExtractors,
            $accessExtractors
        );
    }

    /**
     * Use registered type mappers to resolve a class name.
     */
    protected function resolveTypeClass(string $className, $json): string
    {
        foreach ($this->typeMappers as $typeMapper) {
            if ($mappedTypeClass = $typeMapper->resolve($className, $json)) {
                return $mappedTypeClass;
            }
        }

        return $className;
    }

    /**
     * Handle unmapped properties according to the configured options.
     *
     * The `unknownPropertyHandler` can "manually" map the property in which case it should return `null`
     * to signal that the property is mapped.
     *
     * @return string|null The name of an unmapped property or `null`
     */
    protected function handleUnmappedProperty($obj, string $key, $value)
    {
        if ($this->logger) {
            $this->logger->debug(sprintf('Handling unmapped property; name=%s, class=%s', $key, get_class($obj)));
        }

        if (!$this->options[self::OPTION_IGNORE_UNKNOWN]) {
            throw new ObjectMapperException(sprintf('Unmapped property; name=%s, class=%s', $key, get_class($obj)));
        }

        if ($this->options[self::OPTION_UNKNOWN_PROPERTY_HANDLER]) {
            return call_user_func($this->options['unknownPropertyHandler'], $obj, $key, $value);
        }

        return null;
    }

    /**
     * Verify that all required properties have been set/mapped.
     */
    protected function verifyRequiredProperties(string $className, array $properties, array $mappedProperties): void
    {
        foreach ($properties as $property) {
            if ($docBlock = $this->docBlockCache->getPropertyDocBlock($className, $property)) {
                if ($docBlock->getTagsByName('required')) {
                    if (!in_array($property, $mappedProperties)) {
                        throw new ObjectMapperException(sprintf('Missing required property; name=%s, class=%s', $property, $className));
                    }
                }
            }
        }
    }

    /**
     * Get a compatible native data type.
     *
     * @param string $type     The actual data type
     * @param string $expected The expected type
     *
     * @return string|null The compatible type usable for settype()
     */
    protected function getCompatibleType(string $type, string $expected): ?string
    {
        $alias = [
            'int' => 'integer',
            'bool' => 'boolean',
        ];

        if ($type === $expected) {
            return $type;
        }

        if ($expected && array_key_exists($expected, $alias) && $alias[$expected] === $type) {
            return $type;
        }

        $downcasts = [
            'float' => ['double', 'integer'],
        ];

        // allow some casting, in particular around floats...
        if ($expected && array_key_exists($expected, $downcasts) && in_array($type, $downcasts[$expected])) {
            return $expected;
        }

        return null;
    }

    /**
     * Enforce strict types for native data types.
     */
    protected function nativeType($value, $type, $key = null, string $className = null)
    {
        if (null !== $value && $type) {
            $compatibleType = $this->getCompatibleType(gettype($value), $type);

            if ($this->options[self::OPTION_STRICT_TYPES] && !$compatibleType) {
                throw new ObjectMapperException(sprintf('Incompatible data type; name=%s, class=%s, type=%s, expected=%s', $key, $className, gettype($value), $compatibleType));
            }

            if (false === settype($value, $compatibleType ?: $type)) {
                throw new ObjectMapperException(sprintf('Incompatible data type; name=%s, class=%s, type=%s', $key, $className, gettype($value)));
            }
        }

        return $value;
    }

    /**
     * Instance and populate.
     *
     * @return mixed The value object
     */
    protected function instantiate($json, string $valueClassName)
    {
        $singleValueCtorInstance = function ($valueClassName, $value) {
            $obj = null;
            if ($rc = $this->getReflectionClass($valueClassName)) {
                $cm = $rc->getConstructor();
                if ($cm && ($cp = $cm->getParameters()) && (!is_array($value) && !is_object($value))) {
                    if (!$cp[0]->isDefaultValueAvailable() || null === $cp[0]->getDefaultValue()) {
                        try {
                            if (!($cpType = $cp[0]->getType()) || $this->nativeType($value, (string) $cpType, $valueClassName)) {
                                $obj = new $valueClassName($value);
                            }
                        } catch (ObjectMapperException $ome) {
                            // ignore incompatible types
                        } catch (\InvalidArgumentException $e) {
                            // ignore incompatible types
                        }
                    }
                }
            }

            return $obj;
        };

        if (null === $json) {
            return null;
        }

        // deal with case of actual class instances
        if (is_object($json) && get_class($json) == $valueClassName) {
            return $json;
        }

        $isArrayObject = \ArrayObject::class == $valueClassName || is_subclass_of($valueClassName, \ArrayObject::class);
        if (!is_object($json) && !is_array($json) && $isArrayObject && $this->options[self::OPTION_STRICT_COLLECTIONS]) {
            throw new ObjectMapperException(sprintf('Collection type mismatch: expecting object or array, got %s', gettype($json)));
        }

        // FQDN
        $valueClassName = '\\' . ltrim($valueClassName, '\\');

        $obj = null;
        if (1 == count($arr = (array) $json) && !is_object($json)) {
            $obj = $singleValueCtorInstance($valueClassName, array_pop($arr));
        } elseif (1 == count($properties = (array) $this->propertyInfoExtractor->getProperties($valueClassName)) && 1 == count((array) $json)) {
            if (!$this->propertyInfoExtractor->isWritable($valueClassName, $properties[0])) {
                $obj = $singleValueCtorInstance($valueClassName, $json);
            }
        }

        $needsPopulate = null === $obj;

        if (!$obj) {
            try {
                $obj = new $valueClassName();
            } catch (\ArgumentCountError $e) {
                if ($this->options[self::OPTION_INSTANTIATE_REQUIRE_CTOR]) {
                    throw new ObjectMapperException(sprintf('Unable to instantiate value object; class=%s', $valueClassName), $e->getCode(), $e);
                }

                $obj = ($rc = $this->getReflectionClass($valueClassName))->newInstanceWithoutConstructor();
            }
        }

        return $needsPopulate ? $this->populate($json, $obj) : $obj;
    }

    /**
     * Map JSON into (nested) object(s).
     *
     * @param array|object|string                  $json The JSON data
     * @param string|object|TypeReferenceInterface $type The target type / object
     *
     * @return mixed The value object
     */
    public function map($json, $type)
    {
        if (!$type) {
            throw new \InvalidArgumentException(sprintf('Type must not be null'));
        }

        $jsonResolved = is_string($json) ? json_decode($json) : $json;
        if (!is_array($jsonResolved) && !is_object($jsonResolved)) {
            throw new \InvalidArgumentException(sprintf('Expecting JSON to resolve to either array or object; json=%s, actual=%s', $json, $jsonResolved));
        }

        $typeReference = null;
        if (is_object($type)) {
            if ($type instanceof TypeReferenceInterface) {
                $typeReference = $type;
            } else {
                $typeReference = new ObjectTypeReference($type);
            }
        } else {
            $typeReference = new ClassTypeReference($this->resolveTypeClass($type, $json));
        }

        return $this->mapType($jsonResolved, $typeReference);
    }

    /**
     * Map JSON into (nested) object(s).
     *
     * @param array|object           $json          The JSON data
     * @param TypeReferenceInterface $typeReference
     *
     * @return mixed The value object
     */
    protected function mapType($json, TypeReferenceInterface $typeReference)
    {
        if ($typeReference instanceof ObjectTypeReference) {
            return $this->populate($json, $typeReference->getObject());
        } elseif ($typeReference instanceof ClassTypeReference) {
            $valueClassName = $this->resolveTypeClass($typeReference->getClassName(), $json);

            return $this->instantiate($json, $valueClassName);
        } elseif ($typeReference instanceof CollectionTypeReference) {
            // build in type or ClassTypeReference instance
            $valueType = $typeReference->getValueType();
            // value class name if it is a class type reference
            $valueClassName = ($valueType instanceof ClassTypeReference) ? $valueType->getClassName() : null;

            // collections always start this way :)
            $collection = [];
            foreach ((array) $json as $jsonKey => $jsonValue) {
                // TODO: is $jsonValue a collection?
                if ($valueClassName) {
                    // resolve actual type based on $jsonValue
                    $collection[$jsonKey] = $this->instantiate($jsonValue, $this->resolveTypeClass($valueClassName, $jsonValue));
                } else {
                    $collection[$jsonKey] = $this->nativeType($jsonValue, $valueType, $jsonKey);
                }
            }

            $collectionClassName = $typeReference->getCollectionType();

            return \stdClass::class == $collectionClassName ? (object) $collection : new $collectionClassName($collection);
        }

        throw new ObjectMapperException(sprintf('Unsupported type reference: %s', get_class($typeReference)));
    }

    /**
     * The work horse - mapping object JSON data onto the actual (nested) value object.
     *
     * @param object $json The JSON data
     * @param object $obj  The value object to populate
     *
     * @return mixed The value object
     */
    protected function populate($json, $obj)
    {
        if (!is_object($json) && !($obj instanceof \ArrayObject) && $this->options[self::OPTION_STRICT_COLLECTIONS]) {
            throw new ObjectMapperException(sprintf('Collection type mismatch: expecting object, got %s', gettype($json)));
        }

        $className = get_class($obj);

        // no property info/access support
        $passThrough = ($obj instanceof \ArrayObject) || ($obj instanceof \stdClass);

        $properties = $passThrough ? array_keys((array) $json) : (array) $this->propertyInfoExtractor->getProperties($className);

        // keep track of properties processed in case we want to verify required ones later
        $mappedProperties = [];

        foreach ((array) $json as $jkey => $jval) {
            // allow naming mappers to do their thing
            $keys = array_map(function (NamingMapperInterface $namingMapper) use ($jkey) {
                return $namingMapper->resolve($jkey);
            }, $this->namingMappers);

            $mapped = false;
            // try all keys this property ($jkey) maps to
            foreach ($keys as $key) {
                // find property in object
                if (null !== $key && in_array($key, $properties)) {
                    if (!$passThrough && !$this->propertyInfoExtractor->isWritable($className, $key)) {
                        if ($this->logger) {
                            $this->logger->debug(
                                sprintf('Unwritable property; name=%s, class=%s', $key, $className),
                                ['property' => $key, 'class' => $className]
                            );
                        }
                        continue;
                    }

                    // figure/guess data type
                    if ($types = $this->propertyInfoExtractor->getTypes($className, $key)) {
                        // TODO: deal with multiple?
                        /** @var PropertyInfo\Type $type */
                        $type = $types[0];

                        // got type info from annotation or type hints
                        if (!$type->isNullable() && null === $jval && $this->options[self::OPTION_STRICT_NULL]) {
                            throw new ObjectMapperException(sprintf('Unmappable null value; name=%s, class=%s', $key, $className));
                        }

                        if (null !== $jval) {
                            // figure out what to do with type + $jval
                            if ($type->isCollection()) {
                                if (!is_array($jval) && !is_object($jval)) {
                                    throw new ObjectMapperException(sprintf('Incompatible data type; name=%s, class=%s, value=%s', $key, $className, gettype($jval)));
                                }

                                // figure out value + collection type...
                                $collectionType = null;
                                if ($valueType = $type->getCollectionValueType()) {
                                    if ($buildinType = $valueType->getBuiltinType()) {
                                        if ('object' == $buildinType) {
                                            if ($valueType->getClassName()) {
                                                $valueType = new ClassTypeReference($valueType->getClassName());
                                            } else {
                                                $valueType = \stdClass::class;
                                            }
                                        } else {
                                            $valueType = $buildinType;
                                        }
                                    }
                                }

                                $jval = $this->mapType($jval, new CollectionTypeReference($valueType))->getArrayCopy();
                            } elseif ($valueClassName = $type->getClassName()) {
                                if ($this->options[self::OPTION_STRICT_TYPES] && !is_object($jval)) {
                                    throw new ObjectMapperException(sprintf('Incompatible data type; name=%s, class=%s, type=%s, expected=%s', $key, $className, gettype($jval), 'object'));
                                }

                                $jval = $this->mapType($jval, new ClassTypeReference($valueClassName));
                            } elseif ($buildinType = $type->getBuiltinType()) {
                                $jval = $this->nativeType($jval, $buildinType, $key, $className);
                            }
                        }
                    }

                    // if no type, then there is no more typing to be found, so just set value and stop recursing
                    try {
                        if ($passThrough) {
                            if (($obj instanceof \ArrayObject)) {
                                $obj[$key] = $jval;
                            } else {
                                $obj->{$key} = $jval;
                            }
                        } else {
                            $this->propertyAccessor->setValue($obj, $key, $jval);
                        }
                    } catch (ExceptionInterface $e) {
                        throw new ObjectMapperException($e->getMessage(), $e->getCode(), $e);
                    }

                    $mapped = true;
                    $mappedProperties[] = $key;

                    break;
                }
            }

            if (!$mapped) {
                $mappedProperties[] = $this->handleUnmappedProperty($obj, $jkey, $jval);
            }
        }

        if ($this->options[self::OPTION_VERIFY_REQUIRED]) {
            $this->verifyRequiredProperties($className, $properties, $mappedProperties);
        }

        return $obj;
    }
}
