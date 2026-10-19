<?php

declare(strict_types=1);

namespace Xchert\Schema\Type;

use Symfony\Component\Validator\Constraints as Assert;

final class IntegerType extends Type
{
    public const string NAME = 'integer';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getAliases(): array
    {
        return ['int'];
    }

    public function getConstraints(): array
    {
        return [new Assert\Type('int')];
    }
}
