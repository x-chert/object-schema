<?php

declare(strict_types=1);

namespace Xchert\Schema;

use Symfony\Component\Validator\Constraint;
use Xchert\Schema\Exception\InvalidSchemaException;
use Xchert\Schema\Normalizer\ConstraintNormalizer;
use Xchert\Schema\Type\Type;
use Xchert\Schema\Type\Types;

final readonly class PropertySchema implements \JsonSerializable
{
    /**
     * @param list<Constraint> $constraints
     */
    public function __construct(
        public string $name,
        public Type $type,
        public bool $nullable = false,
        public array $constraints = [],
        public ?string $description = null,
        public bool $hasDefault = false,
        public mixed $default = null,
    ) {
        foreach ($constraints as $constraint) {
            if (!$constraint instanceof Constraint) {
                throw new InvalidSchemaException(\sprintf('Constraints of property "%s" must be instances of "%s".', $name, Constraint::class));
            }
        }
    }

    public static function fromArray(array $data, Types $types = new Types()): self
    {
        $type = $data['type'] ?? throw new InvalidSchemaException('Property definition is missing the "type".');

        return new self(
            name: $data['name'] ?? throw new InvalidSchemaException('Property definition is missing the "name".'),
            type: $types->from($type),
            nullable: $data['nullable'] ?? false,
            constraints: ConstraintNormalizer::denormalizeAll($data['constraints'] ?? []),
            description: $data['description'] ?? null,
            hasDefault: \array_key_exists('default', $data),
            default: $data['default'] ?? null,
        );
    }

    /**
     * @return list<Constraint>
     */
    public function getAllConstraints(): array
    {
        return [...$this->type->getConstraints(), ...$this->constraints];
    }

    public function jsonSerialize(): array
    {
        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'nullable' => $this->nullable,
        ];

        if ($this->constraints !== []) {
            $data['constraints'] = ConstraintNormalizer::normalizeAll($this->constraints);
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->hasDefault) {
            $data['default'] = $this->default;
        }

        return $data;
    }
}
