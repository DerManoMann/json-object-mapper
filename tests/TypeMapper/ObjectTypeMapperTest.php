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
use Radebatz\ObjectMapper\Tests\Models\PhpDocUnionPopo;
use Radebatz\ObjectMapper\Tests\Models\SimplePopo;
use Radebatz\ObjectMapper\Tests\TestUtils;
use Radebatz\ObjectMapper\TypeMapper\ObjectTypeMapper;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;

class ObjectTypeMapperTest extends TestCase
{
    use TestUtils;

    public function mapCases()
    {
        return [
            [(object) ['proString' => 3], new ObjectTypeReference(new SimplePopo()), ObjectMapperException::class],
            [(object) ['proString' => 'foo'], new ObjectTypeReference(new SimplePopo()), null],
            [(object) ['union' => 'foo'], new ObjectTypeReference(new PhpDocUnionPopo()), null],
            [(object) ['union' => 3], new ObjectTypeReference(new PhpDocUnionPopo()), null],
            [(object) ['union' => []], new ObjectTypeReference(new PhpDocUnionPopo()), ObjectMapperException::class],
        ];
    }

    /**
     * @dataProvider mapCases
     */
    public function testMap($value, $typeReference, $fail)
    {
        $mapper = new ObjectTypeMapper(new ObjectMapper());

        try {
            $mapper->map($value, $typeReference);
            $this->assertNull($fail, '*Expected* to fail');
        } catch (ObjectMapperException $e) {
            $this->assertEquals($fail, get_class($e), 'Expected *NOT* to fail');
        }
    }
}
