<?php

declare(strict_types=1);

namespace Xchert\Schema\Normalizer;

use Symfony\Component\Validator\Constraint;
use Xchert\Schema\Exception\InvalidSchemaException;

class ConstraintNormalizer
{
    public const string CLASS_KEY = '$constraint';

    private const string BUILTIN_NAMESPACE = 'Symfony\\Component\\Validator\\Constraints\\';

    /**
     * @throws InvalidSchemaException
     */
    public static function normalize(Constraint $constraint): array
    {
        $class = $constraint::class;
        $data = [self::CLASS_KEY => \str_starts_with($class, self::BUILTIN_NAMESPACE) ? \substr($class, \strlen(self::BUILTIN_NAMESPACE)) : $class];

        $constructor = new \ReflectionClass($constraint)->getConstructor();

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();

            if (!\property_exists($constraint, $name)) {
                continue;
            }

            $property = new \ReflectionProperty($constraint, $name);

            if (!$property->isPublic() || !$property->isInitialized($constraint)) {
                continue;
            }

            $value = $constraint->$name;

            if (self::isDefault($constraint, $parameter, $value)) {
                continue;
            }

            $data[$name] = self::normalizeValue($value, $class, $name);
        }

        return $data;
    }

    /**
     * @throws InvalidSchemaException
     */
    public static function denormalize(array $data): Constraint
    {
        $class = $data[self::CLASS_KEY] ?? throw new InvalidSchemaException(\sprintf('Constraint definition is missing the "%s" key.', self::CLASS_KEY));
        unset($data[self::CLASS_KEY]);

        if (!\str_contains($class, '\\')) {
            $class = self::BUILTIN_NAMESPACE.$class;
        }

        if (!\is_a($class, Constraint::class, true)) {
            throw new InvalidSchemaException(\sprintf('"%s" is not a constraint.', $class));
        }

        $options = \array_map(self::denormalizeValue(...), $data);

        try {
            return new $class(...$options);
        } catch (\Throwable $e) {
            throw new InvalidSchemaException(\sprintf('Constraint "%s" cannot be created: %s', $class, $e->getMessage()), 0, $e);
        }
    }

    /**
     * @param iterable<Constraint> $constraints
     *
     * @return list<array>
     */
    public static function normalizeAll(iterable $constraints): array
    {
        $result = [];

        foreach ($constraints as $constraint) {
            $result[] = self::normalize($constraint);
        }

        return $result;
    }

    /**
     * @return array<Constraint>
     */
    public static function denormalizeAll(array $data): array
    {
        return \array_values(\array_map(self::denormalize(...), $data));
    }

    private static function isDefault(Constraint $constraint, \ReflectionParameter $parameter, mixed $value): bool
    {
        if ($parameter->getName() === 'groups' && $value === [Constraint::DEFAULT_GROUP]) {
            return true;
        }

        if ($value === null) {
            return true;
        }

        if ($parameter->isDefaultValueAvailable() && $parameter->getDefaultValue() === $value) {
            return true;
        }

        $property = new \ReflectionProperty($constraint, $parameter->getName());

        return $property->hasDefaultValue() && $property->getDefaultValue() === $value;
    }

    private static function normalizeValue(mixed $value, string $class, string $option): mixed
    {
        return match (true) {
            $value === null, \is_scalar($value) => $value,
            $value instanceof Constraint => self::normalize($value),
            $value instanceof \BackedEnum => $value->value,
            \is_array($value) => \array_map(static fn(mixed $item) => self::normalizeValue($item, $class, $option), $value),
            default => throw new InvalidSchemaException(\sprintf('Option "%s" of constraint "%s" holds a value of type "%s", which cannot be serialized.', $option, $class, \get_debug_type($value))),
        };
    }

    private static function denormalizeValue(mixed $value): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        if (isset($value[self::CLASS_KEY])) {
            return self::denormalize($value);
        }

        return \array_map(self::denormalizeValue(...), $value);
    }
}
