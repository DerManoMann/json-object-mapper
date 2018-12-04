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

use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\CollectionTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;
use Radebatz\ObjectMapper\TypeReferenceInterface;

/**
 * Maps a value onto a list/map.
 */
class CollectionTypeMapper extends AbstractTypeMapper
{
    public function map($value, ?TypeReferenceInterface $typeReference = null)
    {
        if (!$typeReference || null === $value) {
            return $value;
        }

        $obj = null;
        $valueType = null;
        if ($typeReference instanceof ObjectTypeReference) {
            $obj = $typeReference->getObject();
        } elseif ($typeReference instanceof ClassTypeReference) {
            $valueType = $typeReference->getClassName();
            $obj = new $valueType();
        } elseif ($typeReference instanceof CollectionTypeReference) {
            if ($collectionType = $typeReference->getCollectionType()) {
                $obj = new $collectionType();
            } else {
                $obj = [];
            }
            $valueType = $typeReference->getValueType();
        }

        $propertyAccessor = $this->getObjectMapper()->getPropertyAccessor();

        foreach ($value as $key => $val) {
            if ($valueType instanceof TypeReferenceInterface) {
                $mapper = $this->getObjectMapper()->getTypeMapper($val, $valueType);
                $val = $mapper->map($val, $valueType);
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

        return $obj;
    }
}
