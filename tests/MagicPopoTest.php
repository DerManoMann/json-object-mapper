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
use Radebatz\ObjectMapper\PropertyInfo\DocBlockCache;
use Radebatz\ObjectMapper\PropertyInfo\PhpDocMagicExtractor;

/**
 */
class MagicPopoTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $phpDocMagicExtractor = new PhpDocMagicExtractor(new DocBlockCache());

        $properties = $phpDocMagicExtractor->getProperties(Models\MagicPopo::class);
        //var_dump($properties);

        $types = $phpDocMagicExtractor->getTypes(Models\MagicPopo::class, 'proString');
        $types = $phpDocMagicExtractor->getTypes(Models\MagicPopo::class, 'proInt');
        $types = $phpDocMagicExtractor->getTypes(Models\MagicPopo::class, 'simplePopo');
        //var_dump($types);

        $this->markTestSkipped('TODO');
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
        $objectMapper = new ObjectMapper();

        $popo1 = $objectMapper->map($json, Models\MagicPopo::class);
        $this->assertInstanceOf(Models\MagicPopo::class, $popo1);
        $popo2 = $objectMapper->map(json_encode($popo1), Models\MagicPopo::class);
        $this->assertEquals($popo1, $popo2);
    }
}
