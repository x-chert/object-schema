# xchert/object-schema

Format-independent schemas for data structures, e.g. documents exchanged as JSON, YAML or XML. A schema describes the
properties of an object, their types and their Symfony constraints. It has no relation to PHP classes: data is
handled as plain arrays and scalars.

## Defining schemas

Schemas are data themselves and can be written in any format that decodes to arrays, e.g. YAML:

```yaml
- name: order
  properties:
    - { name: id, type: integer }
    - { name: number, type: string, constraints: [{ $constraint: NotBlank }, { $constraint: Length, max: 20 }] }
    - { name: placedAt, type: datetime }
    - { name: status, type: 'enum<open,shipped,cancelled>', default: open }
    - { name: item, type: 'list<object<item>>', constraints: [{ $constraint: Count, min: 1 }] }
    - { name: note, type: string, nullable: true }
- name: item
  properties:
    - { name: sku, type: string }
    - { name: quantity, type: integer, constraints: [{ $constraint: Positive }] }
```

```php
$registry = SchemaRegistry::fromArray(Yaml::parseFile('order.yaml'));
$json = Json::encode($registry);                         // and back with SchemaRegistry::fromArray()
```

Or in PHP: `new ObjectSchema('order', [new PropertySchema('id', new IntegerType()), ...])`.

Types: `string`, `integer`, `float`, `boolean`, `datetime` (RFC 3339), `date` (`Y-m-d`), `uuid`, `ulid`, `json`
(anything), `enum<a,b,...>`, `object<schema>` and `list<type>`. Constraints are any Symfony constraints, serialized with
their class (short name for built-in ones) and the options that differ from the default.

Schemas not added to the registry can be supplied on demand by a `SchemaProvider`, e.g. from files or from other
definitions.
