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
use Radebatz\ObjectMapper\Tests\Models\DeserializerAwareCollection;
use Radebatz\ObjectMapper\TypeReference\CollectionTypeReference;

class DeserializerAwareTest extends TestCase
{
    use TestUtils;

    public function testPopo()
    {
        $objectMapper = $this->getObjectMapper();

        $json = '{"proString":"foo"}';
        /** @var Models\DeserializerAwarePopo $popo */
        $popo = $objectMapper->map($json, Models\DeserializerAwarePopo::class);
        $this->assertInstanceOf(Models\DeserializerAwarePopo::class, $popo);
        $this->assertEquals('i:nulld:foo', $popo->getAware());
    }

    public function testCollection()
    {
        $objectMapper = $this->getObjectMapper();

        $json = '{"ping":"pong"}';
        /** @var Models\DeserializerAwareCollection $popo */
        $popo = $objectMapper->map($json, new CollectionTypeReference(\stdClass::class, DeserializerAwareCollection::class));
        $this->assertInstanceOf(Models\DeserializerAwareCollection::class, $popo);
        $this->assertEquals('i:0d:1', $popo->getAware());
    }
}
