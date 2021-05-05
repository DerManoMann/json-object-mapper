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
 * No-op naming mapper - this is the default.
 */
class NoopNamingMapper implements NamingMapperInterface
{
    /**
     * @inheritdoc
     */
    public function resolve($name)
    {
        return $name;
    }
}
