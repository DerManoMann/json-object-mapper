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

namespace Radebatz\ObjectMapper;

/**
 * Type mapper/resolver interface for any type that cannot be instantiated.
 */
interface TypeMapperInterface
{
    /**
     * @param string $className The class/interface name to map
     * @param array|object $json The data to deserialize
     * @return string|null A instantiable class name
     */
    public function resolve($className, $json);
}
