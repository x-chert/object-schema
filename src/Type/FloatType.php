<?php

declare(strict_types=1);

namespace Xchert\Schema\Type;

use Symfony\Component\Validator\Constraints as Assert;

final class FloatType extends Type
{
    public const string NAME = 'float';

    public static function getName(): string
    {
        return self::NAME;
    }

    public function getConstraints(): array
    {
        return [new Assert\Type('float')];
    }
}
