<?php

declare(strict_types=1);

namespace Xchert\Schema\Test\TestCase;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Yaml\Yaml;
use Xchert\Schema\Exception\InvalidSchemaException;
use Xchert\Schema\Exception\SchemaNotFoundException;
use Xchert\Schema\Normalizer\ConstraintNormalizer;
use Xchert\Schema\ObjectSchema;
use Xchert\Schema\PropertySchema;
use Xchert\Schema\SchemaProvider;
use Xchert\Schema\SchemaRegistry;
use Xchert\Schema\Type\EnumType;
use Xchert\Schema\Type\ListType;
use Xchert\Schema\Type\ObjectType;
use Xchert\Schema\Type\StringType;
use Xchert\Util\Json;

class SchemaTest extends TestCase
{
    public function testLoadsSchemasFromYaml(): void
    {
        $registry = SchemaRegistry::fromArray(Yaml::parseFile(__DIR__.'/../Resources/order.yaml'));
        $order = $registry->get('order');

        self::assertSame('An order with its items', $order->description);
        self::assertEquals(new ListType(new ObjectType('item')), $order->getProperty('item')->type);
        self::assertEquals(new EnumType(['open', 'shipped', 'cancelled']), $order->getProperty('status')->type);
        self::assertSame('open', $order->getProperty('status')->default);
        self::assertTrue($order->getProperty('note')->nullable);
        self::assertEquals([new Assert\NotBlank(), new Assert\Length(max: 20)], $order->getProperty('number')->constraints);
    }

    public function testSchemasSurviveJsonRoundTrip(): void
    {
        $registry = SchemaRegistry::fromArray(Yaml::parseFile(__DIR__.'/../Resources/order.yaml'));

        $json = Json::encode($registry);
        $restored = SchemaRegistry::fromArray(Json::decode($json));

        self::assertSame($json, Json::encode($restored));
        self::assertEquals($registry->get('order'), $restored->get('order'));
    }

    public function testPropertyJsonIsCompact(): void
    {
        $property = new PropertySchema('name', new StringType(), constraints: [new Assert\NotBlank(), new Assert\Length(max: 100)], description: 'Full name');

        self::assertSame(
            [
                'name' => 'name',
                'type' => ['name' => 'string'],
                'nullable' => false,
                'constraints' => [['$constraint' => 'NotBlank'], ['$constraint' => 'Length', 'max' => 100]],
                'description' => 'Full name',
            ],
            Json::decode(Json::encode($property)),
        );
    }

    public function testRegistryAsksProvidersForMissingSchemas(): void
    {
        $provider = new class implements SchemaProvider {
            public int $calls = 0;

            public function getSchema(string $name): ?ObjectSchema
            {
                ++$this->calls;

                return $name === 'address' ? new ObjectSchema('address', [new PropertySchema('city', new StringType())]) : null;
            }

            public function getSchemaNames(): array
            {
                return ['address'];
            }
        };

        $registry = new SchemaRegistry([new ObjectSchema('customer')], [$provider]);

        self::assertSame('city', $registry->get('address')->getProperty('city')->name);
        $registry->get('address');
        self::assertSame(1, $provider->calls, 'Provided schemas are kept.');
        self::assertSame(['customer', 'address'], \array_keys($registry->all()));

        $this->expectException(SchemaNotFoundException::class);
        $registry->get('unknown');
    }

    public function testNormalizesNestedConstraints(): void
    {
        $constraint = new Assert\All([new Assert\NotBlank(), new Assert\Choice(choices: ['a', 'b'])]);

        $data = ConstraintNormalizer::normalize($constraint);

        self::assertSame(
            ['$constraint' => 'All', 'constraints' => [['$constraint' => 'NotBlank'], ['$constraint' => 'Choice', 'choices' => ['a', 'b']]]],
            $data,
        );
        self::assertEquals($constraint, ConstraintNormalizer::denormalize($data));
    }

    public function testRejectsNonConstraintClasses(): void
    {
        $this->expectException(InvalidSchemaException::class);

        ConstraintNormalizer::denormalize(['$constraint' => \ArrayObject::class]);
    }

    public function testRejectsOptionsThatCannotBeSerialized(): void
    {
        $this->expectException(InvalidSchemaException::class);

        ConstraintNormalizer::normalize(new Assert\Callback(static fn() => null));
    }

    public function testRejectsDuplicateProperties(): void
    {
        $this->expectException(InvalidSchemaException::class);

        new ObjectSchema('broken', [new PropertySchema('a', new StringType()), new PropertySchema('a', new StringType())]);
    }
}
