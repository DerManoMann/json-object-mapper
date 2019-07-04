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

namespace Radebatz\ObjectMapper\TypeReference;

use Radebatz\ObjectMapper\ObjectMapperException;
use Symfony\Component\PropertyInfo\Type;

class TypeReferenceFactory
{
    public static function getTypeReferenceForType(Type $type)
    {
        if ($className = $type->getClassName()) {
            return new ClassTypeReference($className);
        }

        if ($buildInType = $type->getBuiltinType()) {
            switch ($buildInType) {
                case Type::BUILTIN_TYPE_ARRAY:
                    // TODO: collection key type
                    return new CollectionTypeReference(self::getTypeReferenceForType($type->getCollectionValueType()));

                case Type::BUILTIN_TYPE_INT:
                case Type::BUILTIN_TYPE_FLOAT:
                case Type::BUILTIN_TYPE_STRING:
                case Type::BUILTIN_TYPE_BOOL:
                case Type::BUILTIN_TYPE_NULL:
                    return new ScalarTypeReference($buildInType, $type->isNullable());

                case Type::BUILTIN_TYPE_CALLABLE:
                case Type::BUILTIN_TYPE_RESOURCE:
                case Type::BUILTIN_TYPE_ITERABLE:
                    throw new ObjectMapperException(sprintf('Invalid value type: %s', $buildInType));
            }
        }

        return null;
    }
}
