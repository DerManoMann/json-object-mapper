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
 * Type reference to map into an existing value object.
 */
class ObjectTypeReference implements TypeReferenceInterface
{
    protected $obj;

    public function __construct($obj)
    {
        if (!is_object($obj)) {
            throw new \InvalidArgumentException('Expecting object.');
        }

        $this->obj = $obj;
    }

    /**
     * {@inheritdoc}
     */
    public function isCollection(): bool
    {
        return $this->obj instanceof \ArrayObject;
    }

    /**
     * {@inheritdoc}
     */
    public function isNullable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return get_class($this->getObject());
    }

    public function getObject()
    {
        return $this->obj;
    }

    public function setObject($obj)
    {
        $this->obj = $obj;
    }
}
