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
class NestedPopo implements \JsonSerializable
{
    protected $proString = null;
    protected $proSimple = null;
    protected $proInter = null;

    /**
     */
    public function getProString(): ?string
    {
        return $this->proString;
    }

    /**
     */
    public function setProString(?string $proString)
    {
        $this->proString = $proString;
    }

    /**
     */
    public function getProSimple():?SimplePopo
    {
        return $this->proSimple;
    }

    /**
     */
    public function setProSimple(?SimplePopo $proSimple)
    {
        $this->proSimple = $proSimple;
    }

    /**
     */
    public function getProInter():?PopoInterface
    {
        return $this->proInter;
    }

    /**
     */
    public function setProInter(?PopoInterface $proInter)
    {
        $this->proInter = $proInter;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            'proString' => $this->proString,
            'proSimple' => $this->proSimple,
            'proInter' => $this->proInter,
        ];
    }
}
