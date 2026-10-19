<?php

declare(strict_types=1);

namespace Xchert\Schema\Type;

use Symfony\Component\Validator\Constraints as Assert;

class DateTimeType extends Type
{
    public const string NAME = 'datetime';

    public const string PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/';

    public static function getName(): string
    {
        return self::NAME;
    }

    public function getConstraints(): array
    {
        return [new Assert\Type('string'), new Assert\Regex(self::PATTERN, message: 'This value is not a valid RFC 3339 date and time.')];
    }
}
