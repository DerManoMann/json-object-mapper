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

    public function __construct(string $scalarType)
    {
        $this->scalarType = $scalarType;
    }

    /**
     * @inheritdoc
     */
    public function isCollection(): bool
    {
        return false;
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
