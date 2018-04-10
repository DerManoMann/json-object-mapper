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
use Radebatz\ObjectMapper\ObjectMapperException;

/**
 */
class UnmappedPropertiesTest extends TestCase
{
    public function json()
    {
        return [
            ['{"pubStrings":"pub","proString":"pro","priString":"pri"}', true],
            ['{"pubString":null,"proStrings":null}', false],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json, $fail)
    {
        $handlerLog = [];
        $unknownPropertyHandler = function ($obj, $jkey, $jval) use (&$handlerLog) {
            $handlerLog[] = $jkey;
        };
        $objectMapper = new ObjectMapper([
            'ignoreUnknownProperties' => !$fail,
            'unknownPropertyHandler' => $unknownPropertyHandler,
        ]);

        try {
            $objectMapper->map($json, Models\SimplePopo::class);

            $this->assertFalse($fail, '*Expected* to fail');
            $this->assertCount(1, $handlerLog);
        } catch (ObjectMapperException $e) {
            $this->assertTrue($fail, 'Expected *NOT* to fail');
        }
    }
}
