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

class EnumPopo implements \JsonSerializable
{
    protected ?StatusEnumStringBacked $status = null;
    protected ?PlainEnum $colour = null;

    public function getStatus(): ?StatusEnumStringBacked
    {
        return $this->status;
    }

    public function setStatus(?StatusEnumStringBacked $status): void
    {
        $this->status = $status;
    }

    public function getColour(): ?PlainEnum
    {
        return $this->colour;
    }

    public function setColour(?PlainEnum $colour): void
    {
        $this->colour = $colour;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'status' => $this->status,
            'colour' => $this->colour ? $this->colour->name : null,
        ];
    }
}
