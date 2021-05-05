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

class PhpDocUnionPopo implements PopoInterface, \JsonSerializable
{
    protected $union = null;

    /**
     * @return int|string
     */
    public function getUnion()
    {
        return $this->union;
    }

    /**
     * @param int|string $union
     */
    public function setUnion($union): void
    {
        $this->union = $union;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            'union' => $this->union,
        ];
    }
}
