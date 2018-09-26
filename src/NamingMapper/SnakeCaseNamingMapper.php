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

/**
 * Map property names to snake case.
 */
class SnakeCaseNamingMapper implements NamingMapperInterface
{
    protected $cache = [];

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

        if (strtoupper($name) === $name) {
            return ($this->cache[$name] = strtolower($name));
        }

        $snakeKey = $name;
        $snakeKey = preg_replace_callback('/([a-z0-9])([A-Z]+[0-9]?)([a-z])/', function ($match) {
            return $match[1] . '_' . strtolower(substr($match[2], 0, -1)) . '_' . strtolower(substr($match[0], -2));
        }, $snakeKey);
        $snakeKey = preg_replace_callback('/([A-Z]+[0-9]?)([a-z])/', function ($match) {
            return strtolower(substr($match[0], 0, -2)) . '_' . strtolower(substr($match[0], -2));
        }, $snakeKey);
        $snakeKey = preg_replace_callback('/([a-z])([A-Z]+[0-9]?)/', function ($match) {
            return $match[1] . '_' . strtolower($match[2]);
        }, $snakeKey);
        $snakeKey = ltrim(str_replace('__', '_', strtolower($snakeKey)), '_');

        return ($this->cache[$name] = ($snakeKey !== $name ? $snakeKey : null));
    }
}
