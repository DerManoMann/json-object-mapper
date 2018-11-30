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

class ScalarTypesTest extends TestCase
{
    public function json()
    {
        return [
            ['"a string"', 'a string'],
            ['1', 1],
            ['false', false],
            ['{"foo":"bar"}', (object) ['foo' => 'bar']],
            [1, 1],
            ['pong', 'pong', false],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json, $expected, $encoded = true)
    {
        $objectMapper = $this->getObjectMapper();

        $scalar1 = $objectMapper->map($json, null, $encoded);
        $this->assertEquals($expected, $scalar1);
        $scalar2 = $objectMapper->map(json_encode($scalar1));
        $this->assertEquals($scalar1, $scalar2);
    }
}
