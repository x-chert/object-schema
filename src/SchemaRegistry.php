<?php

declare(strict_types=1);

namespace Xchert\Schema;

use Xchert\Schema\Exception\SchemaNotFoundException;
use Xchert\Schema\Type\Types;

/**
 * @implements \IteratorAggregate<string, ObjectSchema>
 */
class SchemaRegistry implements \JsonSerializable, \IteratorAggregate
{
    /**
     * @var array<string, ObjectSchema>
     */
    private array $schemas = [];

    /**
     * @var list<SchemaProvider>
     */
    private array $providers = [];

    /**
     * @param iterable<ObjectSchema>   $schemas
     * @param iterable<SchemaProvider> $providers
     */
    public function __construct(iterable $schemas = [], iterable $providers = [])
    {
        foreach ($schemas as $schema) {
            $this->add($schema);
        }

        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    public function add(ObjectSchema $schema): void
    {
        $this->schemas[$schema->name] = $schema;
    }

    public function addProvider(SchemaProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    public function has(string $name): bool
    {
        return $this->find($name) !== null;
    }

    /**
     * @throws SchemaNotFoundException
     */
    public function get(string $name): ObjectSchema
    {
        return $this->find($name) ?? throw SchemaNotFoundException::forName($name);
    }

    /**
     * @return array<string, ObjectSchema>
     */
    public function all(): array
    {
        foreach ($this->providers as $provider) {
            foreach ($provider->getSchemaNames() as $name) {
                $this->find($name);
            }
        }

        return $this->schemas;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->all();
    }

    public function jsonSerialize(): array
    {
        return \array_values($this->all());
    }

    public static function fromArray(array $data, Types $types = new Types()): self
    {
        return new self(\array_map(static fn(array $schema) => ObjectSchema::fromArray($schema, $types), $data));
    }

    private function find(string $name): ?ObjectSchema
    {
        if (isset($this->schemas[$name])) {
            return $this->schemas[$name];
        }

        foreach ($this->providers as $provider) {
            $schema = $provider->getSchema($name);

            if ($schema !== null) {
                return $this->schemas[$name] = $schema;
            }
        }

        return null;
    }
}
