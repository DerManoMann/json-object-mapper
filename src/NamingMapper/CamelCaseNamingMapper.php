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

namespace Radebatz\ObjectMapper\NamingMapper;

use Radebatz\ObjectMapper\NamingMapperInterface;

/**
 * Map property names to camel case.
 */
class CamelCaseNamingMapper implements NamingMapperInterface
{
    protected $cache = [];
    protected $delimiters;

    public function __construct(array $delimiters = ['_'])
    {
        $this->delimiters = $delimiters;
    }

    /**
     * @inheritdoc
     */
    public function resolve($name)
    {
        if (null === $name || is_numeric($name)) {
            return null;
        }

        if (array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        $words = [$name];
        foreach ($this->delimiters as $delimiter) {
            $tmp = [];
            foreach ($words as $word) {
                $tmp = array_merge($tmp, explode($delimiter, $word));
            }
            $words = $tmp;
        }

        $camelKey = lcfirst(implode('', array_map(function ($word) {
            return ucfirst(strtolower($word));
        }, $words)));

        return ($this->cache[$name] = ($camelKey !== $name ? $camelKey : null));
    }
}
