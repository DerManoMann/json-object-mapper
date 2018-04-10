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

use Radebatz\ObjectMapper\Naming\DefaultCase;
use Radebatz\ObjectMapper\PropertyInfo\DocBlockCache;
use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * TODO:
 * * caching of things expensive
 * * logging
 * * handling of:
 *   * strict type checking: object compat, null ?
 */
class ObjectMapper
{
    protected $options;
    protected $docBlockCache;
    /** @var PropertyInfoExtractor */
    protected $propertyInfoExtractor;
    /** @var PropertyAccess */
    protected $propertyAccessor;
    protected $typeMappers;
    protected $namingMappers;

    /**
     */
    public function __construct(array $options = [], DocBlockCache $docBlockCache = null, PropertyInfoExtractor $propertyInfoExtractor = null, PropertyAccess $propertyAccess = null)
    {
        $this->options = array_merge($this->getDefaultOptions(), $options);
        if ($this->options['unknownPropertyHandler'] && !is_callable($this->options['unknownPropertyHandler'])) {
            throw new ObjectMapperException('Option "unknownPropertyHandler" must be callable');
        }

        $this->docBlockCache = $docBlockCache ?: new DocBlockCache();
        $this->propertyInfoExtractor = $propertyInfoExtractor ?: $this->getDefaultPropertyInfoExtractor();
        $this->propertyAccessor = $propertyAccess ?: PropertyAccess::createPropertyAccessor();
        $this->typeMappers = [];
        $this->namingMappers = [
            new DefaultCase(),
        ];
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
        $rc = new \ReflectionClass($typeClass);
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
            // TODO: issue warning
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
            $typeReference = new ObjectTypeReference(new $type());
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

            return $this->populate($json, new $typeClass());
        }

        // collection
        $typeClass = $typeReference->getClassName();
        $typeClass = $this->resolveTypeClass($typeClass, $json);
        $collection = [];
        foreach ((array) $json as $key => $value) {
            $collection[$key] = $this->populate($value, new $typeClass());
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
    protected function populate($json, $obj)
    {
        if (is_array($json) && !($obj instanceof \ArrayObject)) {
            throw new ObjectMapperException('Collection type mismatch: expecting object, got array');
        }

        $properties = $this->propertyInfoExtractor->getProperties($class = get_class($obj));

        $mappedProperties = [];
        foreach ($json as $jkey => $jval) {
            $keys = array_map(function ($namingMapper) use ($jkey) {
                return $namingMapper->resolve($jkey);
            }, $this->namingMappers);

            $mapped = false;
            foreach ($keys as $key) {
                if ($key && in_array($key, $properties)) {
                    if (!$this->propertyInfoExtractor->isWritable($class, $key)) {
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

                    $mapped = true;
                    $mappedProperties[] = $key;
                    $this->propertyAccessor->setValue($obj, $key, $jval);
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
