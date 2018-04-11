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
class SingleValuePopo implements \JsonSerializable
{
    protected $value;

    public function __construct(?string $value = null)
    {
        $this->value = $value;
    }

    /**
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            'value' => $this->value,
        ];
    }
}
