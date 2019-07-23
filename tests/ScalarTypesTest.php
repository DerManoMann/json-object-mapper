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

use Radebatz\ObjectMapper\ObjectMapperException;

class ScalarTypesTest extends TestCase
{
    public function json()
    {
        return [
            ['"a string"', 'a string', null, false],
            ['1', 1, null, false],
            ['false', false, null, false],
            ['{"foo":"bar"}', (object) ['foo' => 'bar'], null, false],
            [1, 1, null, false],
            ['pong', 'pong', null, false, false],
            [null, null, null, false],
            ['false', false, \stdClass::class, ObjectMapperException::class],
            [new \stdClass(), null, true, \InvalidArgumentException::class],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json, $expected, $type, ?string $fail = null, $encoded = true)
    {
        $objectMapper = $this->getObjectMapper();

        if ($fail) {
            $this->expectException($fail);
        }

        $scalar1 = $objectMapper->map($json, $type, $encoded);
        $this->assertEquals($expected, $scalar1);
        $scalar2 = $objectMapper->map(json_encode($scalar1));
        $this->assertEquals($scalar1, $scalar2);
    }
}
