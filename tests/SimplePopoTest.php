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

/**
 */
class SimplePopoTest extends TestCase
{
    public function json()
    {
        return [
            ['{"pubString":"pub","proString":"pro","priString":"pri"}'],
            ['{"pubString":null,"proString":null}'],
            ['{"proInt":1,"proBool":false}'],
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
        $popo2 = $objectMapper->map(json_encode($popo1), Models\SimplePopo::class);
        $this->assertEquals($popo1, $popo2);
    }
}
