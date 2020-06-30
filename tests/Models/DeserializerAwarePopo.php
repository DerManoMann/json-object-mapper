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

use Radebatz\ObjectMapper\DeserializerAwareInterface;
use Radebatz\ObjectMapper\ObjectMapper;

class DeserializerAwarePopo implements DeserializerAwareInterface
{
    protected $proString = 'null';
    protected $aware = '';

    public function getProString(): ?string
    {
        return $this->proString;
    }

    public function setProString(?string $proString): void
    {
        $this->proString = $proString;
    }

    /**
     * @return string
     */
    public function getAware(): string
    {
        return $this->aware;
    }

    /** {@inheritdoc} */
    public function instantiated(ObjectMapper $objectMapper): void
    {
        $this->aware .= 'i:' . $this->proString;
    }

    /** {@inheritdoc} */
    public function deserialized(ObjectMapper $objectMapper): void
    {
        $this->aware .= 'd:' . $this->proString;
    }
}
