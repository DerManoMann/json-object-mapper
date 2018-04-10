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

namespace Radebatz\ObjectMapper\Tests\Models;

/**
 */
class AnotherPopo implements PopoInterface, \JsonSerializable
{
    protected $foo;

    /**
     */
    public function getFoo()
    {
        return $this->foo;
    }

    /**
     */
    public function setFoo($foo)
    {
        $this->foo = $foo;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            'foo' => $this->foo,
        ];
    }
}
