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
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;

class ObjectTypeReferenceTest extends TestCase
{
    use TestUtils;

    public function testGetType()
    {
        $reference = new ObjectTypeReference(new SimplePopo());

        $this->assertEquals(SimplePopo::class, $reference->getType());
    }
}
