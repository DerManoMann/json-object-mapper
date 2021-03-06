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
 * Type reference to map into a new instance of `$className`.
 */
class ClassTypeReference implements TypeReferenceInterface
{
    protected $className;
    protected $nullable;

    public function __construct(string $className, bool $nullable = true)
    {
        $this->className = $className;
        $this->nullable = $nullable;
    }

    /**
     * @inheritdoc
     */
    public function isCollection(): bool
    {
        return \ArrayObject::class == $this->className || is_subclass_of($this->className, \ArrayObject::class);
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
        return $this->getClassName();
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function setClassName(string $className)
    {
        $this->className = $className;
    }
}
