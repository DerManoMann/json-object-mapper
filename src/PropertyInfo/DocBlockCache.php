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
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\ContextFactory;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

class DocBlockCache
{
    protected $docBlockFactory;
    protected $mutatorPrefixes;
    protected $accessorPrefixes;
    protected $arrayMutatorPrefixes;
    protected $contextFactory;
    /** @var DocBlock[] */
    protected $docBlocks = [];

    /**
     * @param DocBlockFactoryInterface $docBlockFactory
     * @param string[]|null            $mutatorPrefixes
     * @param string[]|null            $accessorPrefixes
     */
    public function __construct(DocBlockFactoryInterface $docBlockFactory = null, array $mutatorPrefixes = null, array $accessorPrefixes = null, array $arrayMutatorPrefixes = null)
    {
        if (!class_exists(DocBlockFactory::class)) {
            throw new \RuntimeException(sprintf('Unable to use the "%s" class as the "phpdocumentor/reflection-docblock" package is not installed.', __CLASS__));
        }

        $this->docBlockFactory = $docBlockFactory ?: DocBlockFactory::createInstance();
        $this->mutatorPrefixes = null !== $mutatorPrefixes ? $mutatorPrefixes : ReflectionExtractor::$defaultMutatorPrefixes;
        $this->accessorPrefixes = null !== $accessorPrefixes ? $accessorPrefixes : ReflectionExtractor::$defaultAccessorPrefixes;
        $this->arrayMutatorPrefixes = null !== $arrayMutatorPrefixes ? $arrayMutatorPrefixes : ReflectionExtractor::$defaultArrayMutatorPrefixes;
        $this->contextFactory = new ContextFactory();
    }

    public function getMutatorPrefixes()
    {
        return $this->mutatorPrefixes;
    }

    public function getAccessorPrefixes()
    {
        return $this->accessorPrefixes;
    }

    public function getArrayMutatorPrefixes(): array
    {
        return $this->arrayMutatorPrefixes;
    }

    public function getClassDocBlock(string $class): ?DocBlock
    {
        if (array_key_exists($class, $this->docBlocks)) {
            return $this->docBlocks[$class];
        }

        try {
            $reflectionClass = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            return null;
        }

        $docContext = $this->contextFactory->createFromReflector($reflectionClass);
        $docBlock = $this->docBlockFactory->create($reflectionClass, $docContext);

        return $this->docBlocks[$class] = $docBlock;
    }

    public function getPropertyDocBlock(string $class, string $property): ?DocBlock
    {
        $docBlockHash = sprintf('%s::%s', $class, $property);
        if (array_key_exists($docBlockHash, $this->docBlocks)) {
            return $this->docBlocks[$docBlockHash];
        }

        $ucProperty = ucfirst($property);

        try {
            switch (true) {
                case $docBlock = $this->getDocBlockFromProperty($class, $property):
                    break;

                case $docBlock = $this->getDocBlockFromMethod($class, $ucProperty, $this->mutatorPrefixes):
                    break;

                case $docBlock = $this->getDocBlockFromMethod($class, $ucProperty, $this->accessorPrefixes):
                    break;

                default:
                    $docBlock = null;
            }
        } catch (\InvalidArgumentException $e) {
            $docBlock = null;
        }

        return $this->docBlocks[$docBlockHash] = $docBlock;
    }

    protected function getDocBlockFromProperty(string $class, string $property): ?DocBlock
    {
        // use ReflectionProperty instead of $class to get the actual parent class if applicable
        try {
            $reflectionProperty = new \ReflectionProperty($class, $property);
        } catch (\ReflectionException $e) {
            return null;
        }

        if ($reflectionProperty->isStatic() || !$reflectionProperty->isPublic()) {
            return null;
        }

        return $this->docBlockFactory->create($reflectionProperty, $this->contextFactory->createFromReflector($reflectionProperty->getDeclaringClass()));
    }

    private function getDocBlockFromMethod(string $class, string $ucFirstProperty, array $prefixes): ?DocBlock
    {
        $reflectionMethod = null;
        foreach ($prefixes as $prefix) {
            $methodName = $prefix . $ucFirstProperty;

            try {
                $reflectionMethod = new \ReflectionMethod($class, $methodName);
                if ($reflectionMethod->isStatic()) {
                    continue;
                }
            } catch (\ReflectionException $e) {
                // ignore
            }
        }

        if (!$reflectionMethod) {
            return null;
        }

        return $this->docBlockFactory->create($reflectionMethod, $this->contextFactory->createFromReflector($reflectionMethod));
    }
}
