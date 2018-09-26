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
 * Type reference to map into an existing value object.
 */
class ObjectTypeReference implements TypeReferenceInterface
{
    protected $obj;

    /**
     */
    public function __construct($obj)
    {
        $this->obj = $obj;
    }

    /**
     */
    public function getObject()
    {
        return $this->obj;
    }

    /**
     */
    public function setObject($obj)
    {
        $this->obj = $obj;
    }
}
