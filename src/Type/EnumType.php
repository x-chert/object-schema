<?php

declare(strict_types=1);

namespace Xchert\Schema\Type;

use Symfony\Component\Validator\Constraints as Assert;
use Xchert\Schema\Exception\InvalidSchemaException;

class EnumType extends Type
{
    public const string NAME = 'enum';

    /**
     * @param array<int|string> $values
     */
    public function __construct(
        public readonly array $values,
    ) {
        if ($values === []) {
            throw new InvalidSchemaException('An enum type needs at least one value.');
        }

        foreach ($values as $value) {
            if (!\is_int($value) && !\is_string($value)) {
                throw new InvalidSchemaException('The values of an enum type must be strings or integers.');
            }
        }
    }

    public static function fromExpression(?string $argument, Types $types): static
    {
        $values = \array_map(\trim(...), \explode(',', self::requireArgument($argument)));

        return new static(\array_map(static fn(string $value) => \preg_match('/^-?\d+$/', $value) ? (int)$value : $value, $values));
    }

    public static function fromArray(array $data, Types $types): static
    {
        return new static($data['values'] ?? throw new InvalidSchemaException('Enum type is missing the "values".'));
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    public function getConstraints(): array
    {
        return [new Assert\Choice(choices: $this->values)];
    }

    public function jsonSerialize(): array
    {
        return [...parent::jsonSerialize(), 'values' => $this->values];
    }

    public function __toString(): string
    {
        return \sprintf('enum<%s>', \implode(',', $this->values));
    }
}
