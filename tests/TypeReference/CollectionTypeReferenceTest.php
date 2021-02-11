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

namespace Radebatz\ObjectMapper\Tests\TypeReference;

use PHPUnit\Framework\TestCase;
use Radebatz\ObjectMapper\Tests\Models\SimplePopo;
use Radebatz\ObjectMapper\Tests\TestUtils;
use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\CollectionTypeReference;

class CollectionTypeReferenceTest extends TestCase
{
    use TestUtils;

    public function typeCases()
    {
        return [
            'default-collection-type' => [SimplePopo::class, null, sprintf('array<%s>', SimplePopo::class)],
            'array-object-collection-type' => [SimplePopo::class, \ArrayObject::class, sprintf('ArrayObject<%s>', SimplePopo::class)],
            'type-ref-value-type' => [new ClassTypeReference(SimplePopo::class), null, sprintf('array<%s>', SimplePopo::class)],
        ];
    }

    /**
     * @dataProvider typeCases
     */
    public function testGetType($valueType, $collectionType, $expected)
    {
        $reference = new CollectionTypeReference($valueType, $collectionType);

        $this->assertEquals($expected, $reference->getType());
    }
}
