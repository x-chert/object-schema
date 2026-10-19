<?php

declare(strict_types=1);

namespace Xchert\Schema\Type;

use Symfony\Component\Validator\Constraints as Assert;
use Xchert\Schema\Exception\InvalidSchemaException;

class ObjectType extends Type
{
    public const string NAME = 'object';

    public function __construct(
        public readonly string $schema,
    ) {}

    public static function fromExpression(?string $argument, Types $types): static
    {
        return new static(self::requireArgument($argument));
    }

    public static function fromArray(array $data, Types $types): static
    {
        return new static($data['schema'] ?? throw new InvalidSchemaException('Object type is missing the "schema".'));
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    public function getConstraints(): array
    {
        return [new Assert\Type('array')];
    }

    public function jsonSerialize(): array
    {
        return [...parent::jsonSerialize(), 'schema' => $this->schema];
    }

    public function __toString(): string
    {
        return \sprintf('object<%s>', $this->schema);
    }
}
