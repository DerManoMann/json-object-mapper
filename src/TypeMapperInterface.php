<?php declare(strict_types=1);

/*
* This file is part of the ObjectMapper library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ObjectMapper;

/**
 * Maps a single data type to an (optional) target type
 */
interface TypeMapperInterface
{
    public function map($value, ?TypeReferenceInterface $typeReference = null);
}
