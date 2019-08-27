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
 * Value type resolver interface to dynamically resolve target types.
 */
interface ValueTypeResolverInterface
{
    /**
     * @param string       $className The class/interface name to map
     * @param array|object $json      The data to deserialize
     *
     * @return string|null a instantiable class name or scalar data type ('integer', 'float', 'string', 'boolean')
     */
    public function resolve($className, $json): ?string;
}
