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
use Radebatz\ObjectMapper\SimpleTypeMapperInterface;
use Radebatz\ObjectMapper\TypeReference\ScalarTypeReference;
use Radebatz\ObjectMapper\TypeReferenceInterface;

/**
 * Maps a scalar including type juggling.
 */
class ScalarTypeMapper extends AbstractTypeMapper implements SimpleTypeMapperInterface
{
    public function map($value, ?TypeReferenceInterface $typeReference = null, $key = null)
    {
        $mappedValue = $value;

        if (!$typeReference) {
            return $mappedValue;
        }

        if ($typeReference instanceof ScalarTypeReference && null !== $mappedValue) {
            $strictTypes = $this->getObjectMapper()->isOption(ObjectMapper::OPTION_STRICT_TYPES);

            $compatibleType = $this->getCompatibleType($mappedValue, $scalarType = $typeReference->getScalarType(), $strictTypes);

            if (($strictTypes && !$compatibleType) || !is_scalar($mappedValue)) {
                throw new ObjectMapperException(sprintf('Incompatible data type; key=%s, type=%s, expected=%s', $key, gettype($value), $scalarType));
            }

            if ($compatibleType && false === settype($mappedValue, $compatibleType)) {
                throw new ObjectMapperException(sprintf('Incompatible data type; key=%s, type=%s', $key, gettype($value)));
            }
        }

        return $mappedValue;
    }

    protected function getCompatibleType($value, string $expectedType, bool $strictTypes = true): ?string
    {
        $valueType = gettype($value);

        $alias = [
            'int' => 'integer',
            'bool' => 'boolean',
        ];
        $expectedType = array_key_exists($expectedType, $alias) ? $alias[$expectedType] : $expectedType;

        if ($valueType === $expectedType) {
            return $valueType;
        }

        $casts = [
            'integer' => [
                'type' => 'numeric',
                'strict' => [],
                'compatible' => ['string'],
            ],
            'float' => [
                'type' => 'numeric',
                'strict' => ['double', 'integer'],
                'compatible' => ['string'],
            ],
            'boolean' => [
                'type' => 'boolean',
                'strict' => [],
                'compatible' => ['string'],
            ],
            'string' => [
                'type' => 'string',
                'strict' => [],
                'compatible' => ['integer', 'float', 'double', 'boolean'],
            ],
        ];

        if (array_key_exists($expectedType, $casts)) {
            $cast = $casts[$expectedType];

            $castTypes = $strictTypes ? ['strict'] : ['strict', 'compatible'];

            foreach ($castTypes as $castType) {
                if (in_array($valueType, $cast[$castType])) {
                    switch ($cast['type']) {
                        case 'numeric':
                            if (is_numeric($value)) {
                                return $expectedType;
                            }
                            break;

                        case 'boolean':
                            if (is_bool($value) || is_numeric($value)) {
                                return $expectedType;
                            }
                            break;

                        case 'string':
                            return $expectedType;
                    }
                }
            }
        }

        return null;
    }
}
