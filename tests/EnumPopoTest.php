<?php

declare(strict_types=1);

namespace Radebatz\ObjectMapper\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @requires PHP 8.1
 */
class EnumPopoTest extends TestCase
{
    use TestUtils;

    public function json()
    {
        return [
            ['{"status":null,"colour":null}'],
            ['{"status":"draft","colour":null}'],
            ['{"status":null,"colour":"BLUE"}'],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json)
    {
        $objectMapper = $this->getObjectMapper();

        $popo1 = $objectMapper->map($json, Models\EnumPopo::class);
        $this->assertInstanceOf(Models\EnumPopo::class, $popo1);
        $this->assertEquals($json, json_encode($popo1));
        $popo2 = $objectMapper->map(json_encode($popo1), Models\EnumPopo::class);
        $this->assertEquals($popo1, $popo2);
        $this->assertEquals($json, json_encode($popo2));
    }
}
