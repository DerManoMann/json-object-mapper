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
use Radebatz\ObjectMapper\ObjectMapperException;

class VerifyRequiredTest extends TestCase
{
    use TestUtils;

    public function json()
    {
        return [
            ['{"pubString":"pub","proString":"pro","priString":"pri"}', true],
            ['{"proInt":1,"proBool":false}', false],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json, $fail)
    {
        $objectMapper = $this->getObjectMapper([
            ObjectMapper::OPTION_VERIFY_REQUIRED => true,
        ]);

        try {
            $objectMapper->map($json, Models\SimplePopo::class);

            $this->assertFalse($fail, '*Expected* to fail');
        } catch (ObjectMapperException $e) {
            $this->assertTrue($fail, 'Expected *NOT* to fail');
        }
    }
}
