<?php

declare(strict_types=1);

namespace Xchert\Schema;

use Xchert\Schema\Exception\InvalidSchemaException;
use Xchert\Schema\Type\Types;
use Xchert\Util\Json;

final readonly class ObjectSchema implements \JsonSerializable
{
    /**
     * @var array<string, PropertySchema>
     */
    public array $properties;

    /**
     * @param iterable<PropertySchema> $properties
     */
    public function __construct(
        public string $name,
        iterable $properties = [],
        public ?string $description = null,
    ) {
        $indexed = [];

        foreach ($properties as $property) {
            if (isset($indexed[$property->name])) {
                throw new InvalidSchemaException(\sprintf('Property "%s" is defined more than once in schema "%s".', $property->name, $name));
            }

            $indexed[$property->name] = $property;
        }

        $this->properties = $indexed;
    }

    public function hasProperty(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    public function getProperty(string $name): PropertySchema
    {
        return $this->properties[$name] ?? throw new InvalidSchemaException(\sprintf('Schema "%s" has no property "%s".', $this->name, $name));
    }

    public function withProperties(PropertySchema ...$properties): self
    {
        $merged = $this->properties;

        foreach ($properties as $property) {
            $merged[$property->name] = $property;
        }

        return new self($this->name, $merged, $this->description);
    }

    public function jsonSerialize(): array
    {
        $data = ['name' => $this->name];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        $data['properties'] = \array_values($this->properties);

        return $data;
    }

    public static function fromArray(array $data, Types $types = new Types()): self
    {
        return new self(
            name: $data['name'] ?? throw new InvalidSchemaException('Schema definition is missing the "name".'),
            properties: \array_map(static fn(array $property) => PropertySchema::fromArray($property, $types), $data['properties'] ?? []),
            description: $data['description'] ?? null,
        );
    }

    /**
     * @throws \JsonException
     */
    public static function fromJson(string $json, Types $types = new Types()): self
    {
        return self::fromArray(Json::decode($json), $types);
    }
}
