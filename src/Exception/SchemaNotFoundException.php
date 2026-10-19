<?php

declare(strict_types=1);

namespace Xchert\Schema\Exception;

class SchemaNotFoundException extends \OutOfBoundsException
{
    public static function forName(string $name): self
    {
        return new self(\sprintf('Schema "%s" is not registered.', $name));
    }
}
