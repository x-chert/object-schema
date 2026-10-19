<?php

declare(strict_types=1);

namespace Xchert\Schema\Type;

use Symfony\Component\Validator\Constraints as Assert;

final class BooleanType extends Type
{
    public const string NAME = 'boolean';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getAliases(): array
    {
        return ['bool'];
    }

    public function getConstraints(): array
    {
        return [new Assert\Type('bool')];
    }
}
