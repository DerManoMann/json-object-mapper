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
 * Type reference to map into a new instance of `$className`.
 */
class ClassTypeReference implements TypeReferenceInterface
{
    protected $className;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function setClassName(string $className)
    {
        $this->className = $className;
    }
}
