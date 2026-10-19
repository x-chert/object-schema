<?php

declare(strict_types=1);

namespace Xchert\Schema\Type;

use Symfony\Component\Validator\Constraints as Assert;
use Xchert\Schema\Exception\InvalidSchemaException;

class ListType extends Type
{
    public const string NAME = 'list';

    public function __construct(
        public readonly Type $itemType,
    ) {}

    public static function fromExpression(?string $argument, Types $types): static
    {
        return new static($types->parse(self::requireArgument($argument)));
    }

    public static function fromArray(array $data, Types $types): static
    {
        return new static($types->fromArray($data['items'] ?? throw new InvalidSchemaException('List type is missing the "items".')));
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    public function getConstraints(): array
    {
        return [new Assert\Type('list'), new Assert\All(constraints: $this->itemType->getConstraints())];
    }

    public function jsonSerialize(): array
    {
        return [...parent::jsonSerialize(), 'items' => $this->itemType];
    }

    public function __toString(): string
    {
        return \sprintf('list<%s>', $this->itemType);
    }
}
