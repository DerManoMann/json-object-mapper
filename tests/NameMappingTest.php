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
use Radebatz\ObjectMapper\NamingMapper\CamelCaseNamingMapper;
use Radebatz\ObjectMapper\NamingMapper\SnakeCaseNamingMapper;

class NameMappingTest extends TestCase
{
    use TestUtils;

    public function json()
    {
        return [
            ['{"pub_String":"pub","proString":"pro","pri_String":"pri"}', ['pub', 'pro', 'pri']],
            ['{"pub_String":null,"proString":null}', [null, null, null]],
            ['{"Pub_String":"","pro_STRING":"deng"}', ['', 'deng', null]],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json, $expected)
    {
        $objectMapper = $this->getObjectMapper();

        $objectMapper->addNamingMapper(new CamelCaseNamingMapper());
        $objectMapper->addNamingMapper(new SnakeCaseNamingMapper());

        /** @var Models\SimplePopo */
        $popo = $objectMapper->map($json, Models\SimplePopo::class);
        $this->assertEquals($expected[0], $popo->pubString);
        $this->assertEquals($expected[1], $popo->getProString());
        $this->assertEquals($expected[2], $popo->getPriString());
    }
}
