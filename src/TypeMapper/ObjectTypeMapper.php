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
use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;
use Radebatz\ObjectMapper\TypeReferenceInterface;

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

            $resolvedTypeClassName = $this->getObjectMapper()->resolveValueType($typeClassName, $value);

            $obj = new $resolvedTypeClassName();
        }

        $properties = (array)$propertyInfoExtractor->getProperties(get_class($obj));

        foreach ((array)$value as $key => $val) {
            $keys = array_map(function (NamingMapperInterface $namingMapper) use ($key) {
                return $namingMapper->resolve($key);
            }, $this->getObjectMapper()->getNamingMappers());

            $mapped = false;
            foreach ($keys as $key) {
                if (null === $key || !in_array($key, $properties)) {
                    continue;
                }

                $types = $propertyInfoExtractor->getTypes(get_class($obj), $key);
                if ($types = $propertyInfoExtractor->getTypes(get_class($obj), $key)) {
                    $type = $types[0];
                    if ($className = $type->getClassName()) {
                        $val = $this->map($val, new ClassTypeReference($className));
                    }
                    // TODO: collections?
                }

                $propertyAccessor->setValue($obj, $key, $val);
            }
        }

        // TODO: type juggling / mapping
        return $obj;
    }
}
