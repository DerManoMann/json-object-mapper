<?php declare(strict_types=1);

/*
* This file is part of the ObjectMapper library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ObjectMapper\TypeMapper;

use Radebatz\ObjectMapper\NamingMapperInterface;
use Radebatz\ObjectMapper\ObjectMapper;
use Radebatz\ObjectMapper\ObjectMapperException;
use Radebatz\ObjectMapper\DeserializerAwareInterface;
use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;
use Radebatz\ObjectMapper\TypeReference\TypeReferenceFactory;
use Radebatz\ObjectMapper\TypeReferenceInterface;
use Radebatz\PropertyInfoExtras\PropertyInfoExtraExtractorInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Generic object type mapper.
 */
class ObjectTypeMapper extends AbstractTypeMapper
{
    public function map($value, ?TypeReferenceInterface $typeReference = null, $key = null)
    {
        if (!$typeReference || null === $value) {
            return $value;
        }

        $propertyInfoExtractor = $this->getObjectMapper()->getPropertyInfoExtractor();
        $propertyAccessor = $this->getObjectMapper()->getPropertyAccessor();

        $obj = null;
        $resolvedTypeClassName = null;
        $ctorArg = false;
        if ($typeReference instanceof ObjectTypeReference) {
            $obj = $typeReference->getObject();
        } elseif ($typeReference instanceof ClassTypeReference) {
            $typeClassName = $typeReference->getClassName();

            $resolvedTypeClassName = $this->resolveValueType($typeClassName, $value);

            if (is_object($value) && $value instanceof $resolvedTypeClassName) {
                return $value;
            }

            if (in_array($resolvedTypeClassName, ['integer', 'float', 'string', 'boolean'])) {
                settype($value, $resolvedTypeClassName);

                return $value;
            }

            list($obj, $ctorArg) = $this->instantiate($value, $resolvedTypeClassName);
        } else {
            throw new ObjectMapperException(sprintf('Unexpected type reference: %s', get_class($typeReference)));
        }

        if (!is_object($value)) {
            if (($obj instanceof \UnitEnum)) {
                return $obj;
            }

            if ($this->getObjectMapper()->isOption(ObjectMapper::OPTION_STRICT_TYPES)) {
                throw new ObjectMapperException(sprintf('Incompatible data type; name=%s, class=%s, type=%s, expected=object', $key, $resolvedTypeClassName ?: 'N/A', gettype($value)));
            }

            if ($ctorArg) {
                if ($obj instanceof DeserializerAwareInterface) {
                    $obj->deserialized($this->getObjectMapper());
                }

                return $obj;
            }
        }

        // keep track of mapped properties in case we want to verify required ones later
        $mappedProperties = [];

        $properties = (array) $propertyInfoExtractor->getAllProperties(get_class($obj));

        foreach ((array) $value as $valueKey => $val) {
            $keys = array_map(function (NamingMapperInterface $namingMapper) use ($valueKey) {
                return $namingMapper->resolve($valueKey);
            }, $this->getObjectMapper()->getNamingMappers());

            $mapped = false;
            foreach ($keys as $key) {
                if (null === $key) {
                    continue;
                }

                if (in_array($key, $properties)) {
                    $mappedValue = $this->mapValue($obj, $key, $val, $propertyInfoExtractor);
                    try {
                        $propertyAccessor->setValue($obj, $key, $mappedValue);
                        $mapped = true;
                    } catch (NoSuchPropertyException $e) {
                        // ignore
                    } catch (\Throwable $t) {
                        throw new ObjectMapperException($t->getMessage(), $t->getCode(), $t);
                    }
                    break;
                } elseif (get_class($obj) === \stdClass::class) {
                    $mappedValue = $this->mapValue($obj, $key, $val, $propertyInfoExtractor);
                    $obj->{$key} = $mappedValue;
                    $mapped = true;
                    break;
                }
            }

            $mappedProperties[] = $mapped ? $key : $this->handleUnmappedProperty($obj, $key, $val);
        }

        if ($this->getObjectMapper()->isOption(ObjectMapper::OPTION_VERIFY_REQUIRED)) {
            $this->verifyRequiredProperties(get_class($obj), $properties, $mappedProperties);
        }

        if ($obj instanceof DeserializerAwareInterface) {
            $obj->deserialized($this->getObjectMapper());
        }

        return $obj;
    }

    protected function mapValue($obj, $key, $val, PropertyInfoExtraExtractorInterface $propertyInfoExtractor)
    {
        // default for untyped data
        $valueTypeReferences = [null];
        if ($types = $propertyInfoExtractor->getAllTypes(get_class($obj), $key)) {
            $valueTypeReferences = array_map(function ($type) {
                return TypeReferenceFactory::getTypeReferenceForType($type);
            }, $types);
        }

        if (null === $val && $valueTypeReferences
            && !array_reduce($valueTypeReferences, function ($carry, $item) {
                return $carry || null === $item || $item->isNullable();
            }, false)
            && $this->getObjectMapper()->isOption(ObjectMapper::OPTION_STRICT_NULL)) {
            throw new ObjectMapperException(sprintf('Unmappable null value; name=%s, class=%s', $key, get_class($obj)));
        }

        foreach ($valueTypeReferences as $valueTypeReference) {
            try {
                $valueTypeMapper = $this->getObjectMapper()->getTypeMapper($val, $valueTypeReference);

                return $valueTypeMapper->map($val, $valueTypeReference, $key);
            } catch (ObjectMapperException $e) {
                // ignore
            }
        }

        throw new ObjectMapperException(sprintf(
            'Incompatible value type; key=%s, type=%s, expected=%s',
            $key,
            gettype($val),
            implode(', ', array_map(function ($valueTypeReference) {
                return $valueTypeReference->getType();
            }, $valueTypeReferences))
        ), 0, 1 === count($valueTypeReferences) ? $e : null);
    }
}
