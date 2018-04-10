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
 * As-is name resolver - this is the default.
 */
class DefaultCase implements NamingMapperInterface
{
    /**
     * @inheritdoc
     */
    public function resolve($name)
    {
        return $name;
    }
}
