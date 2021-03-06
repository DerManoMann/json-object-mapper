<?php declare(strict_types=1);

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
 * Type reference for collections.
 *
 * A `collectionType` value of `null` will be resolved as simple `array` / `[]`.
 */
class CollectionTypeReference implements TypeReferenceInterface
{
    protected $valueType;
    protected $collectionType = null;
    protected $nullable;

    /**
     * @param string|ClassTypeReference $valueType String values are taken as build in data type
     */
    public function __construct($valueType, ?string $collectionType = null, bool $nullable = true)
    {
        $this->valueType = $valueType;
        $this->collectionType = $collectionType;
        $this->nullable = $nullable;
    }

    /**
     * @inheritdoc
     */
    public function isCollection(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        $vt = $this->getValueType();
        $vt = $vt instanceof TypeReferenceInterface ? $vt->getType() : $vt;

        return sprintf('%s<%s>', ($this->getCollectionType() ?: 'array'), $vt);
    }

    public function getValueType()
    {
        return $this->valueType;
    }

    public function getCollectionType(): ?string
    {
        return $this->collectionType;
    }
}
