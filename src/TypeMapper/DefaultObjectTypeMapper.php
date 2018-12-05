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

/**
 * Default object type mapper.
 */
class DefaultObjectTypeMapper extends AbstractTypeMapper
{
    public function map($value, ?TypeReferenceInterface $typeReference = null)
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

            $obj = $this->instantiate($value, $resolvedTypeClassName);
        } else {
            throw new ObjectMapperException(sprintf('Unexpected type reference: %s', get_class($typeReference)));
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

                // TODO: move into abstract parent
                $valueTypeReference = null;
                if ($types = $propertyInfoExtractor->getTypes(get_class($obj), $key)) {
                    $valueTypeReference = TypeReferenceFactory::getTypeReferenceForType($types[0]);
                }

                $valueTypeMapper = $this->getObjectMapper()->getTypeMapper($val, $valueTypeReference);
                $mappedValue = $valueTypeMapper->map($val, $valueTypeReference);

                if (in_array($key, $properties)) {
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
                    $obj->{$key} = $mappedValue;
                    $mapped = true;
                    break;
                }
            }

            $mappedProperties[] = $mapped ? $key : $this->handleUnmappedProperty($obj, $key, $val);
        }

        if ($this->getObjectMapper()->getOption(ObjectMapper::OPTION_VERIFY_REQUIRED)) {
            $this->verifyRequiredProperties(get_class($obj), $properties, $mappedProperties);
        }

        return $obj;
    }
}
