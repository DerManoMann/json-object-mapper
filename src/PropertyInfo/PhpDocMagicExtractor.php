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

namespace Radebatz\ObjectMapper\PropertyInfo;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Nullable;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\Void_;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

class PhpDocMagicExtractor implements PropertyListExtractorInterface, PropertyTypeExtractorInterface
{
    protected $dockBlockCache;
    /** @var string[] */
    protected $properties = [];

    public function __construct(DocBlockCache $dockBlockCache)
    {
        $this->dockBlockCache = $dockBlockCache;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties($class, array $context = [])
    {
        if (array_key_exists($class, $this->properties)) {
            return $this->properties[$class];
        }

        if (!$docBlock = $this->dockBlockCache->getClassDocBlock($class)) {
            return null;
        }

        $properties = [];

        /** @var DocBlock\Tags\Method $method */
        foreach ($docBlock->getTagsByName('method') as $method) {
            if ($method->isStatic()) {
                continue;
            }
            $propertyName = $this->getPropertyName($method->getMethodName());
            if ($propertyName && !preg_match('/^[A-Z]{2,}/', $propertyName)) {
                $propertyName = lcfirst($propertyName);
                $properties[$propertyName] = $propertyName;
            }
        }

        return $this->properties[$class] = ($properties ? array_values($properties) : null);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes($class, $property, array $context = [])
    {
        if (!$docBlock = $this->dockBlockCache->getClassDocBlock($class)) {
            return null;
        }

        $properties = $this->getProperties($class, $context);
        if (!in_array($property, $properties)) {
            return null;
        }

        $type = null;
        $ucProperty = ucfirst($property);

        /** @var DocBlock\Tags\Method $method */
        foreach ($docBlock->getTagsByName('method') as $method) {
            $methodName = $method->getMethodName();
            foreach ($this->dockBlockCache->getMutatorPrefixes() as $mutatorPrefix) {
                if ($mutatorPrefix . $ucProperty == $methodName) {
                    if ($arguments = $method->getArguments()) {
                        $argument = $arguments[0];
                        $magicType = $argument['type'];
                        if ($nullable = $magicType instanceof Nullable) {
                            $magicType = $magicType->getActualType();
                        }
                        switch (get_class($magicType)) {
                            case Array_::class:
                                $type = new Type(Type::BUILTIN_TYPE_ARRAY, $nullable, null, true);
                                break;
                            case Void_::class:
                                $type = new Type(Type::BUILTIN_TYPE_NULL, $nullable);
                                break;
                            case Object_::class:
                                $type = new Type(Type::BUILTIN_TYPE_OBJECT, $nullable, (string) $magicType);
                                break;
                            default:
                                $type = new Type((string) $magicType, $nullable);
                                break;
                        }
                        break;
                    }
                }
            }
        }

        return $type ? [$type] : null;
    }

    protected function getPropertyName(string $methodName): ?string
    {
        $pattern = implode('|', array_merge($this->dockBlockCache->getAccessorPrefixes(), $this->dockBlockCache->getMutatorPrefixes()));

        if ('' !== $pattern && preg_match('/^(' . $pattern . ')(.+)$/i', $methodName, $matches)) {
            return $matches[2];
        }

        return null;
    }
}
