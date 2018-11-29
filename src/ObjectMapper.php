<?php declare(strict_types=1);

/*
* This file is part of the ObjectMapper library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ObjectMapper;

use Psr\Log\LoggerInterface;
use Radebatz\ObjectMapper\NamingMapper\NoopNamingMapper;
use Radebatz\ObjectMapper\TypeMapper\CollectionTypeMapper;
use Radebatz\ObjectMapper\TypeMapper\NoopTypeMapper;
use Radebatz\ObjectMapper\TypeMapper\ObjectTypeMapper;
use Radebatz\ObjectMapper\TypeMapper\ScalarTypeMapper;
use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

class ObjectMapper
{
    public const OPTION_STRICT_TYPES = 'strictTypes';
    public const OPTION_STRICT_COLLECTIONS = 'strictCollections';
    public const OPTION_STRICT_NULL = 'strictNull';
    public const OPTION_IGNORE_UNKNOWN = 'ignoreUnknown';
    public const OPTION_VERIFY_REQUIRED = 'verifyRequired';
    public const OPTION_INSTANTIATE_REQUIRE_CTOR = 'instantiateRequireCtor';
    public const OPTION_UNKNOWN_PROPERTY_HANDLER = 'unknownPropertyHandler';

    /** @var array */
    protected $namingMappers = [];

    /** @var array */
    protected $valueTypeResolvers = [];

    public function __construct(array $options = [], ?LoggerInterface $logger = null)
    {
        $this->namingMappers = [
            new NoopNamingMapper(),
        ];
    }

    public function setNamingMappers(array $namingMappers): void
    {
        $this->namingMappers = $namingMappers;
    }

    public function addNamingMapper(NamingMapperInterface $namingMapper): void
    {
        // default is always last
        array_unshift($this->namingMappers, $namingMapper);
    }

    public function getNamingMappers(): array
    {
        return $this->namingMappers;
    }

    public function setValueTypeResolvers(array $valueTypeResolvers): void
    {
        $this->valueTypeResolvers = $valueTypeResolvers;
    }

    public function addValueTypeResolver(ValueTypeResolverInterface $valueTypeResolver): void
    {
        $this->valueTypeResolvers[] = $valueTypeResolver;
    }

    public function getValueTypeResolvers(): array
    {
        return $this->valueTypeResolvers;
    }

    public function getPropertyAccessor(): PropertyAccessor
    {
        return PropertyAccess::createPropertyAccessor();
    }

    public function getPropertyInfoExtractor(): PropertyInfoExtractor
    {
        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();

        $listExtractors = [
            $reflectionExtractor,
        ];

        $typeExtractors = [
            $phpDocExtractor,
            $reflectionExtractor,
        ];

        $descriptionExtractors = [
            $phpDocExtractor,
        ];

        $accessExtractors = [
            $reflectionExtractor,
        ];

        return new PropertyInfoExtractor(
            $listExtractors,
            $typeExtractors,
            $descriptionExtractors,
            $accessExtractors
        );
    }

    /**
     * Resolve data types via registered value type resolvers.
     */
    public function resolveValueType(string $className, $json): string
    {
        foreach ($this->valueTypeResolvers as $valueTypeResolver) {
            if ($mappedTypeClass = $valueTypeResolver->resolve($className, $json)) {
                return $mappedTypeClass;
            }
        }
        return $className;
    }

    /**
     * Get the appropriate type mapper for the give data and type.
     */
    public function getTypeMapper($value, ?TypeReferenceInterface $typeReference): TypeMapperInterface
    {
        if ((!$typeReference && is_array($value)) || ($typeReference && $typeReference->isCollection())) {
            return new CollectionTypeMapper($this);
        }

        if ($typeReference) {
            // assume class
            return new ObjectTypeMapper($this);
        }

        if (is_scalar($value)) {
            return new ScalarTypeMapper($this);
        }

        return new NoopTypeMapper($this);
    }

    /**
     * Map a given (complex) value onto a new/different type.
     *
     * @param mixed $value The value.
     * @param null|string|object|TypeReferenceInterface $type The target type.
     * @param bool $encoded If set to true, `string` values will be json_decoded; defaults to `true`.
     */
    public function map($value, $type = null, bool $encoded = true)
    {
        if ($encoded && is_string($value)) {
            $value = json_decode($value);
        }

        if ($type && !($type instanceof TypeReferenceInterface)) {
            $type = is_object($type) ? new ObjectTypeReference($type) : new ClassTypeReference($type);
        }

        $mapper = $this->getTypeMapper($value, $type);

        return $mapper->map($value, $type);
    }
}
