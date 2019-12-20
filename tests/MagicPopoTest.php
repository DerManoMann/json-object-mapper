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

use Radebatz\PropertyInfoExtras\PropertyInfoExtraExtractorInterface;

class MagicPopoTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!($this->getObjectMapper()->getPropertyInfoExtractor() instanceof PropertyInfoExtraExtractorInterface)) {
            $this->markTestSkipped('PropertyInfoExtraExtractor not configured');
        }
    }

    public function json()
    {
        return [
            ['{"pubString":"pub","proString":"pro","priString":"pri"}'],
            ['{"pubString":null,"proString":null}'],
            ['{"proInt":1,"proBool":false}'],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json)
    {
        $objectMapper = $this->getObjectMapper();

        $popo1 = $objectMapper->map($json, Models\MagicPopo::class);
        $this->assertInstanceOf(Models\MagicPopo::class, $popo1);
        $this->assertNotEmpty($popo1->all());
        $popo2 = $objectMapper->map(json_encode($popo1), Models\MagicPopo::class);
        $this->assertNotEmpty($popo2->all());
        $this->assertEquals($popo1, $popo2);
    }
}
