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
    protected $proArrObj = null;
    protected $proStdC = null;
    protected $proSimpleArr = [];

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
     */
    public function getProArrObj():?\ArrayObject
    {
        return $this->proArrObj;
    }

    /**
     */
    public function setProArrObj(?\ArrayObject $proArrObj)
    {
        $this->proArrObj = $proArrObj;
    }

    /**
     */
    public function getProStdC():?\stdClass
    {
        return $this->proStdC;
    }

    /**
     */
    public function setProStdC(?\stdClass $proStdC)
    {
        $this->proStdC = $proStdC;
    }

    /**
     */
    public function getProSimpleArr()
    {
        return $this->proSimpleArr;
    }

    /**
     * @param SimplePopo []
     */
    public function setProSimpleArr($proSimpleArr)
    {
        $this->proSimpleArr = $proSimpleArr;
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
            'proArrObj' => $this->proArrObj,
            'proStdC' => $this->proStdC,
        ];
    }
}
