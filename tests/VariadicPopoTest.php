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
use Radebatz\ObjectMapper\Utils\VariadicPropertyAccessor;

class VariadicPopoTest extends TestCase
{
    use TestUtils;

    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->getObjectMapper([ObjectMapper::OPTION_VARIADIC_SETTER => true])->getPropertyAccessor() instanceof VariadicPropertyAccessor) {
            $this->markTestSkipped('requires VariadicPropertyAccessor');
        }
    }

    public function json()
    {
        return [
            ['{"varPopos":[{"foo":null},{"foo":null}]}'],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json)
    {
        $objectMapper = $this->getObjectMapper([ObjectMapper::OPTION_VARIADIC_SETTER => true]);

        $popo1 = $objectMapper->map($json, Models\VariadicPopo::class);
        $this->assertInstanceOf(Models\VariadicPopo::class, $popo1);
        $this->assertEquals($json, json_encode($popo1));
        $popo2 = $objectMapper->map(json_encode($popo1), Models\VariadicPopo::class);
        $this->assertEquals($popo1, $popo2);
        $this->assertEquals($json, json_encode($popo2));
    }
}
