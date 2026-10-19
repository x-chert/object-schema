<?php

declare(strict_types=1);

namespace Xchert\Schema\Test\TestCase;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Xchert\Schema\Exception\InvalidSchemaException;
use Xchert\Schema\SchemaRegistry;
use Xchert\Schema\Test\Fixture\MoneyType;
use Xchert\Schema\Test\Fixture\TrimmedStringType;
use Xchert\Schema\Type\BooleanType;
use Xchert\Schema\Type\DateIntervalType;
use Xchert\Schema\Type\EnumType;
use Xchert\Schema\Type\IntegerType;
use Xchert\Schema\Type\ListType;
use Xchert\Schema\Type\Types;
use Xchert\Util\Json;

class TypesTest extends TestCase
{
    public static function expressions(): iterable
    {
        yield ['string'];
        yield ['integer'];
        yield ['float'];
        yield ['boolean'];
        yield ['datetime'];
        yield ['date'];
        yield ['dateinterval'];
        yield ['enum<open,shipped,1>'];
        yield ['object<address>'];
        yield ['list<list<date>>'];
    }

    #[DataProvider('expressions')]
    public function testParsesAndPrintsExpressions(string $expression): void
    {
        $types = new Types();
        $type = $types->parse($expression);

        self::assertSame($expression, (string)$type);
        self::assertEquals($type, $types->fromArray(Json::decode(Json::encode($type))));
    }

    public function testResolvesAliases(): void
    {
        $types = new Types();

        self::assertEquals(new IntegerType(), $types->parse('int'));
        self::assertEquals(new BooleanType(), $types->parse('Bool'));
        self::assertEquals(new DateIntervalType(), $types->parse('duration'));
    }

    public function testRejectsInvalidExpressions(): void
    {
        $this->expectException(InvalidSchemaException::class);

        new Types()->parse('string<foo>');
    }

    public function testRequiresArguments(): void
    {
        $this->expectException(InvalidSchemaException::class);

        new Types()->parse('list');
    }

    public function testRejectsUnknownTypes(): void
    {
        $this->expectException(InvalidSchemaException::class);

        new Types()->parse('money<EUR>');
    }

    public function testParsesCustomTypes(): void
    {
        $types = new Types([MoneyType::class]);

        self::assertTrue($types->has('money'));
        self::assertEquals(new ListType(new MoneyType('EUR')), $types->parse('list<money<EUR>>'));
        self::assertEquals(new MoneyType('USD'), $types->fromArray(['name' => 'money', 'currency' => 'USD']));
    }

    public function testRestoresSchemasWithCustomTypes(): void
    {
        $types = new Types([MoneyType::class]);
        $registry = SchemaRegistry::fromArray([['name' => 'item', 'properties' => [['name' => 'price', 'type' => 'money<EUR>']]]], $types);

        $restored = SchemaRegistry::fromArray(Json::decode(Json::encode($registry)), $types);

        self::assertEquals(new MoneyType('EUR'), $restored->get('item')->getProperty('price')->type);
    }

    public function testCustomTypesCanReplaceBuiltInTypes(): void
    {
        $types = new Types();
        $types->add(TrimmedStringType::class);

        self::assertInstanceOf(TrimmedStringType::class, $types->parse('string'));
    }

    public function testCopiesTypesWithoutCreatingANameProperty(): void
    {
        $copy = EnumType::from(new EnumType(['open', 'shipped']));

        self::assertEquals(new EnumType(['open', 'shipped']), $copy);
        self::assertFalse(property_exists($copy, 'name'));
        self::assertSame(['name' => 'enum', 'values' => ['open', 'shipped']], $copy->toArray());
    }

    public static function dateIntervals(): iterable
    {
        yield ['P1Y2M', true];
        yield ['P2W3D', true];
        yield ['PT1H30M', true];
        yield ['P1DT12H', true];
        yield ['PT0S', true];
        yield ['P', false];
        yield ['PT', false];
        yield ['P1DT', false];
        yield ['P1H', false];
        yield ['PT1D', false];
        yield ['P1.5D', false];
        yield ['-P1D', false];
        yield ['1 day', false];
        yield [86400, false];
    }

    #[DataProvider('dateIntervals')]
    public function testValidatesDateIntervals(string|int $value, bool $valid): void
    {
        $violations = Validation::createValidator()->validate($value, new DateIntervalType()->getConstraints());

        self::assertSame($valid, \count($violations) === 0);

        if ($valid) {
            self::assertInstanceOf(\DateInterval::class, new \DateInterval($value));
        }
    }

    public function testRejectsClassesThatAreNoTypes(): void
    {
        $this->expectException(InvalidSchemaException::class);

        new Types([\ArrayObject::class]);
    }
}
