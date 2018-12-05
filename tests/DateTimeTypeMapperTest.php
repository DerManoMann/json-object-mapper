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

use Radebatz\ObjectMapper\ObjectMapper;
use Radebatz\ObjectMapper\Tests\Models\SimplePopo;
use Radebatz\ObjectMapper\TypeMapper\DateTimeTypeMapper;

class DateTimeTypeMapperTest extends TestCase
{
    public function json()
    {
        $dt = new \DateTime();
        $dateTime = $dt->format(\DateTime::ATOM);
        $timestamp = $dt->getTimestamp();

        return [
            [sprintf('{"date":"%s"}', $dateTime), $timestamp],
            [sprintf('{"date":%s}', $timestamp), $timestamp],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testDateTime($json, $timestamp)
    {
        $options = [
            ObjectMapper::OPTION_STRICT_TYPES => false,
        ];
        $objectMapper = $this->getObjectMapper($options);
        $objectMapper->setObjectTypeMapper(\DateTime::class, new DateTimeTypeMapper($objectMapper));

        /** @var SimplePopo $simplePopo */
        $simplePopo = $objectMapper->map($json, SimplePopo::class);
        $this->assertInstanceOf(SimplePopo::class, $simplePopo);
        $this->assertNotNull($simplePopo->getDate());
        // use timestamp here as we do lose millis otherwise
        $this->assertEquals($timestamp, $simplePopo->getDate()->getTimestamp());
    }
}
