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

/**
 */
class NoDefaultCtorPopoTest extends TestCase
{
    /**
     * @expectedException \Radebatz\ObjectMapper\ObjectMapperException
     * @expectedExceptionMessage Unable to instantiate value object; class=\Radebatz\ObjectMapper\Tests\Models\NoDefaultCtorPopo
     */
    public function testCtorValueFail()
    {
        $json = '{"value":"foo","other":false}';

        $objectMapper = $this->getObjectMapper();

        $objectMapper->map($json, Models\NoDefaultCtorPopo::class);
    }

    public function testCtorValue()
    {
        $json = '{"value":"foo","other":false}';

        $objectMapper = $this->getObjectMapper([
            ObjectMapper::OPTION_INSTANTIATE_REQUIRE_CTOR => false,
        ]);

        $popo = $objectMapper->map($json, Models\NoDefaultCtorPopo::class);
        $this->assertFalse($popo->isOther());
        $this->assertNull($popo->getValue());
    }
}
