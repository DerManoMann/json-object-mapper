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

/**
 * Type reference to map data into a list/map with custom collection type classes allowed.
 *
 * Custom collection types are expected to have a constructor that accepts the collection data.
 * Only exception to that is `\stdClass`.
 */
class CollectionTypeReference extends ClassTypeReference
{
    protected $collectionType;

    /**
     */
    public function __construct(string $className, string $collectionType = \ArrayObject::class)
    {
        parent::__construct($className);

        $this->collectionType = $collectionType;
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
