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

use Radebatz\ObjectMapper\ObjectMapper;
use Radebatz\ObjectMapper\ObjectMapperException;
use Radebatz\ObjectMapper\DeserializerAwareInterface;
use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\CollectionTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;
use Radebatz\ObjectMapper\TypeReferenceInterface;

/**
 * Maps a value onto a list/map.
 */
class CollectionTypeMapper extends AbstractTypeMapper
{
    public function map($value, ?TypeReferenceInterface $typeReference = null, $key = null)
    {
        if (is_scalar($value) && $this->getObjectMapper()->isOption(ObjectMapper::OPTION_STRICT_COLLECTIONS)) {
            throw new ObjectMapperException(sprintf('Invalid collection value; name=%s, type=%s', $key, gettype($value)));
        }

        if (!$typeReference || null === $value) {
            return $value;
        }

        $obj = null;
        $valueType = null;
        if ($typeReference instanceof ObjectTypeReference) {
            $obj = $typeReference->getObject();
        } elseif ($typeReference instanceof ClassTypeReference) {
            $valueTypeClassName = $typeReference->getClassName();

            $resolvedValueTypeClassName = $this->resolveValueType($valueTypeClassName, $value);

            list($obj, $ctorArg) = $this->instantiate($value, $resolvedValueTypeClassName);
        } elseif ($typeReference instanceof CollectionTypeReference) {
            if ($collectionType = $typeReference->getCollectionType()) {
                $obj = new $collectionType();

                if ($obj instanceof DeserializerAwareInterface) {
                    $obj->instantiated($this->getObjectMapper());
                }
            } else {
                $obj = [];
            }
            $valueType = $typeReference->getValueType();
        }

        $propertyAccessor = $this->getObjectMapper()->getPropertyAccessor();

        foreach ((array) $value as $key => $val) {
            if ($valueType instanceof TypeReferenceInterface) {
                $mapper = $this->getObjectMapper()->getTypeMapper($val, $valueType);
                $val = $mapper->map($val, $valueType, $key);
            }

            if ($obj instanceof \ArrayAccess) {
                $obj->offsetSet($key, $val);
            } elseif (is_array($obj)) {
                $obj[$key] = $val;
            } elseif (get_class($obj) === \stdClass::class) {
                $obj->{$key} = $val;
            } else {
                $propertyAccessor->setValue($obj, $key, $val);
            }
        }

        if ($obj instanceof DeserializerAwareInterface) {
            $obj->deserialized($this->getObjectMapper());
        }

        return $obj;
    }
}
