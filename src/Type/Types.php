<?php

declare(strict_types=1);

namespace Xchert\Schema\Type;

use Xchert\Schema\Exception\InvalidSchemaException;

class Types
{
    public const array BUILTIN = [
        StringType::class,
        IntegerType::class,
        FloatType::class,
        BooleanType::class,
        DateTimeType::class,
        DateType::class,
        DateIntervalType::class,
        EnumType::class,
        ObjectType::class,
        ListType::class,
    ];

    /**
     * @var array<string, class-string<Type>>
     */
    private array $types = [];

    /**
     * @param iterable<class-string<Type>> $types
     */
    public function __construct(iterable $types = [])
    {
        foreach (self::BUILTIN as $type) {
            $this->add($type);
        }

        foreach ($types as $type) {
            $this->add($type);
        }
    }

    /**
     * @param class-string<Type> $type
     */
    public function add(string $type): void
    {
        if (!\is_subclass_of($type, Type::class)) {
            throw new InvalidSchemaException(\sprintf('"%s" does not extend "%s".', $type, Type::class));
        }

        foreach ([$type::getName(), ...$type::getAliases()] as $name) {
            $this->types[\strtolower($name)] = $type;
        }
    }

    public function has(string $name): bool
    {
        return isset($this->types[\strtolower($name)]);
    }

    public function parse(string $expression): Type
    {
        $expression = \trim($expression);

        if (!\preg_match('/^([a-z][\w-]*)(?:<(.+)>)?$/i', $expression, $match)) {
            throw new InvalidSchemaException(\sprintf('Invalid type expression "%s".', $expression));
        }

        return $this->get($match[1])::fromExpression(isset($match[2]) ? \trim($match[2]) : null, $this);
    }

    public function fromArray(array $data): Type
    {
        $name = $data['name'] ?? throw new InvalidSchemaException('Type definition is missing the "name".');

        return $this->get($name)::fromArray($data, $this);
    }

    public function from(string|array $type): Type
    {
        return \is_string($type) ? $this->parse($type) : $this->fromArray($type);
    }

    /**
     * @return class-string<Type>
     */
    private function get(string $name): string
    {
        return $this->types[\strtolower($name)] ?? throw new InvalidSchemaException(\sprintf('Unknown type "%s".', $name));
    }
}
