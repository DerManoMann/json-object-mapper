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
use Radebatz\ObjectMapper\Tests\Models\AnotherPopo;
use Radebatz\ObjectMapper\Tests\Models\NestedPopo;
use Radebatz\ObjectMapper\Tests\Models\PopoInterface;
use Radebatz\ObjectMapper\Tests\Models\SimplePopo;
use Radebatz\ObjectMapper\ValueTypeResolverInterface;

class ValueTypeResolverTest extends TestCase
{
    use TestUtils;

    public function json()
    {
        $date = new \DateTime();

        return [
            ['{"proInter":{"foo":"bar"}}', AnotherPopo::class],
            ['{"proInter":{"proString":"bar"}}', SimplePopo::class],
        ];
    }

    /**
     * @dataProvider json
     */
    public function testJson($json, $mappedTypeClass)
    {
        $objectMapper = $this->getObjectMapper();

        $objectMapper->addValueTypeResolver(
            new class() implements ValueTypeResolverInterface {
                public function resolve($className, $json): ?string
                {
                    if (is_object($json) && PopoInterface::class == $className) {
                        if (property_exists($json, 'foo')) {
                            return AnotherPopo::class;
                        }

                        return SimplePopo::class;
                    }

                    return null;
                }
            }
        );

        $popo1 = $objectMapper->map($json, NestedPopo::class);
        $this->assertInstanceOf($mappedTypeClass, $popo1->getProInter());
        $this->assertInstanceOf(NestedPopo::class, $popo1);

        $popo2 = $objectMapper->map(json_encode($popo1), NestedPopo::class);
        $this->assertEquals($popo1, $popo2);
    }
}
