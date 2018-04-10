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

namespace Radebatz\ObjectMapper\Tests;

use PHPUnit\Framework\TestCase;
use Radebatz\ObjectMapper\ObjectMapper;

/**
 */
class NestedPopoTest extends TestCase
{
    public function json()
    {
        return [
            ['{"proString":"pro"}'],
            ['{"proSimple":{}}'],
            ['{"proString":"pro", "proSimple":{}}'],
            ['{"proString":"pro", "proSimple":{"proString":null,"proBool":false}}'],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json)
    {
        $objectMapper = new ObjectMapper();

        $popo1 = $objectMapper->map($json, Models\NestedPopo::class);
        $this->assertInstanceOf(Models\NestedPopo::class, $popo1);
        $popo2 = $objectMapper->map(json_encode($popo1), Models\NestedPopo::class);
        $this->assertEquals($popo1, $popo2);
    }
}
