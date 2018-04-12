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
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 */
class ObjectMapper
{
    protected $options;
    /** @var LoggerInterface */
    protected $logger;
    protected $docBlockCache;
    /** @var PropertyInfoExtractor */
    protected $propertyInfoExtractor;
    /** @var PropertyAccess */
    protected $propertyAccessor;
    protected $typeMappers;
    protected $namingMappers;
    protected $reflectionClasses;

    /**
     */
    public function __construct(array $options = [], LoggerInterface $logger = null, DocBlockCache $docBlockCache = null, PropertyInfoExtractor $propertyInfoExtractor = null, PropertyAccess $propertyAccess = null)
    {
        $this->options = array_merge($this->getDefaultOptions(), $options);
        if ($this->options['unknownPropertyHandler'] && !is_callable($this->options['unknownPropertyHandler'])) {
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
            'ignoreUnknownProperties' => true,
            'verifyRequiredProperties' => false,
            'unknownPropertyHandler' => null,
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
            $reflectionExtractor
        ];

        $typeExtractors = [
            $phpDocExtractor,
            $reflectionExtractor
        ];

        $descriptionExtractors = [
            $phpDocExtractor
        ];

        $accessExtractors = [
            $reflectionExtractor
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
            $this->logger->debug(sprintf('Handling unmapped property; class=%s, key=%s', get_class($obj), $key));
        }

        if (!$this->options['ignoreUnknownProperties']) {
            throw new ObjectMapperException(sprintf('Unmapped property: %s', $key));
        }

        if ($this->options['unknownPropertyHandler']) {
            return call_user_func($this->options['unknownPropertyHandler'], $obj, $key, $value);
        }

        return null;
    }

    /**
     */
    protected function verifyRequiredProperties(string $class, array $properties, array $mappedProperties)
    {
        if (!$this->docBlockCache) {
            if ($this->logger) {
                $this->logger->warning('Skipping required verification - no DocBlockCache configured');
            }

            return;
        }

        foreach ($properties as $property) {
            if ($docBlock = $this->docBlockCache->getPropertyDocBlock($class, $property)) {
                if ($docBlock->getTagsByName('required')) {
                    if (!in_array($property, $mappedProperties)) {
                        throw new ObjectMapperException(sprintf('Missing required property: name=%s, class=%s', $property, $class));
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
                if ($cm && $cp = $cm->getParameters()) {
                    if (1 == count($cp) || $cp[1]->isDefaultValueAvailable()) {
                        // single arg ctor
                        $arr = (array) $json;
                        $obj = new $typeClass(array_pop($arr));
                    }
                }
            }

            return $obj;
        };

        $obj = null;
        if (!$properties && 1 == count((array) $json)) {
            $obj = $singleValueCtorInstance($typeClass, $json);
        } elseif (1 == count((array) $properties) && 1 == count((array) $json)) {
            if (!$this->propertyInfoExtractor->isWritable($typeClass, $properties[0])) {
                $obj = $singleValueCtorInstance($typeClass, $json);
            }
        }

        return $obj ? [$obj, false] : [new $typeClass(), true];
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
        $jsonResolved = is_string($json) ? json_decode($json) : $json;
        if (!is_array($jsonResolved) && !is_object($jsonResolved)) {
            throw new \InvalidArgumentException(sprintf('Expecting json to resolve to either array or object; json=%s, got=%s', $json, $jsonResolved));
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
        foreach ((array) $json as $key => $value) {
            list($obj, $needsPopulate) = $this->instantiate($typeClass, $json, $properties);
            $collection[$key] = $needsPopulate ? $this->populate($value, $obj) : $obj;
        }

        $collectionClass = $typeReference->getCollectionType();

        return \stdClass::class == $collectionClass ? (object) $collection : new $collectionClass($collection);
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
            throw new ObjectMapperException('Collection type mismatch: expecting object, got array');
        }

        $properties = $properties ?: $this->propertyInfoExtractor->getProperties($class = get_class($obj));

        $simpleClass = false;
        if (($obj instanceof \ArrayObject) || \stdClass::class == $class) {
            $simpleClass = true;
            $properties = array_keys((array) $json);
        }

        $mappedProperties = [];
        foreach ($json as $jkey => $jval) {
            $keys = array_map(function ($namingMapper) use ($jkey) {
                return $namingMapper->resolve($jkey);
            }, $this->namingMappers);

            $mapped = false;
            foreach ($keys as $key) {
                if ($key && in_array($key, $properties)) {
                    if (!$simpleClass && !$this->propertyInfoExtractor->isWritable($class, $key)) {
                        throw new ObjectMapperException(sprintf('Cannot set property value %s', $key));
                    }
                    if ($type = $this->propertyInfoExtractor->getTypes($class, $key)) {
                        $type = $type[0];
                        // type checking
                    }

                    if (is_array($jval)) {
                        $jval = $this->readInto($jval, new ObjectTypeReference(new \ArrayObject()));
                    } elseif (is_object($jval)) {
                        $jvalType = $type ? $type->getClassName() : \stdClass::class;
                        $jval = $this->readInto($jval, new ClassTypeReference($jvalType));
                    }

                    if ($simpleClass) {
                        if (($obj instanceof \ArrayObject)) {
                            $obj[$key] = $jval;
                        } else {
                            $obj->$key = $jval;
                        }
                    } else {
                        $this->propertyAccessor->setValue($obj, $key, $jval);
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

        if ($this->options['verifyRequiredProperties']) {
            $this->verifyRequiredProperties($class, $properties, $mappedProperties);
        }

        return $obj;
    }
}
