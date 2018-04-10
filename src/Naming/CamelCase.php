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

namespace Radebatz\ObjectMapper\Naming;

use Radebatz\ObjectMapper\NamingMapperInterface;

/**
 * Resolve property names to camel case.
 */
class CamelCase implements NamingMapperInterface
{
    protected $cache = [];

    /**
     * @inheritdoc
     */
    public function resolve($name)
    {
        if (array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        $words = explode('_', $name);
        $camelKey = lcfirst(implode('', array_map(function ($word) {
            return ucfirst(strtolower($word));
        }, $words)));

        return ($this->cache[$name] = ($camelKey !== $name ? $camelKey : null));
    }
}
