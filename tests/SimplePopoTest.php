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

class SimplePopoTest extends TestCase
{
    use TestUtils;

    public function json()
    {
        return [
            ['{"pubString":"pub","proString":"pro","priString":"pri","proInt":0,"proBool":true,"proFloats":[],"date":null,"mixed":0}'],
            ['{"pubString":null,"proString":null,"priString":null,"proInt":0,"proBool":true,"proFloats":[],"date":null,"mixed":0}'],
            ['{"pubString":null,"proString":null,"priString":null,"proInt":1,"proBool":false,"proFloats":[],"date":null,"mixed":0}'],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json)
    {
        $objectMapper = $this->getObjectMapper();

        $popo1 = $objectMapper->map($json, Models\SimplePopo::class);
        $this->assertInstanceOf(Models\SimplePopo::class, $popo1);
        $this->assertEquals($json, json_encode($popo1));
        $popo2 = $objectMapper->map(json_encode($popo1), Models\SimplePopo::class);
        $this->assertEquals($popo1, $popo2);
        $this->assertEquals($json, json_encode($popo2));
    }
}
