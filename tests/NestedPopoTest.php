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

/**
 */
class NestedPopoTest extends TestCase
{
    public function json()
    {
        return [
            'plain' => ['{"proString":"pro"}'],
            'simple' => ['{"proString":"pro", "proSimple":{"proString":"yup","proBool":false}}'],
            'array-object' => ['{"proString":"pro", "proArrObj":{"proString":"yup","proBool":false}}'],
            'interface' => ['{"proString":"pro", "proStdC":{"proString":"yup","proBool":false}}'],
            'typed-array' => ['{"proString":"pro", "proSimpleArr":[{"proString":"yup","proBool":false}]}'],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json)
    {
        $objectMapper = $this->getObjectMapper();

        /** @var Models\NestedPopo */
        $popo1 = $objectMapper->map($json, Models\NestedPopo::class);
        $this->assertInstanceOf(Models\NestedPopo::class, $popo1);
        if (null !== ($proSimple = $popo1->getProSimple())) {
            /** @var Models\SimplePopo $proSimple */
            $this->assertInstanceOf(Models\SimplePopo::class, $proSimple);
            $this->assertEquals('yup', $proSimple->getProString());
        }
        if (null !== ($proArrObj = $popo1->getProArrObj())) {
            /** @var \ArrayObject $proArrObj */
            $this->assertInstanceOf(\ArrayObject::class, $proArrObj);
            $this->assertTrue($proArrObj->offsetExists('proString'));
            $this->assertEquals('yup', $proArrObj['proString']);
        }
        if (null !== ($proStdC = $popo1->getProStdC())) {
            /** @var \stdClass $proStdC */
            $this->assertEquals(\stdClass::class, get_class($proStdC));
            $this->assertTrue(property_exists($proStdC, 'proString'));
            $this->assertEquals('yup', $proStdC->proString);
        }
        if (($proSimpleArr = $popo1->getProSimpleArr())) {
            $this->assertTrue(is_array($proSimpleArr));
            $this->assertInstanceOf(Models\SimplePopo::class, $proSimpleArr[0]);
        }
        $popo2 = $objectMapper->map(json_encode($popo1), Models\NestedPopo::class);
        $this->assertEquals($popo1, $popo2);
    }
}
