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
use Psr\Log\NullLogger;
use Radebatz\ObjectMapper\NamingMapper\NoopNamingMapper;
use Radebatz\ObjectMapper\PropertyInfo\DocBlockCache;
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

    /** @var LoggerInterface */
    protected $logger;
    /** @var array */
    protected $namingMappers = [];
    /** @var array */
    protected $valueTypeResolvers = [];
    /** @var DocBlockCache */
    protected $docBlockCache = null;
    /** @var PropertyInfoExtractor */
    protected $propertyInfoExtractor = null;
    /** @var PropertyAccessor */
    protected $propertyAccessor = null;

    public function __construct(array $options = [], ?LoggerInterface $logger = null, DocBlockCache $docBlockCache = null, PropertyInfoExtractor $propertyInfoExtractor = null, PropertyAccess $propertyAccess = null)
    {
        $this->options = array_merge($this->getDefaultOptions(), $options);
        if ($this->options[self::OPTION_UNKNOWN_PROPERTY_HANDLER] && !is_callable($this->options[self::OPTION_UNKNOWN_PROPERTY_HANDLER])) {
            throw new ObjectMapperException('Option "unknownPropertyHandler" must be callable');
        }
        $this->logger = $logger ?: new NullLogger();

        $this->namingMappers = [
            new NoopNamingMapper(),
        ];

        $this->docBlockCache = $docBlockCache ?: new DocBlockCache();
        $this->propertyInfoExtractor = $propertyInfoExtractor ?: $this->getDefaultPropertyInfoExtractor();
        $this->propertyAccessor = $propertyAccess ?: $this->getDefaultPropertyAccessor();
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    protected function getDefaultOptions(): array
    {
        return [
            self::OPTION_STRICT_TYPES => true,
            self::OPTION_STRICT_COLLECTIONS => true,
            self::OPTION_STRICT_NULL => true,
            self::OPTION_IGNORE_UNKNOWN => true,
            self::OPTION_VERIFY_REQUIRED => false,
            self::OPTION_INSTANTIATE_REQUIRE_CTOR => true,
            self::OPTION_UNKNOWN_PROPERTY_HANDLER => null,
        ];
    }

    public function getOption(string $name)
    {
        return array_key_exists($name, $this->options) ? $this->options[$name] : null;
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

    public function getDocBlockCache(): DocBlockCache
    {
        return $this->docBlockCache;
    }

    public function getPropertyAccessor(): PropertyAccessor
    {
        return $this->propertyAccessor;
    }

    protected function getDefaultPropertyAccessor(): PropertyAccessor
    {
        return PropertyAccess::createPropertyAccessor();
    }

    public function getPropertyInfoExtractor(): PropertyInfoExtractor
    {
        return $this->propertyInfoExtractor;
    }

    protected function getDefaultPropertyInfoExtractor(): PropertyInfoExtractor
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
     * @param mixed                                     $value   the value
     * @param null|string|object|TypeReferenceInterface $type    the target type
     * @param bool                                      $encoded if set to true, `string` values will be json_decoded; defaults to `true`
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
