<?php

declare(strict_types=1);

namespace Xchert\Schema\Type;

use Symfony\Component\Validator\Constraint;
use Xchert\Schema\Exception\InvalidSchemaException;
use Xchert\Util\Pod\Pod;

abstract class Type extends Pod implements \Stringable
{
    abstract public static function getName(): string;

    /**
     * @return array<Constraint>
     */
    abstract public function getConstraints(): array;

    /**
     * @return list<string>
     */
    public static function getAliases(): array
    {
        return [];
    }

    /**
     * Creates the type from the argument in angle brackets of its expression, e.g. "item" of "object<item>".
     */
    public static function fromExpression(?string $argument, Types $types): static
    {
        if ($argument !== null) {
            throw new InvalidSchemaException(\sprintf('Type "%s" does not accept an argument.', static::getName()));
        }

        return new static();
    }

    public static function fromArray(array $data, Types $types): static
    {
        return new static();
    }

    protected static function requireArgument(?string $argument): string
    {
        return $argument ?? throw new InvalidSchemaException(\sprintf('Type "%s" requires an argument in angle brackets.', static::getName()));
    }

    public function jsonSerialize(): array
    {
        return ['name' => static::getName()];
    }

    public function __toString(): string
    {
        return static::getName();
    }

    public function toArray(): array
    {
        return ['name' => static::getName(), ...parent::toArray()];
    }
}
