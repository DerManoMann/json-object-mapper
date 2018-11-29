<?php declare(strict_types=1);

/*
* This file is part of the ObjectMapper library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ObjectMapper\TypeMapper;

use Radebatz\ObjectMapper\ObjectMapper;
use Radebatz\ObjectMapper\TypeMapperInterface;

abstract class AbstractTypeMapper implements TypeMapperInterface
{
    protected $objectMapper;

    public function __construct(ObjectMapper $objectMapper)
    {
        $this->objectMapper = $objectMapper;
    }

    public function getObjectMapper(): ObjectMapper
    {
        return $this->objectMapper;
    }
}
