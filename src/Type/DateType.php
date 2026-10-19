<?php

declare(strict_types=1);

namespace Xchert\Schema\Type;

use Symfony\Component\Validator\Constraints as Assert;

class DateType extends Type
{
    public const string NAME = 'date';

    public static function getName(): string
    {
        return self::NAME;
    }

    public function getConstraints(): array
    {
        return [new Assert\Type('string'), new Assert\Date()];
    }
}
