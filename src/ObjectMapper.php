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
use Radebatz\ObjectMapper\Naming\DefaultCase;
use Radebatz\ObjectMapper\PropertyInfo\DocBlockCache;
use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\CollectionTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Type as PropertyInfoType;

/**
 */
class ObjectMapper
{
    const OPTION_STRICT_TYPES = 'strictTypes';
    const OPTION_IGNORE_UNKNOWN = 'ignoreUnknown';
    const OPTION_VERIFY_REQUIRED = 'verifyRequired';
    const OPTION_UNKNOWN_PROPRTY_HANDLER = 'unknownPropertyHandler';
    const OPTION_STRICT_COLLECTIONS = 'strictCollections';
    const OPTION_INSTANTIATE_REQUIRE_CTOR = 'instantiateRequireCtor';

    protected $options;
    /** @var LoggerInterface */
    protected $logger;
    protected $docBlockCache;
    /** @var PropertyInfoExtractor */
    protected $propertyInfoExtractor;
    /** @var PropertyAccessor */
    protected $propertyAccessor;
    protected $typeMappers;
    protected $namingMappers;
    protected $reflectionClasses;

    /**
     */
    public function __construct(array $options = [], LoggerInterface $logger = null, DocBlockCache $docBlockCache = null, PropertyInfoExtractor $propertyInfoExtractor = null, PropertyAccess $propertyAccess = null)
    {
        $this->options = array_merge($this->getDefaultOptions(), $options);
        if ($this->options[self::OPTION_UNKNOWN_PROPRTY_HANDLER] && !is_callable($this->options[self::OPTION_UNKNOWN_PROPRTY_HANDLER])) {
            throw new ObjectMapperException('Option "unknownPropertyHandler" must be callable');
        }

        $this->logger = $logger;
        $this->docBlockCache = $docBlockCache ?: new DocBlockCache();
        $this->propertyInfoExtractor = $propertyInfoExtractor ?: $this->getDefaultPropertyInfoExtractor();
        $this->propertyAccessor = $propertyAccess ?: PropertyAccess::createPropertyAccessor();
        $this->typeMappers = [];
        $this->namingMappers = [
            new DefaultCase(),
        ];
        $this->reflectionClasses = [];
    }

    /**
     */
    public function addTypeMapper(TypeMapperInterface $typeMapper): void
    {
        $this->typeMappers[] = $typeMapper;
    }

    /**
     */
    public function addNamingMapper(NamingMapperInterface $namingMapper): void
    {
        // default is always last
        array_unshift($this->namingMappers, $namingMapper);
    }

    /**
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     */
    protected function getDefaultOptions()
    {
        return [
            self::OPTION_STRICT_COLLECTIONS => true,
            self::OPTION_STRICT_TYPES => true,
            self::OPTION_IGNORE_UNKNOWN => true,
            self::OPTION_VERIFY_REQUIRED => false,
            self::OPTION_UNKNOWN_PROPRTY_HANDLER => null,
            self::OPTION_INSTANTIATE_REQUIRE_CTOR => true,
        ];
    }

    /**
     */
    protected function getReflectionClass($className): \ReflectionClass
    {
        if (!array_key_exists($className, $this->reflectionClasses)) {
            $this->reflectionClasses[$className] = new \ReflectionClass(($className));
        }

        return $this->reflectionClasses[$className];
    }

    /**
     */
    protected function getDefaultPropertyInfoExtractor()
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
     */
    protected function resolveTypeClass($typeClass, $json)
    {
        $rc = $this->getReflectionClass($typeClass);
        if (!$rc->isInstantiable()) {
            foreach ($this->typeMappers as $typeMapper) {
                if ($mappedTypeClass = $typeMapper->resolve($typeClass, $json)) {
                    $typeClass = $mappedTypeClass;
                    break;
                }
            }
        }

        return $typeClass;
    }

    /**
     */
    protected function handleUnmappedProperty($obj, $key, $value)
    {
        if ($this->logger) {
            $this->logger->debug(sprintf('Handling unmapped property; name=%s, class=%s', $key, get_class($obj)));
        }

        if (!$this->options[self::OPTION_IGNORE_UNKNOWN]) {
            throw new ObjectMapperException(sprintf('Unmapped property; name=%s, class=%s', $key, get_class($obj)));
        }

        if ($this->options[self::OPTION_UNKNOWN_PROPRTY_HANDLER]) {
            return call_user_func($this->options['unknownPropertyHandler'], $obj, $key, $value);
        }

        return null;
    }

    /**
     */
    protected function verifyRequiredProperties(string $class, array $properties, array $mappedProperties)
    {
        foreach ($properties as $property) {
            if ($docBlock = $this->docBlockCache->getPropertyDocBlock($class, $property)) {
                if ($docBlock->getTagsByName('required')) {
                    if (!in_array($property, $mappedProperties)) {
                        throw new ObjectMapperException(sprintf('Missing required property; name=%s, class=%s', $property, $class));
                    }
                }
            }
        }
    }

    /**
     * Creates typeClass instances and handles single value ctor injection
     */
    protected function instantiate($typeClass, $json, $properties)
    {
        $singleValueCtorInstance = function ($typeClass, $json) {
            $obj = null;
            if ($rc = $this->getReflectionClass($typeClass)) {
                $cm = $rc->getConstructor();
                if ($cm && ($cp = $cm->getParameters())) {
                    // reflection bug DateTime?
                    if (1 == count($cp) || $cp[0]->isDefaultValueAvailable() || '\\DateTime' == $typeClass) {
                        // single arg ctor
                        $arr = (array)$json;
                        $obj = new $typeClass(array_pop($arr));
                    }
                }
            }

            return $obj;
        };

        if (null === $json) {
            // null instance
            return [null, false];
        }

        $typeClass = '\\' . ltrim($typeClass, '\\');

        $obj = null;
        if (1 == count((array)$json)) {
            $obj = $singleValueCtorInstance($typeClass, $json);
        } elseif (1 == count((array)$properties) && 1 == count((array)$json)) {
            if (!$this->propertyInfoExtractor->isWritable($typeClass, $properties[0])) {
                $obj = $singleValueCtorInstance($typeClass, $json);
            }
        }

        $needsPopulate = null === $obj;

        if (!$obj) {
            try {
                $obj = new $typeClass();
            } catch (\ArgumentCountError $e) {
                if ($this->options[self::OPTION_INSTANTIATE_REQUIRE_CTOR]) {
                    throw new ObjectMapperException(sprintf('Unable to instantiate value object; class=%s', $typeClass), $e->getCode(), $e);
                }

                $obj = ($rc = $this->getReflectionClass($typeClass))->newInstanceWithoutConstructor();
            }
        }

        return [$obj, $needsPopulate];
    }

    /**
     * Compare build in types
     *
     * @param string $type The actual data type
     * @param string $expected The expected type
     * @return string The compatible type usable for settype()
     */
    protected function getCompatible($type, $expected)
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

        return null;
    }

    /**
     * Map Json to (nested) object(s).
     *
     * @param array|object|string $json The JSON data
     * @param string|object|TypeReferenceInterface $type The target type / object
     * @return mixed The value object
     */
    public function map($json, $type)
    {
        if (!$type) {
            throw new \InvalidArgumentException(sprintf('Type must not be null'));
        }

        $jsonResolved = is_string($json) ? json_decode($json) : $json;
        if (!is_array($jsonResolved) && !is_object($jsonResolved)) {
            throw new \InvalidArgumentException(sprintf('Expecting json to resolve to either array or object; json=%s, actual=%s', $json, $jsonResolved));
        }

        $typeReference = null;
        if (is_object($type)) {
            if ($type instanceof TypeReferenceInterface) {
                $typeReference = $type;
            } else {
                $typeReference = new ObjectTypeReference($type);
            }
        } else {
            $typeReference = new ClassTypeReference($type);
        }

        return $this->readInto($jsonResolved, $typeReference);
    }

    /**
     * Map Json to (nested) object(s).
     *
     * @param array|object $json The JSON data
     * @param TypeReferenceInterface $typeReference
     * @return mixed The value object
     */
    protected function readInto($json, TypeReferenceInterface $typeReference)
    {
        if ($typeReference instanceof ObjectTypeReference) {
            return $this->populate($json, $typeReference->getObject());
        } elseif (ClassTypeReference::class === get_class($typeReference)) {
            $typeClass = $typeReference->getClassName();
            $typeClass = $this->resolveTypeClass($typeClass, $json);
            $properties = $this->propertyInfoExtractor->getProperties($typeClass);
            list($obj, $needsPopulate) = $this->instantiate($typeClass, $json, $properties);

            return $needsPopulate ? $this->populate($json, $obj) : $obj;
        }

        // collection
        $typeClass = $typeReference->getClassName();
        $typeClass = $this->resolveTypeClass($typeClass, $json);
        $properties = $this->propertyInfoExtractor->getProperties($typeClass);
        $collection = [];
        foreach ((array)$json as $key => $value) {
            list($obj, $needsPopulate) = $this->instantiate($typeClass, $value, $properties);
            $collection[$key] = $needsPopulate ? $this->populate($value, $obj) : $obj;
        }

        $collectionClass = $typeReference->getCollectionType();

        return \stdClass::class == $collectionClass ? (object)$collection : new $collectionClass($collection);
    }

    /**
     * The work horse.
     *
     * @param array|object $json The JSON data
     * @param mixed $obj The value object to populate
     * @return mixed The value object
     */
    protected function populate($json, $obj, $properties = null)
    {
        if (is_array($json) && !($obj instanceof \ArrayObject)) {
            if ($this->options[self::OPTION_STRICT_COLLECTIONS]) {
                throw new ObjectMapperException('Collection type mismatch: expecting object, got array');
            }
        }

        if (!is_array($json) && !is_object($json)) {
            throw new ObjectMapperException(sprintf('Incompatible data type; class=%s, json=%s', get_class($obj), gettype($json)));
        }

        $properties = $properties ?: $this->propertyInfoExtractor->getProperties($class = get_class($obj));

        // simple classes do not have accessor support
        $simpleClass = false;
        if (($obj instanceof \ArrayObject) || \stdClass::class == $class) {
            $simpleClass = true;
            $properties = array_keys((array)$json);
        }

        $mappedProperties = [];
        foreach ((array)$json as $jkey => $jval) {
            $keys = array_map(function ($namingMapper) use ($jkey) {
                return $namingMapper->resolve($jkey);
            }, $this->namingMappers);

            $mapped = false;
            foreach ($keys as $key) {
                if (null !== $key && in_array($key, $properties)) {
                    if (!$simpleClass && !$this->propertyInfoExtractor->isWritable($class, $key)) {
                        // try again
                        continue;
                    }

                    /** @var PropertyInfoType $type */
                    if ($type = $this->propertyInfoExtractor->getTypes($class, $key)) {
                        $type = $type[0];
                    }

                    // figure+cast types
                    if ($type) {
                        if (!$type->isNullable() && null === $jval) {
                            throw new ObjectMapperException(sprintf('Unmappable null value; name=%s, class=%s', $key, $class));
                        }

                        if (null !== $jval) {
                            if ($type->isCollection()) {
                                if (!is_array($jval) && !is_object($jval)) {
                                    throw new ObjectMapperException(sprintf('Incompatible data type; name=%s, class=%s, value=%s', $key, $class, gettype($jval)));
                                }

                                $className = \stdClass::class;
                                if ($valueType = $type->getCollectionValueType()) {
                                    if ($buildinType = $valueType->getBuiltinType()) {
                                        if ('object' == $buildinType) {
                                            $className = $valueType->getClassName();
                                        }
                                    }
                                }

                                $jval = $this->readInto($jval, new CollectionTypeReference($className))->getArrayCopy();
                            } elseif ($className = $type->getClassName()) {
                                $jval = $this->readInto($jval, new ClassTypeReference($className));
                            } elseif ($buildinType = $type->getBuiltinType()) {
                                if (null !== $jval) {
                                    $compatibleType = $this->getCompatible(gettype($jval), $buildinType);
                                    if ($this->options[self::OPTION_STRICT_TYPES] && !$compatibleType) {
                                        throw new ObjectMapperException(sprintf('Incompatible data type; name=%s, class=%s, type=%s, expected=%s', $key, $class, gettype($jval), $compatibleType));
                                    }
                                    if (false === settype($jval, $compatibleType ?: $buildinType)) {
                                        throw new ObjectMapperException(sprintf('Incompatible data type; name=%s, class=%s, type=%s', $key, $class, gettype($jval)));
                                    }
                                }
                            }
                        }
                    } elseif (is_array($jval)) {
                        $typeReference = new ObjectTypeReference(new \ArrayObject());
                        $jval = $this->readInto($jval, $typeReference)->getArrayCopy();
                    } elseif (is_object($jval)) {
                        $typeReference = new ObjectTypeReference(new \stdClass());
                        $jval = $this->readInto($jval, $typeReference);
                    }

                    if ($simpleClass) {
                        if (($obj instanceof \ArrayObject)) {
                            $obj[$key] = $jval;
                        } else {
                            $obj->$key = $jval;
                        }
                    } else {
                        try {
                            $this->propertyAccessor->setValue($obj, $key, $jval);
                        } catch (ExceptionInterface $e) {
                            throw new ObjectMapperException($e->getMessage(), $e->getCode(), $e);
                        }
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
            $this->verifyRequiredProperties($class, $properties, $mappedProperties);
        }

        return $obj;
    }
}
