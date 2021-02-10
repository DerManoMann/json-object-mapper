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

class TypeHintUnionPopoTest extends TestCase
{
    use TestUtils;

    public function json()
    {
        return [
            ['{"union":"pub"}'],
            ['{"union":3}'],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json)
    {
        $objectMapper = $this->getObjectMapper();

        $popo1 = $objectMapper->map($json, Models\TypeHintUnionPopo::class);
        $this->assertInstanceOf(Models\TypeHintUnionPopo::class, $popo1);
        $this->assertEquals($json, json_encode($popo1));
        $popo2 = $objectMapper->map(json_encode($popo1), Models\TypeHintUnionPopo::class);
        $this->assertEquals($popo1, $popo2);
        $this->assertEquals($json, json_encode($popo2));
    }
}
