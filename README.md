# xchert/object-schema

Format-independent schemas for data structures. A schema describes the properties of an object, their types and their
constraints.

## Defining schemas

Schemas are data themselves and can be written in any format that decodes to arrays, e.g. YAML:

```yaml
-   name: order
    properties:
        - { name: id, type: integer }
        - { name: number, type: string, constraints: [ { $constraint: NotBlank }, { $constraint: Length, max: 20 } ] }
        - { name: placedAt, type: datetime }
        - { name: status, type: 'enum<open,shipped,cancelled>', default: open }
        - { name: item, type: 'list<object<item>>', constraints: [ { $constraint: Count, min: 1 } ] }
        - { name: note, type: string, nullable: true }
-   name: item
    properties:
        - { name: sku, type: string }
        - { name: quantity, type: integer, constraints: [ { $constraint: Positive } ] }
```

```php
$registry = SchemaRegistry::fromArray(Yaml::parseFile('order.yaml'));
$json = Json::encode($registry);                         // and back with SchemaRegistry::fromArray()
```

Or in PHP: `new ObjectSchema('order', [new PropertySchema('id', new IntegerType()), ...])`.

Types: `string`, `integer` (`int`), `float`, `boolean` (`bool`), `datetime` (RFC 3339), `date` (`Y-m-d`),
`dateinterval` (`duration`, ISO 8601 duration such as `P1DT2H`),
`enum<a,b,...>`, `object<schema>` and `list<type>`. Constraints are any Symfony constraints, serialized with their class
(short name for built-in ones) and the options that differ from the default.

Schemas not added to the registry can be supplied on demand by a `SchemaProvider`, e.g. from files or from other
definitions.

## Custom types

A type extends `Type` and is registered in `Types`, which parses type expressions and restores types from JSON:

```php
final readonly class MoneyType extends Type
{
    public function __construct(public string $currency) {}

    public static function getName(): string
    {
        return 'money';
    }

    // "money<EUR>"
    public static function fromExpression(?string $argument, Types $types): static
    {
        return new self(self::requireArgument($argument));
    }

    // {"name": "money", "currency": "EUR"}
    public static function fromArray(array $data, Types $types): static
    {
        return new self($data['currency']);
    }

    public function getConstraints(): array
    {
        return [new Assert\Type('string'), new Assert\Regex('/^-?\d+\.\d{2} '.$this->currency.'$/')];
    }

    public function jsonSerialize(): array
    {
        return [...parent::jsonSerialize(), 'currency' => $this->currency];
    }

    public function __toString(): string
    {
        return "money<{$this->currency}>";
    }
}

$types = new Types([MoneyType::class]);
$registry = SchemaRegistry::fromArray(Yaml::parseFile('order.yaml'), $types);
```

Types without an argument only implement `getName()` and `getConstraints()`. A registered type replaces a built-in type
of the same name.
