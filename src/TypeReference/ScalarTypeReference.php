<?php declare(strict_types=1);

/*
* This file is part of the ObjectMapper library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ObjectMapper\TypeReference;

use Radebatz\ObjectMapper\TypeReferenceInterface;

/**
 * Scalar type reference.
 */
class ScalarTypeReference implements TypeReferenceInterface
{
    protected $scalarType;
    protected $nullable;

    public function __construct(string $scalarType, bool $nullable = true)
    {
        $this->scalarType = $scalarType;
        $this->nullable = $nullable;
    }

    /**
     * @inheritdoc
     */
    public function isCollection(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return $this->getScalarType();
    }

    public function getScalarType(): string
    {
        return $this->scalarType;
    }

    public function setScalarType(string $scalarType)
    {
        $this->scalarType = $scalarType;
    }
}
