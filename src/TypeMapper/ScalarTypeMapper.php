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
use Radebatz\ObjectMapper\ObjectMapperException;
use Radebatz\ObjectMapper\TypeReference\ScalarTypeReference;
use Radebatz\ObjectMapper\TypeReferenceInterface;

/**
 * Maps a scalar including type juggling.
 */
class ScalarTypeMapper extends AbstractTypeMapper
{
    public function map($value, ?TypeReferenceInterface $typeReference = null)
    {
        if (!$typeReference || !($strictTypes = $this->getObjectMapper()->getOption(ObjectMapper::OPTION_STRICT_TYPES))) {
            return $value;
        }

        if ($typeReference instanceof ScalarTypeReference && null !== $value) {
            $compatibleType = $this->getCompatibleType(gettype($value), $scalarType = $typeReference->getScalarType());

            if ($strictTypes && !$compatibleType) {
                throw new ObjectMapperException(sprintf('Incompatible data type; type=%s, expected=%s', gettype($value), $compatibleType));
            }

            if (false === settype($value, $compatibleType ?: $scalarType)) {
                throw new ObjectMapperException(sprintf('Incompatible data type; type=%s', gettype($value)));
            }
        }

        return $value;
    }

    protected function getCompatibleType(string $type, string $expected): ?string
    {
        $alias = [
            'int' => 'integer',
            'bool' => 'boolean',
        ];

        if ($type === $expected) {
            return $type;
        }

        if ($expected && array_key_exists($expected, $alias) && $alias[$expected] === $type) {
            return $type;
        }

        $downcasts = [
            'float' => ['double', 'integer'],
        ];

        // allow some casting, in particular around floats...
        if ($expected && array_key_exists($expected, $downcasts) && in_array($type, $downcasts[$expected])) {
            return $expected;
        }

        return null;
    }
}
