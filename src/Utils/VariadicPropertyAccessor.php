<?php

namespace Radebatz\ObjectMapper\Utils;

use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;

class VariadicPropertyAccessor extends PropertyAccessor
{
    public function setValue(&$objectOrArray, $propertyPath, $value)
    {
        try {
            return parent::setValue($objectOrArray, $propertyPath, $value);
        } catch (InvalidArgumentException $e) {
            if (!$this->setVariadic($objectOrArray, $propertyPath, $value)) {
                throw $e;
            }
        }
    }

    protected function setVariadic(&$objectOrArray, $propertyPath, $value): bool
    {
        try {
            $mutator = $this->getWriteInfo(get_class($objectOrArray), $propertyPath, $value);

            if (PropertyWriteInfo::TYPE_METHOD !== $mutator->getType()) {
                return false;
            }

            $rm = new \ReflectionMethod($objectOrArray, $mutator->getName());
            $parameters = $rm->getParameters();
            if (!$parameters || !$parameters[0]->isVariadic()) {
                return false;
            }

            $objectOrArray->{$mutator->getName()}(...$value);

            return true;
        } catch (\Throwable $t) {
            return false;
        }
    }

    private function getWriteInfo(string $class, string $property, $value): PropertyWriteInfo
    {
        $writeInfoExtractor = new ReflectionExtractor(['set'], null, null, false);

        $useAdderAndRemover = \is_array($value) || $value instanceof \Traversable;
        $mutator = $writeInfoExtractor->getWriteInfo($class, $property, [
            'enable_getter_setter_extraction' => true,
            'enable_magic_methods_extraction' => PropertyAccessor::MAGIC_GET | PropertyAccessor::MAGIC_SET,
            'enable_constructor_extraction' => false,
            'enable_adder_remover_extraction' => $useAdderAndRemover,
        ]);

        return $mutator;
    }
}
