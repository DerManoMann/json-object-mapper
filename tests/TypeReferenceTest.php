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
use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\CollectionTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;

class TypeReferenceTest extends TestCase
{
    use TestUtils;

    public function json()
    {
        return [
            'strings' => ['{"pubString":"pub","proString":"pro","priString":"pri"}'],
            'null' => ['{"pubString":null,"proString":null}'],
            'other' => ['{"proInt":1,"proBool":false}'],
            'floats' => ['{"proFloats":[1.0,2.1]}'],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testClassType($json)
    {
        $objectMapper = $this->getObjectMapper();

        $popo1 = $objectMapper->map($json, $type = new ClassTypeReference(Models\SimplePopo::class));
        $this->assertInstanceOf(Models\SimplePopo::class, $popo1);
        $popo2 = $objectMapper->map(json_encode($popo1), $type);
        $this->assertEquals($popo1, $popo2);
    }

    /**
     * @dataProvider json
     */
    public function testObjectType($json)
    {
        $objectMapper = $this->getObjectMapper();

        $popo1 = $objectMapper->map($json, $type = new ObjectTypeReference(new Models\SimplePopo()));
        $this->assertInstanceOf(Models\SimplePopo::class, $popo1);
        $popo2 = $objectMapper->map(json_encode($popo1), $type);
        $this->assertEquals($popo1, $popo2);
    }

    /**
     * @dataProvider json
     */
    public function testCollectionTypeList($json)
    {
        $objectMapper = $this->getObjectMapper();

        $json = sprintf('[%s]', $json);

        $popo1 = $objectMapper->map($json, $type = new CollectionTypeReference(new ClassTypeReference(Models\SimplePopo::class), \ArrayObject::class));

        $this->assertInstanceOf(\ArrayObject::class, $popo1);
        $this->assertEquals(1, count($popo1));

        $popo2 = $objectMapper->map(json_encode($popo1), $type);
        $this->assertEquals($popo1, $popo2);
    }

    /**
     * @dataProvider json
     */
    public function testCollectionTypeMap($json)
    {
        $objectMapper = $this->getObjectMapper();

        $json = sprintf('{"obj":%s}', $json);

        $popo1 = $objectMapper->map($json, $type = new CollectionTypeReference(new ClassTypeReference(Models\SimplePopo::class), \ArrayObject::class));

        $this->assertInstanceOf(\ArrayObject::class, $popo1);
        $this->assertEquals(1, count($popo1));

        $popo2 = $objectMapper->map(json_encode($popo1), $type);
        $this->assertEquals($popo1, $popo2);
    }

    /**
     * @dataProvider json
     */
    public function testCollectionTypeCustomMap($json)
    {
        $objectMapper = $this->getObjectMapper();

        $json = sprintf('{"obj":%s}', $json);

        $popo1 = $objectMapper->map($json, $type = new CollectionTypeReference(new ClassTypeReference(Models\SimplePopo::class), \stdClass::class));

        $this->assertInstanceOf(\stdClass::class, $popo1);
        $this->assertEquals(1, count((array) $popo1));

        $popo2 = $objectMapper->map(json_encode($popo1), $type);
        $this->assertEquals($popo1, $popo2);
    }

    /**
     * @dataProvider json
     */
    public function testCollectionTypeCustomNestedMap($json)
    {
        $objectMapper = $this->getObjectMapper();

        $json = sprintf('[{"obj":%s}]', $json);

        $popo1 = $objectMapper->map($json, $type = new CollectionTypeReference(new CollectionTypeReference(new ClassTypeReference(Models\SimplePopo::class), \ArrayObject::class), \ArrayObject::class));

        $this->assertInstanceOf(\ArrayObject::class, $popo1);
        $this->assertEquals(1, count($popo1));
        $innerPopo = $popo1[0];
        $this->assertInstanceOf(\ArrayObject::class, $innerPopo);
        $this->assertEquals(1, count((array) $innerPopo));

        $popo2 = $objectMapper->map(json_encode($popo1), $type);
        $this->assertEquals($popo1, $popo2);
    }
}
