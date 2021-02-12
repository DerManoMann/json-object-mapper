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

namespace Radebatz\ObjectMapper\Tests\TypeMapper;

use PHPUnit\Framework\TestCase;
use Radebatz\ObjectMapper\ObjectMapper;
use Radebatz\ObjectMapper\ObjectMapperException;
use Radebatz\ObjectMapper\Tests\TestUtils;
use Radebatz\ObjectMapper\TypeMapper\ScalarTypeMapper;
use Radebatz\ObjectMapper\TypeReference\ScalarTypeReference;

class ScalarTypeMapperTest extends TestCase
{
    use TestUtils;

    public function mapCases()
    {
        return [
            'integer' => [3, new ScalarTypeReference('integer'), 3, [], null],
            'integer-strict' => ['3', new ScalarTypeReference('integer'), null, [], ObjectMapperException::class],
            'integer-cast' => ['3', new ScalarTypeReference('integer'), 3, [ObjectMapper::OPTION_STRICT_TYPES => false], null],
            'boolean' => [true, new ScalarTypeReference('boolean'), true, [], null],
            'boolean-strict' => [1, new ScalarTypeReference('boolean'), null, [], ObjectMapperException::class],
            'boolean-cast-integer-true' => [3, new ScalarTypeReference('boolean'), true, [ObjectMapper::OPTION_STRICT_TYPES => false], null],
            'boolean-cast-integer-false' => [0, new ScalarTypeReference('boolean'), false, [ObjectMapper::OPTION_STRICT_TYPES => false], null],
            'boolean-cast-string' => ['3', new ScalarTypeReference('boolean'), true, [ObjectMapper::OPTION_STRICT_TYPES => false], null],
            'string' => ['foo', new ScalarTypeReference('string'), 'foo', [], null],
            'string-strict' => [3, new ScalarTypeReference('string'), null, [], ObjectMapperException::class],
            'string-cast-integer' => [3, new ScalarTypeReference('string'), '3', [ObjectMapper::OPTION_STRICT_TYPES => false], null],
            'string-cast-boolean' => [true, new ScalarTypeReference('string'), '1', [ObjectMapper::OPTION_STRICT_TYPES => false], null],
        ];
    }

    /**
     * @dataProvider mapCases
     */
    public function testMap($value, $typeReference, $expected, $config, $fail)
    {
        $mapper = new ScalarTypeMapper(new ObjectMapper($config));

        try {
            $mapped = $mapper->map($value, $typeReference);
            $this->assertNull($fail, '*Expected* to fail');
            $this->assertTrue($expected === $mapped);
        } catch (ObjectMapperException $e) {
            $this->assertEquals($fail, get_class($e), 'Expected *NOT* to fail');
        }
    }
}
