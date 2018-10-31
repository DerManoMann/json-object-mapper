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

class SingleValueTest extends TestCase
{
    public function testDateTime()
    {
        $json = '{"date":"2018-10-31 12:12:22"}';
        $options = [
            ObjectMapper::OPTION_STRICT_TYPES => false,
        ];
        $objectMapper = $this->getObjectMapper($options);
        $simplePopo = $objectMapper->map($json, SimplePopo::class);
        $this->assertInstanceOf(SimplePopo::class, $simplePopo);
    }
}
