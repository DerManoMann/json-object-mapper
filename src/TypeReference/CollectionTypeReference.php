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

use Radebatz\ObjectMapper\TypeReferenceInterface;

/**
 * Type reference to map data into a list/map with custom collection type classes allowed.
 *
 * Custom collection types are expected to have a constructor that accepts the collection data.
 * Only exception to that is `\stdClass`.
 */
class CollectionTypeReference implements TypeReferenceInterface
{
    protected $valueType;
    protected $collectionType;

    /**
     * @param string|ClassTypeReference
     */
    public function __construct($valueType, string $collectionType = \ArrayObject::class)
    {
        $this->valueType = $valueType;
        $this->collectionType = $collectionType;
    }

    /**
     */
    public function getValueType()
    {
        return $this->valueType;
    }

    /**
     */
    public function setValueType($valueType)
    {
        $this->valueType = $valueType;
    }

    /**
     */
    public function getCollectionType()
    {
        return $this->collectionType;
    }

    /**
     */
    public function setCollectionType($collectionType)
    {
        $this->collectionType = $collectionType;
    }
}
