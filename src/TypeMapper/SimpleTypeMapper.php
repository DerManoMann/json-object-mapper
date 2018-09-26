<?php

declare(strict_types=1);

/*
* This file is part of the ObjectMapper library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ObjectMapper\TypeMapper;

/**
 * Simple class name mapper.
 */
class SimpleTypeMapper implements TypeMapperInterface
{
    protected $fromClassName;
    protected $toClassName;

    public function __construct(string $fromClassName, string $toClassName)
    {
        $this->fromClassName = $fromClassName;
        $this->toClassName = $toClassName;
    }

    /**
     * @inheritdoc
     */
    public function resolve($className, $json): ?string
    {
        if (is_object($json) && $this->fromClassName == $className) {
            return $this->toClassName;
        }

        return null;
    }
}
