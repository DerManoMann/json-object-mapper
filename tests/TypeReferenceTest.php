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
use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\CollectionTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;

/**
 */
class TypeReferenceTest extends TestCase
{
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
    public function testClassType($json)
    {
        $objectMapper = new ObjectMapper();

        $popo1 = $objectMapper->map($json, new ClassTypeReference(Models\SimplePopo::class));
        $this->assertInstanceOf(Models\SimplePopo::class, $popo1);
        $popo2 = $objectMapper->map(json_encode($popo1), new ClassTypeReference(Models\SimplePopo::class));
        $this->assertEquals($popo1, $popo2);
    }

    /**
     * @dataProvider json
     */
    public function testObjectType($json)
    {
        $objectMapper = new ObjectMapper();

        $popo1 = $objectMapper->map($json, new ObjectTypeReference(new Models\SimplePopo()));
        $this->assertInstanceOf(Models\SimplePopo::class, $popo1);
        $popo2 = $objectMapper->map(json_encode($popo1), new ObjectTypeReference(new Models\SimplePopo()));
        $this->assertEquals($popo1, $popo2);
    }

    /**
     * @dataProvider json
     */
    public function testCollectionTypeList($json)
    {
        $objectMapper = new ObjectMapper();

        $json = sprintf('[%s]', $json);

        $popo1 = $objectMapper->map($json, new CollectionTypeReference(Models\SimplePopo::class));

        $this->assertInstanceOf(\ArrayObject::class, $popo1);
        $this->assertEquals(1, count($popo1));

        $popo2 = $objectMapper->map(json_encode($popo1), new CollectionTypeReference(Models\SimplePopo::class));
        $this->assertEquals($popo1, $popo2);
    }

    /**
     * @dataProvider json
     */
    public function testCollectionTypeMap($json)
    {
        $objectMapper = new ObjectMapper();

        $json = sprintf('{"obj":%s}', $json);

        $popo1 = $objectMapper->map($json, new CollectionTypeReference(Models\SimplePopo::class));

        $this->assertInstanceOf(\ArrayObject::class, $popo1);
        $this->assertEquals(1, count($popo1));

        $popo2 = $objectMapper->map(json_encode($popo1), new CollectionTypeReference(Models\SimplePopo::class));
        $this->assertEquals($popo1, $popo2);
    }

    /**
     * @dataProvider json
     */
    public function testCollectionTypeCustomMap($json)
    {
        $objectMapper = new ObjectMapper();

        $json = sprintf('{"obj":%s}', $json);

        $popo1 = $objectMapper->map($json, new CollectionTypeReference(Models\SimplePopo::class, \stdClass::class));

        $this->assertInstanceOf(\stdClass::class, $popo1);
        $this->assertEquals(1, count((array) $popo1));

        $popo2 = $objectMapper->map(json_encode($popo1), new CollectionTypeReference(Models\SimplePopo::class, \stdClass::class));
        $this->assertEquals($popo1, $popo2);
    }
}
