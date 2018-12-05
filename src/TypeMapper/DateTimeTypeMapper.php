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
 * Maps single values onto `\DateTime` instance.
 */
class DateTimeTypeMapper extends AbstractTypeMapper
{
    public function map($value, ?TypeReferenceInterface $typeReference = null)
    {
        if (!is_scalar($value)) {
            throw new \InvalidArgumentException('Expecting scalar value');
        }

        if (is_numeric($value)) {
            $value = '@' . $value;
        }

        return new \DateTime($value);
    }
}
