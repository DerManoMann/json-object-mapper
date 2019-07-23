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

class NoDefaultCtorPopo implements \JsonSerializable
{
    protected $value = null;
    protected $other = true;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function isOther(): bool
    {
        return $this->other;
    }

    public function setOther(bool $other)
    {
        $this->other = $other;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'value' => $this->value,
            'other' => $this->other,
        ];
    }
}
