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
use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;
use Radebatz\ObjectMapper\TypeReference\TypeReferenceFactory;
use Radebatz\ObjectMapper\TypeReferenceInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

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

            $obj = $this->instantiate($value, $resolvedTypeClassName);
        } else {
            throw new ObjectMapperException(sprintf('Unexpected type reference: %s', get_class($typeReference)));
        }

        if (!is_object($value) && $this->getObjectMapper()->isOption(ObjectMapper::OPTION_STRICT_TYPES)) {
            throw new ObjectMapperException(sprintf('Incompatible data type; name=%s, class=%s, type=%s, expected=object', $key, $resolvedTypeClassName, gettype($value)));
        }

        // keep track of mapped properties in case we want to verify required ones later
        $mappedProperties = [];

        $properties = (array) $propertyInfoExtractor->getProperties(get_class($obj));

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

        return $obj;
    }

    protected function mapValue($obj, $key, $val, PropertyInfoExtractor $propertyInfoExtractor)
    {
        $valueTypeReference = null;
        if ($types = $propertyInfoExtractor->getTypes(get_class($obj), $key)) {
            $valueTypeReference = TypeReferenceFactory::getTypeReferenceForType($types[0]);
        }

        if (null === $val && $valueTypeReference && !$valueTypeReference->isNullable()
            && $this->getObjectMapper()->isOption(ObjectMapper::OPTION_STRICT_NULL)) {
            throw new ObjectMapperException(sprintf('Unmappable null value; name=%s, class=%s', $key, get_class($obj)));
        }

        $valueTypeMapper = $this->getObjectMapper()->getTypeMapper($val, $valueTypeReference);

        return $valueTypeMapper->map($val, $valueTypeReference, $key);
    }
}
