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

use Radebatz\ObjectMapper\ObjectMapper;
use Radebatz\ObjectMapper\ObjectMapperException;

class DataTypeTest extends TestCase
{
    public function json()
    {
        return [
            ['{"proString":false}', true, true],
            ['{"proInt":1,"proBool":false}', true, false],
            ['{"proInt":"abc"}', true, true],
            ['{"proString":false}', false, false],
            ['{"proInt":1,"proBool":false}', false, false],
            ['{"proInt":"abc"}', false, false],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json, $strict, $fail)
    {
        $objectMapper = $this->getObjectMapper([
            ObjectMapper::OPTION_STRICT_TYPES => $strict,
        ]);

        try {
            $popo = $objectMapper->map($json, Models\SimplePopo::class);

            $this->assertFalse($fail, '*Expected* to fail');
        } catch (ObjectMapperException $e) {
            $this->assertTrue($fail, 'Expected *NOT* to fail');
        }
    }
}
