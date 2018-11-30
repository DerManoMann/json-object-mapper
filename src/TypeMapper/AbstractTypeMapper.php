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
use Radebatz\ObjectMapper\TypeMapperInterface;

abstract class AbstractTypeMapper implements TypeMapperInterface
{
    protected $objectMapper;

    public function __construct(ObjectMapper $objectMapper)
    {
        $this->objectMapper = $objectMapper;
    }

    public function getObjectMapper(): ObjectMapper
    {
        return $this->objectMapper;
    }

    protected function resolveValueType(string $className, $json): string
    {
        foreach ($this->getObjectMapper()->getValueTypeResolvers() as $valueTypeResolver) {
            if ($mappedTypeClass = $valueTypeResolver->resolve($className, $json)) {
                return $mappedTypeClass;
            }
        }

        return $className;
    }

    protected function handleUnmappedProperty($obj, string $key, $value)
    {
        $objectMapper = $this->getObjectMapper();

        if ($logger = $objectMapper->getLogger()) {
            $logger->debug(sprintf('Handling unmapped property; name=%s, class=%s', $key, get_class($obj)));
        }

        if ($unknownPropertyHandler = $objectMapper->getOption(ObjectMapper::OPTION_UNKNOWN_PROPERTY_HANDLER)) {
            return call_user_func($unknownPropertyHandler, $obj, $key, $value);
        }

        if (!$objectMapper->getOption(ObjectMapper::OPTION_IGNORE_UNKNOWN)) {
            throw new ObjectMapperException(sprintf('Unmapped property; name=%s, class=%s', $key, get_class($obj)));
        }

        return null;
    }

    protected function verifyRequiredProperties(string $className, array $properties, array $mappedProperties): void
    {
        foreach ($properties as $property) {
            if ($docBlock = $this->getObjectMapper()->getDocBlockCache()->getPropertyDocBlock($className, $property)) {
                if ($docBlock->getTagsByName('required')) {
                    if (!in_array($property, $mappedProperties)) {
                        throw new ObjectMapperException(sprintf('Missing required property; name=%s, class=%s', $property, $className));
                    }
                }
            }
        }
    }
}
