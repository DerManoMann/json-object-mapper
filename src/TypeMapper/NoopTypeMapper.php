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

use Radebatz\ObjectMapper\SimpleTypeMapperInterface;
use Radebatz\ObjectMapper\TypeReferenceInterface;

/**
 * No-op mapper.
 */
class NoopTypeMapper extends AbstractTypeMapper implements SimpleTypeMapperInterface
{
    public function map($value, ?TypeReferenceInterface $typeReference = null, $key = null)
    {
        return $value;
    }
}
