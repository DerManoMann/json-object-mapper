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
            'bool-as-string-strict-fail' => ['{"proString":false}', true, true],
            'bool-as-string-ok' => ['{"proString":false}', false, false],
            'int-bool-types-strict-ok' => ['{"proInt":1,"proBool":false}', true, false],
            'int-bool-types-ok' => ['{"proInt":1,"proBool":false}', false, false],
            'string-as-int-strict-fail' => ['{"proInt":"abc"}', true, true],
            'string-as-int-fail' => ['{"proInt":"abc"}', false, true], // fails due to strict typing
            'weak-typed-int-strict-ok' => ['{"weakTyped":1}', true, false],
            'weak-typed-string-strict-fail' => ['{"weakTyped":"abc"}', true, true],
            'weak-typed-string-ok' => ['{"weakTyped":"abc"}', false, false],
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
            $objectMapper->map($json, Models\SimplePopo::class);

            $this->assertFalse($fail, '*Expected* to fail');
        } catch (ObjectMapperException $e) {
            $this->assertTrue($fail, sprintf('Expected *NOT* to fail: %s', $e->getMessage()));
        }
    }
}
