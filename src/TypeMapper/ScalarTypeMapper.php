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

use Radebatz\ObjectMapper\TypeReferenceInterface;

/**
 * Maps a scalar including type juggling.
 */
class ScalarTypeMapper extends AbstractTypeMapper
{
    public function map($value, ?TypeReferenceInterface $typeReference = null)
    {
        // TODO: type validation

        if (!$typeReference) {
            return $value;
        }

        // TODO: type juggling / mapping
        return $value;
    }
}
