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
use Radebatz\ObjectMapper\TypeMapper\CollectionTypeMapper;
use Radebatz\ObjectMapper\TypeMapper\NoopTypeMapper;
use Radebatz\ObjectMapper\TypeMapper\ObjectTypeMapper;
use Radebatz\ObjectMapper\TypeMapper\ScalarTypeMapper;
use Radebatz\ObjectMapper\TypeReference\ClassTypeReference;
use Radebatz\ObjectMapper\TypeReference\CollectionTypeReference;
use Radebatz\ObjectMapper\TypeReference\ObjectTypeReference;
use Radebatz\ObjectMapper\TypeReference\ScalarTypeReference;
use Radebatz\ObjectMapper\Utils\VariadicPropertyAccessor;
use Radebatz\PropertyInfoExtras\Extractor\DocBlockCache;
use Radebatz\PropertyInfoExtras\Extractor\DocBlockMagicExtractor;
use Radebatz\PropertyInfoExtras\PropertyInfoExtraExtractor;
use Radebatz\PropertyInfoExtras\PropertyInfoExtraExtractorAdapter;
use Radebatz\PropertyInfoExtras\PropertyInfoExtraExtractorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
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
    public const OPTION_VARIADIC_SETTER = 'variadicSetter';

    /** @var array */
    protected $options = [];
    /** @var LoggerInterface */
    protected $logger;
    /** @var array */
    protected $namingMappers = [];
    /** @var array */
    protected $valueTypeResolvers = [];
    /** @var DocBlockCache */
    protected $docBlockCache = null;
    /** @var PropertyInfoExtraExtractorInterface */
    protected $propertyInfoExtractor = null;
    /** @var PropertyAccessorInterface */
    protected $propertyAccessor = null;
    /** @var array */
    protected $objectTypeMappers = [];

    public function __construct(array $options = [], ?LoggerInterface $logger = null, DocBlockCache $docBlockCache = null, PropertyInfoExtractor $propertyInfoExtractor = null, PropertyAccess $propertyAccess = null)
    {
        $this->options = array_merge($this->getDefaultOptions(), $options);
        if ($this->options[self::OPTION_UNKNOWN_PROPERTY_HANDLER] && !is_callable($this->options[self::OPTION_UNKNOWN_PROPERTY_HANDLER])) {
            throw new ObjectMapperException(sprintf('Option "%s" must be callable', self::OPTION_UNKNOWN_PROPERTY_HANDLER));
        }
        $this->logger = $logger ?: new NullLogger();

        $this->namingMappers = [
            new NoopNamingMapper(),
        ];

        $this->docBlockCache = $docBlockCache ?: new DocBlockCache();
        $this->propertyInfoExtractor = $propertyInfoExtractor ?: $this->getDefaultPropertyInfoExtractor();
        if (!$this->propertyInfoExtractor instanceof PropertyInfoExtraExtractorInterface) {
            $this->propertyInfoExtractor = new PropertyInfoExtraExtractorAdapter($this->propertyInfoExtractor);
        }

        $this->propertyAccessor = $propertyAccess ?: $this->getDefaultPropertyAccessor();
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
            self::OPTION_VARIADIC_SETTER => false,
        ];
    }

    protected function getDefaultPropertyInfoExtractor(): PropertyInfoExtraExtractorInterface
    {
        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();
        $docBlockMagicExtractor = new DocBlockMagicExtractor(new DocBlockCache());

        $listExtractors = [
            $reflectionExtractor,
            $docBlockMagicExtractor,
        ];

        $typeExtractors = [
            $phpDocExtractor,
            $reflectionExtractor,
            $docBlockMagicExtractor,
        ];

        $descriptionExtractors = [
            $phpDocExtractor,
        ];

        $accessExtractors = [
            $reflectionExtractor,
        ];

        return new PropertyInfoExtraExtractor(
            $listExtractors,
            $typeExtractors,
            $descriptionExtractors,
            $accessExtractors
        );
    }

    protected function getDefaultPropertyAccessor(): PropertyAccessorInterface
    {
        if ($this->isOption(self::OPTION_VARIADIC_SETTER) && defined(sprintf('%s::MAGIC_CALL', PropertyAccessor::class))) {
            $propertyAccessor = new VariadicPropertyAccessor(PropertyAccessor::MAGIC_CALL);
        } else {
            $propertyAccessorBuilder = PropertyAccess::createPropertyAccessorBuilder();
            $propertyAccessorBuilder->enableMagicCall();
            $propertyAccessor = $propertyAccessorBuilder->getPropertyAccessor();
        }

        return $propertyAccessor;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function isOption(string $name): bool
    {
        return array_key_exists($name, $this->options) ? (bool) $this->options[$name] : false;
    }

    public function getOption(string $name)
    {
        return array_key_exists($name, $this->options) ? $this->options[$name] : null;
    }

    public function addNamingMapper(NamingMapperInterface $namingMapper): void
    {
        // default is always last
        array_unshift($this->namingMappers, $namingMapper);
    }

    /**
     * @return NamingMapperInterface[]
     */
    public function getNamingMappers(): array
    {
        return $this->namingMappers;
    }

    public function setNamingMappers(array $namingMappers): void
    {
        $this->namingMappers = $namingMappers;
    }

    public function addValueTypeResolver(ValueTypeResolverInterface $valueTypeResolver): void
    {
        $this->valueTypeResolvers[] = $valueTypeResolver;
    }

    /**
     * @return ValueTypeResolverInterface[]
     */
    public function getValueTypeResolvers(): array
    {
        return $this->valueTypeResolvers;
    }

    public function setValueTypeResolvers(array $valueTypeResolvers): void
    {
        $this->valueTypeResolvers = $valueTypeResolvers;
    }

    public function setObjectTypeMapper(string $className, TypeMapperInterface $objectTypeMapper): void
    {
        $this->objectTypeMappers[$className] = $objectTypeMapper;
    }

    /**
     * @return TypeMapperInterface[]
     */
    public function getObjectTypeMappers(): array
    {
        return $this->objectTypeMappers;
    }

    public function setObjectTypeMappers(array $objectTypeMappers): void
    {
        $this->objectTypeMappers = $objectTypeMappers;
    }

    public function getDocBlockCache(): DocBlockCache
    {
        return $this->docBlockCache;
    }

    public function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->propertyAccessor;
    }

    public function getPropertyInfoExtractor(): PropertyInfoExtraExtractorInterface
    {
        return $this->propertyInfoExtractor;
    }

    /**
     * Map a given (complex) value onto a new/different type.
     *
     * `$type` may be `null` if the given `$value` is a scalar.
     *
     * @param mixed                                     $value   the value
     * @param null|string|object|TypeReferenceInterface $type    the target type
     * @param bool                                      $encoded if set to true, `string` values will be json_decoded; defaults to `true`
     *
     * @return mixed the mapping result
     */
    public function map($value, $type = null, bool $encoded = true)
    {
        if ($encoded && is_string($value)) {
            $value = json_decode($value);
        }

        if ($type && !($type instanceof TypeReferenceInterface)) {
            if (!is_string($type) && !is_object($type)) {
                throw new \InvalidArgumentException(sprintf('Invalid type: %s', gettype($type)));
            }
            $type = is_object($type) ? new ObjectTypeReference($type) : new ClassTypeReference($type);
        }

        $mapper = $this->getTypeMapper($value, $type);

        if (null === $type && !($mapper instanceof SimpleTypeMapperInterface)) {
            throw new \InvalidArgumentException('Type must not be null');
        }

        return $mapper->map($value, $type);
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
            switch (get_class($typeReference)) {
                case ScalarTypeReference::class:
                    return new ScalarTypeMapper($this);

                case CollectionTypeReference::class:
                    return new CollectionTypeMapper($this);

                case ClassTypeReference::class:
                    /** @var ClassTypeReference $typeReference */
                    $className = $typeReference->getClassName();
                    $classInterfaces = class_implements($className);
                    foreach ($this->objectTypeMappers as $name => $objectTypeMapper) {
                        if ($name === $className || in_array($name, $classInterfaces)) {
                            return $objectTypeMapper;
                        }
                    }

                    return new ObjectTypeMapper($this);

                default:
                    return new ObjectTypeMapper($this);
            }
        }

        if (is_scalar($value)) {
            return new ScalarTypeMapper($this);
        }

        return new NoopTypeMapper($this);
    }
}
