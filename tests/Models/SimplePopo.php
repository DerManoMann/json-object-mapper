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
class SimplePopo implements PopoInterface, \JsonSerializable
{
    public $pubString = null;
    protected $proString = null;
    private $priString = null;
    protected $proInt = 0;
    protected $proBool = true;
    protected $proFloats = [];

    /**
     */
    public function getProString(): ?string
    {
        return $this->proString;
    }

    /**
     */
    public function setProString(?string $proString): void
    {
        $this->proString = $proString;
    }

    /**
     */
    public function getPriString(): ?string
    {
        return $this->priString;
    }

    /**
     */
    public function setPriString(?string $priString): void
    {
        $this->priString = $priString;
    }

    /**
     */
    public function getProInt(): int
    {
        return $this->proInt;
    }

    /**
     * @required
     */
    public function setProInt(int $proInt): void
    {
        $this->proInt = $proInt;
    }

    /**
     */
    public function isProBool(): bool
    {
        return $this->proBool;
    }

    /**
     */
    public function setProBool(bool $proBool): void
    {
        $this->proBool = $proBool;
    }

    /**
     * @return float[]
     */
    public function getProFloats(): array
    {
        return $this->proFloats;
    }

    /**
     * @param float[]
     */
    public function setProFloats(array $proFloats)
    {
        $this->proFloats = $proFloats;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            'pubString' => $this->pubString,
            'proString' => $this->proString,
            'priString' => $this->priString,
            'proInt' => $this->proInt,
            'proBool' => $this->proBool,
            'proFloats' => $this->proFloats,
        ];
    }
}
