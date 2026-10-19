<?php

declare(strict_types=1);

namespace Xchert\Schema\Type;

use Symfony\Component\Validator\Constraints as Assert;

class DateIntervalType extends Type
{
    public const string NAME = 'dateinterval';

    /**
     * ISO 8601 duration as accepted by \DateInterval, e.g. "P1Y2M", "P2W3D" or "PT1H30M".
     */
    public const string PATTERN = '/^P(?!$)(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(?=\d)(\d+H)?(\d+M)?(\d+S)?)?$/';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getAliases(): array
    {
        return ['duration'];
    }

    public function getConstraints(): array
    {
        return [new Assert\Type('string'), new Assert\Regex(self::PATTERN, message: 'This value is not a valid ISO 8601 duration.')];
    }
}
