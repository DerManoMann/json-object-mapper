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
use Radebatz\PropertyInfoExtras\PropertyInfoExtraExtractorInterface;

class MagicPopoTest extends TestCase
{
    use TestUtils;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        if (!($this->getObjectMapper()->getPropertyInfoExtractor() instanceof PropertyInfoExtraExtractorInterface)) {
            $this->markTestSkipped('PropertyInfoExtraExtractor not configured');
        }
    }

    public function json()
    {
        return [
            'strings' => ['{"pubString":"pub","proString":"pro","priString":"pri"}'],
            'nulls' => ['{"pubString":null,"proString":null}'],
            'mixed' => ['{"proInt":1,"proBool":false}'],
            'simple' => ['{"simplePopo":{"proFloats":[1.23]}}'],
            'empty' => ['{}'],
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
