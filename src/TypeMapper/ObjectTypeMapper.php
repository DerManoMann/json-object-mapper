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
use Radebatz\ObjectMapper\TypeReferenceInterface;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface as PropertyAccessExceptionInterface;

/**
 * Maps a value onto an object.
 */
class ObjectTypeMapper extends AbstractTypeMapper
{
    public function map($value, ?TypeReferenceInterface $typeReference = null)
    {
        // TODO: type validation

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

            $obj = new $resolvedTypeClassName();
        }

        // keep track of mapped properties in case we want to verify required ones later
        $mappedProperties = [];

        $properties = (array) $propertyInfoExtractor->getProperties(get_class($obj));

        foreach ((array) $value as $key => $val) {
            $keys = array_map(function (NamingMapperInterface $namingMapper) use ($key) {
                return $namingMapper->resolve($key);
            }, $this->getObjectMapper()->getNamingMappers());

            $mapped = false;
            foreach ($keys as $key) {
                if (null === $key || !in_array($key, $properties)) {
                    continue;
                }

                // TODO: move into abstract parent
                $valueTypeReference = null;
                if ($types = $propertyInfoExtractor->getTypes(get_class($obj), $key)) {
                    $type = $types[0];
                    if ($className = $type->getClassName()) {
                        $valueTypeReference = new ClassTypeReference($className);
                    } else {
                        // TODO:??
                    }
                }
                $valueTypeMapper = $this->getObjectMapper()->getTypeMapper($val, $valueTypeReference);

                try {
                    $propertyAccessor->setValue($obj, $key, $valueTypeMapper->map($val, $valueTypeReference));
                } catch (PropertyAccessExceptionInterface $e) {
                    throw new ObjectMapperException($e->getMessage(), $e->getCode(), $e);
                }

                $mapped = true;
                break;
            }

            if (!$mapped) {
                $mappedProperties[] = $this->handleUnmappedProperty($obj, $key, $val);
            }
        }

        if ($this->getObjectMapper()->getOption(ObjectMapper::OPTION_VERIFY_REQUIRED)) {
            $this->verifyRequiredProperties($className, $properties, $mappedProperties);
        }

        // TODO: type juggling / mapping
        return $obj;
    }
}
