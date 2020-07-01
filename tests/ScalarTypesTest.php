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
use Radebatz\ObjectMapper\ObjectMapperException;

class ScalarTypesTest extends TestCase
{
    use TestUtils;

    public function json()
    {
        return [
            ['"pong"', '"pong"', null, null, false],
            ['"pong"', 'pong', null, null, true],
            ['pong', 'pong', null, null, false],
            ['pong', null, null, null, true],
            ['1', 1, null, null, false],
            ['1', 1, null, null, true],
            ['false', false, null, null, true],
            ['{"foo":"bar"}', (object) ['foo' => 'bar'], null, null, true],
            [1, 1, null, null, false],
            [1, 1, null, null, true],
            [null, null, null, null, false],
            ['false', false, \stdClass::class, ObjectMapperException::class, true],
            [new \stdClass(), null, true, \InvalidArgumentException::class, true],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json, $expected, $type, ?string $fail, $encoded)
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
