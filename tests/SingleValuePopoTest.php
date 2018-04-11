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

/**
 */
class SingleValuePopoTest extends TestCase
{
    public function testCtorValue()
    {
        $json = '{"value":"foo"}';

        $objectMapper = new ObjectMapper();

        $popo = $objectMapper->map($json, Models\SingleValuePopo::class);
        $this->assertEquals('foo', $popo->getValue());
    }
}
