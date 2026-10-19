<?php

declare(strict_types=1);

namespace Xchert\Schema\Test\Fixture;

use Symfony\Component\Validator\Constraints as Assert;
use Xchert\Schema\Exception\InvalidSchemaException;
use Xchert\Schema\Type\Type;
use Xchert\Schema\Type\Types;

final class MoneyType extends Type
{
    public function __construct(
        public readonly string $currency,
    ) {}

    public static function getName(): string
    {
        return 'money';
    }

    public static function fromExpression(?string $argument, Types $types): static
    {
        return new self(self::requireArgument($argument));
    }

    public static function fromArray(array $data, Types $types): static
    {
        return new self($data['currency'] ?? throw new InvalidSchemaException('Money type is missing the "currency".'));
    }

    public function getConstraints(): array
    {
        return [new Assert\Type('string'), new Assert\Regex('/^-?\d+\.\d{2} '.\preg_quote($this->currency, '/').'$/')];
    }

    public function jsonSerialize(): array
    {
        return [...parent::jsonSerialize(), 'currency' => $this->currency];
    }

    public function __toString(): string
    {
        return \sprintf('money<%s>', $this->currency);
    }
}
